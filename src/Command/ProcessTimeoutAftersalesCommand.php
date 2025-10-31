<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Service\AutoAuditService;

/**
 * 处理超时售后申请的定时命令
 */
#[AsCommand(
    name: 'aftersales:process-timeout',
    description: '自动处理超时的售后申请',
)]
#[WithMonologChannel(channel: 'order_refund')]
class ProcessTimeoutAftersalesCommand extends Command
{
    public function __construct(
        private readonly AftersalesRepository $aftersalesRepository,
        private readonly AutoAuditService $autoAuditService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('自动处理超时的售后申请')
            ->setHelp('扫描所有超时的售后申请，并根据业务规则进行自动处理')
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                '每批处理的数量',
                100
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                '仅预览，不执行实际操作'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $config = $this->parseInputOptions($input);

        $this->initializeOutput($io, (bool) $config['isDryRun']);

        try {
            $result = $this->processBatches($io, $config);
            $this->displayResults($io, $result);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->handleError($io, $e);

            return Command::FAILURE;
        }
    }

    /**
     * @return array{batchSize: int, isDryRun: bool}
     */
    private function parseInputOptions(InputInterface $input): array
    {
        $batchSizeOption = $input->getOption('batch-size');

        return [
            'batchSize' => is_numeric($batchSizeOption) ? (int) $batchSizeOption : 100,
            'isDryRun' => (bool) $input->getOption('dry-run'),
        ];
    }

    private function initializeOutput(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->title('处理超时售后申请');

        if ($isDryRun) {
            $io->note('运行在预览模式，不会执行实际操作');
        }
    }

    /**
     * @param array{batchSize: int, isDryRun: bool} $config
     * @return array<string, int>
     */
    private function processBatches(SymfonyStyle $io, array $config): array
    {
        $processedCount = 0;
        $errorCount = 0;
        $offset = 0;

        do {
            $timeoutAftersales = $this->findTimeoutAftersales($offset, $config['batchSize']);
            $currentBatchCount = count($timeoutAftersales);

            if (0 === $currentBatchCount) {
                break;
            }

            $batchResult = $this->processBatch($io, $timeoutAftersales, $config['isDryRun']);
            $processedCount += $batchResult['processed'];
            $errorCount += $batchResult['errors'];

            $this->finalizeBatch($config['isDryRun']);
            $offset += $config['batchSize'];
        } while ($currentBatchCount >= $config['batchSize']);

        return ['processed' => $processedCount, 'errors' => $errorCount];
    }

    /**
     * @param array<Aftersales> $timeoutAftersales
     * @return array<string, int>
     */
    private function processBatch(SymfonyStyle $io, array $timeoutAftersales, bool $isDryRun): array
    {
        $processedCount = 0;
        $errorCount = 0;

        $io->progressStart(count($timeoutAftersales));

        foreach ($timeoutAftersales as $aftersales) {
            if ($this->processSingleAftersales($io, $aftersales, $isDryRun)) {
                ++$processedCount;
            } else {
                ++$errorCount;
            }
            $io->progressAdvance();
        }

        $io->progressFinish();

        return ['processed' => $processedCount, 'errors' => $errorCount];
    }

    private function processSingleAftersales(SymfonyStyle $io, Aftersales $aftersales, bool $isDryRun): bool
    {
        try {
            if ($this->processTimeoutAftersales($aftersales, $isDryRun)) {
                $this->logSuccess($aftersales, $isDryRun);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logError($io, $aftersales, $e);

            return false;
        }
    }

    private function logSuccess(Aftersales $aftersales, bool $isDryRun): void
    {
        if (!$isDryRun) {
            $this->logger->info('售后申请超时自动处理成功', [
                'aftersales_id' => $aftersales->getId(),
                'original_state' => $aftersales->getState()->value,
            ]);
        }
    }

    private function logError(SymfonyStyle $io, Aftersales $aftersales, \Exception $e): void
    {
        $this->logger->error('处理超时售后申请失败', [
            'aftersales_id' => $aftersales->getId(),
            'error' => $e->getMessage(),
        ]);

        if ($io->isVerbose()) {
            $io->error(sprintf(
                '处理售后申请 %s 失败: %s',
                $aftersales->getId(),
                $e->getMessage()
            ));
        }
    }

    private function finalizeBatch(bool $isDryRun): void
    {
        if (!$isDryRun) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }

    /**
     * @param array<string, int> $result
     */
    private function displayResults(SymfonyStyle $io, array $result): void
    {
        $io->success([
            sprintf('处理完成！共处理 %d 个超时售后申请', $result['processed']),
            $result['errors'] > 0 ? sprintf('失败 %d 个', $result['errors']) : '无错误',
        ]);
    }

    private function handleError(SymfonyStyle $io, \Exception $e): void
    {
        $io->error('命令执行失败: ' . $e->getMessage());
        $this->logger->critical('超时售后处理命令执行失败', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * 查找超时的售后申请
     *
     * @return array<Aftersales>
     */
    private function findTimeoutAftersales(int $offset, int $limit): array
    {
        $qb = $this->aftersalesRepository->createQueryBuilder('a')
            ->where('a.autoProcessTime IS NOT NULL')
            ->andWhere('a.autoProcessTime <= :now')
            ->andWhere('a.state IN (:pendingStates)')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('pendingStates', [
                AftersalesState::PENDING_APPROVAL,
                AftersalesState::PENDING_RETURN,
                AftersalesState::PENDING_RECEIVE,
            ])
            ->orderBy('a.autoProcessTime', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        $result = $qb->getQuery()->getResult();
        assert(is_array($result));

        /** @var array<Aftersales> $result */
        return $result;
    }

    /**
     * 处理单个超时的售后申请
     */
    private function processTimeoutAftersales(Aftersales $aftersales, bool $isDryRun): bool
    {
        if (!$this->autoAuditService->isEligibleForAutoProcess($aftersales)) {
            return false;
        }

        $originalState = $aftersales->getState();

        switch ($originalState) {
            case AftersalesState::PENDING_APPROVAL:
                // 超时自动审核通过
                if (!$isDryRun) {
                    $aftersales->setState(AftersalesState::APPROVED);
                    $aftersales->setAuditTime(new \DateTimeImmutable());
                    $aftersales->setAutoProcessTime(null); // 清除自动处理时间
                }
                break;

            case AftersalesState::PENDING_RETURN:
                // 超时自动取消退货
                if (!$isDryRun) {
                    $aftersales->setState(AftersalesState::CANCELLED);
                    $aftersales->setCompletedTime(new \DateTimeImmutable());
                    $aftersales->setAutoProcessTime(null);
                }
                break;

            case AftersalesState::PENDING_RECEIVE:
                // 超时自动确认收货并进入退款流程
                if (!$isDryRun) {
                    $aftersales->setState(AftersalesState::PENDING_REFUND);
                    $aftersales->setAutoProcessTime(null);
                }
                break;

            default:
                return false;
        }

        return true;
    }
}
