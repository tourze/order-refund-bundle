<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;

#[AsCommand(
    name: 'order-refund:init-express-companies',
    description: '初始化快递公司基础数据'
)]
final class InitExpressCompaniesCommand extends Command
{
    /**
     * @var array<int, array{
     *     code: string,
     *     name: string,
     *     trackingUrlTemplate: string,
     *     sortOrder: int
     * }>
     */
    private array $defaultCompanies = [
        [
            'code' => 'SF',
            'name' => '顺丰',
            'trackingUrlTemplate' => 'https://www.sf-express.com/chn/sc/dynamic_function/waybill/#search/bill-number/%s',
            'sortOrder' => 1,
        ],
        [
            'code' => 'STO',
            'name' => '申通',
            'trackingUrlTemplate' => 'https://www.sto.cn/query.html?no=%s',
            'sortOrder' => 2,
        ],
        [
            'code' => 'YD',
            'name' => '韵达',
            'trackingUrlTemplate' => 'https://www.yundaex.com/index.php/query/index.html?no=%s',
            'sortOrder' => 3,
        ],
        [
            'code' => 'ZTO',
            'name' => '中通',
            'trackingUrlTemplate' => 'https://www.zto.com/Home/QueryOrderInfo?txtBillCode=%s',
            'sortOrder' => 4,
        ],
        [
            'code' => 'YTO',
            'name' => '圆通',
            'trackingUrlTemplate' => 'https://www.yto.net.cn/query.html?no=%s',
            'sortOrder' => 5,
        ],
        [
            'code' => 'EMS',
            'name' => '邮政EMS',
            'trackingUrlTemplate' => 'https://www.ems.com.cn/queryList?mailNum=%s',
            'sortOrder' => 6,
        ],
        [
            'code' => 'JD',
            'name' => '京东',
            'trackingUrlTemplate' => 'https://www.jd.com/track?waybillCode=%s',
            'sortOrder' => 7,
        ],
        [
            'code' => 'DBL',
            'name' => '德邦',
            'trackingUrlTemplate' => 'https://www.deppon.com/internetBillQuery.action?billNo=%s',
            'sortOrder' => 8,
        ],
        [
            'code' => 'ZJS',
            'name' => '宅急送',
            'trackingUrlTemplate' => 'http://www.zjs.com.cn/query/single.asp?OrderNumber=%s',
            'sortOrder' => 9,
        ],
        [
            'code' => 'HTKY',
            'name' => '百世汇通',
            'trackingUrlTemplate' => 'https://www.800best.com/queryOrder.do?order=%s',
            'sortOrder' => 10,
        ],
        [
            'code' => 'UC',
            'name' => '优速',
            'trackingUrlTemplate' => 'http://www.uc56.com/guest/trackQuery.htm?trackNo=%s',
            'sortOrder' => 11,
        ],
        [
            'code' => 'TTKDEX',
            'name' => '天天',
            'trackingUrlTemplate' => 'http://www.ttkdex.com/ttkd_single_result.aspx?wen=%s',
            'sortOrder' => 12,
        ],
        [
            'code' => 'FAST',
            'name' => '快捷',
            'trackingUrlTemplate' => 'http://www.fastexpress.com.cn/cn/track.html?billcode=%s',
            'sortOrder' => 13,
        ],
        [
            'code' => 'AJ',
            'name' => '安捷',
            'trackingUrlTemplate' => 'http://www.anjie56.com/QueryResult.aspx?OrderNumber=%s',
            'sortOrder' => 14,
        ],
        [
            'code' => 'OTHER',
            'name' => '其他',
            'trackingUrlTemplate' => '',
            'sortOrder' => 99,
        ],
    ];

    public function __construct(
        private readonly ExpressCompanyRepository $expressCompanyRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制重新初始化（会覆盖现有数据）')
            ->addOption('inactive', null, InputOption::VALUE_NONE, '将初始化的快递公司设为非活跃状态')
            ->setHelp('此命令会初始化系统默认的快递公司数据。')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $inactive = (bool) $input->getOption('inactive');

        $io->title('初始化快递公司数据');

        if (!$this->shouldProceed($io, $force)) {
            return Command::SUCCESS;
        }

        $stats = $this->processCompanies($io, $force, $inactive);
        $this->finalizeSaving();
        $this->displayResults($io, $stats);

        return Command::SUCCESS;
    }

    private function shouldProceed(SymfonyStyle $io, bool $force): bool
    {
        $existingCount = $this->expressCompanyRepository->count([]);
        if (0 === $existingCount || $force) {
            return true;
        }

        $io->warning(sprintf('系统中已存在 %d 个快递公司记录。', $existingCount));

        return $io->confirm('是否继续添加新的快递公司？（已存在的不会重复添加）');
    }

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    private function processCompanies(SymfonyStyle $io, bool $force, bool $inactive): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($this->defaultCompanies as $companyData) {
            $result = $this->processCompany($io, $companyData, $force, $inactive);
            ++$stats[$result];
        }

        return [
            'created' => $stats['created'],
            'updated' => $stats['updated'],
            'skipped' => $stats['skipped'],
        ];
    }

    /**
     * @param array{code: string, name: string, trackingUrlTemplate: string, sortOrder: int} $companyData
     */
    private function processCompany(SymfonyStyle $io, array $companyData, bool $force, bool $inactive): string
    {
        $existing = $this->expressCompanyRepository->findByCode($companyData['code']);

        if (null !== $existing) {
            return $this->handleExistingCompany($io, $existing, $companyData, $force, $inactive);
        }

        return $this->createNewCompany($io, $companyData, $inactive);
    }

    /**
     * @param array{code: string, name: string, trackingUrlTemplate: string, sortOrder: int} $companyData
     */
    private function handleExistingCompany(
        SymfonyStyle $io,
        ExpressCompany $existing,
        array $companyData,
        bool $force,
        bool $inactive,
    ): string {
        if (!$force) {
            $io->text(sprintf('已跳过: %s (%s) - 已存在', $companyData['name'], $companyData['code']));

            return 'skipped';
        }

        $existing->setName($companyData['name']);
        $existing->setTrackingUrlTemplate($companyData['trackingUrlTemplate']);
        $existing->setSortOrder($companyData['sortOrder']);
        $existing->setIsActive(!$inactive);

        $this->expressCompanyRepository->save($existing);
        $io->text(sprintf('已更新: %s (%s)', $companyData['name'], $companyData['code']));

        return 'updated';
    }

    /**
     * @param array{code: string, name: string, trackingUrlTemplate: string, sortOrder: int} $companyData
     */
    private function createNewCompany(SymfonyStyle $io, array $companyData, bool $inactive): string
    {
        $company = new ExpressCompany();
        $company->setCode($companyData['code']);
        $company->setName($companyData['name']);
        $company->setTrackingUrlTemplate($companyData['trackingUrlTemplate']);
        $company->setSortOrder($companyData['sortOrder']);
        $company->setIsActive(!$inactive);

        $this->expressCompanyRepository->save($company, true);
        $io->text(sprintf('已创建: %s (%s)', $companyData['name'], $companyData['code']));

        return 'created';
    }

    private function finalizeSaving(): void
    {
        // All entities are already flushed individually
    }

    /**
     * @param array{created: int, updated: int, skipped: int} $stats
     */
    private function displayResults(SymfonyStyle $io, array $stats): void
    {
        $io->success(sprintf(
            '初始化完成！创建: %d，更新: %d，跳过: %d',
            $stats['created'],
            $stats['updated'],
            $stats['skipped']
        ));

        $activeCount = $this->expressCompanyRepository->countActiveCompanies();
        $totalCount = $this->expressCompanyRepository->count([]);

        $io->info(sprintf('当前系统中共有 %d 个快递公司，其中 %d 个为活跃状态。', $totalCount, $activeCount));
    }
}
