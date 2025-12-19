<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\OrderRefundBundle\Service\ReturnAddressService;

#[AsCommand(
    name: 'order-refund:init-return-addresses',
    description: '初始化寄回地址基础数据'
)]
final class InitReturnAddressesCommand extends Command
{
    /**
     * @var array<int, array{
     *     name: string,
     *     contactName: string,
     *     contactPhone: string,
     *     province: string,
     *     city: string,
     *     district: string,
     *     address: string,
     *     zipCode: string,
     *     businessHours: string,
     *     specialInstructions: string,
     *     companyName: string,
     *     isDefault: bool,
     *     sortOrder: int
     * }>
     */
    private array $defaultAddresses = [
        [
            'name' => '主要退货仓库',
            'contactName' => '客服中心',
            'contactPhone' => '400-1234-5678',
            'province' => '广东省',
            'city' => '深圳市',
            'district' => '南山区',
            'address' => '科技园南区高新南一道XX号XX大厦XX层',
            'zipCode' => '518057',
            'businessHours' => '周一至周五 9:00-18:00',
            'specialInstructions' => '请在包裹上清楚标注售后单号，以便快速处理',
            'companyName' => '深圳XX科技有限公司',
            'isDefault' => true,
            'sortOrder' => 1,
        ],
        [
            'name' => '华北区退货仓库',
            'contactName' => '华北客服',
            'contactPhone' => '010-12345678',
            'province' => '北京市',
            'city' => '北京市',
            'district' => '朝阳区',
            'address' => '望京SOHO塔XX楼XX室',
            'zipCode' => '100102',
            'businessHours' => '周一至周五 9:00-17:30',
            'specialInstructions' => '北方地区用户退货地址，请注意营业时间',
            'companyName' => '北京XX分公司',
            'isDefault' => false,
            'sortOrder' => 2,
        ],
        [
            'name' => '华东区退货仓库',
            'contactName' => '华东客服',
            'contactPhone' => '021-87654321',
            'province' => '上海市',
            'city' => '上海市',
            'district' => '浦东新区',
            'address' => '张江高科技园区XX路XX号XX大厦XX层',
            'zipCode' => '201203',
            'businessHours' => '周一至周五 8:30-17:30',
            'specialInstructions' => '华东地区用户退货地址，支持上门取件服务',
            'companyName' => '上海XX分公司',
            'isDefault' => false,
            'sortOrder' => 3,
        ],
    ];

    public function __construct(
        private readonly ReturnAddressService $returnAddressService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, '强制重新初始化（会覆盖现有数据）')
            ->addOption('inactive', null, InputOption::VALUE_NONE, '将初始化的地址设为非活跃状态')
            ->addOption('no-default', null, InputOption::VALUE_NONE, '不设置默认地址')
            ->setHelp('此命令会初始化系统默认的寄回地址数据。')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $inactive = (bool) $input->getOption('inactive');
        $noDefault = (bool) $input->getOption('no-default');

        return $this->processInitialization($io, $force, $inactive, $noDefault);
    }

    private function processInitialization(SymfonyStyle $io, bool $force, bool $inactive, bool $noDefault): int
    {
        $io->title('初始化寄回地址数据');

        if (!$this->shouldProceed($io, $force)) {
            return Command::SUCCESS;
        }

        $stats = $this->processAddresses($io, $force, $inactive, $noDefault);
        $this->displayResults($io, $stats);

        return Command::SUCCESS;
    }

    private function shouldProceed(SymfonyStyle $io, bool $force): bool
    {
        if ($force || !$this->returnAddressService->hasAvailableAddress()) {
            return true;
        }

        $io->warning('系统中已存在寄回地址记录。');

        return $io->confirm('是否继续添加新的地址？（已存在的不会重复添加）');
    }

    /**
     * @return array{created: int, skipped: int}
     */
    private function processAddresses(SymfonyStyle $io, bool $force, bool $inactive, bool $noDefault): array
    {
        $stats = ['created' => 0, 'skipped' => 0];

        foreach ($this->defaultAddresses as $addressData) {
            $result = $this->processAddress($io, $addressData, $force, $inactive, $noDefault);
            ++$stats[$result];
        }

        return [
            'created' => $stats['created'],
            'skipped' => $stats['skipped'],
        ];
    }

    /**
     * @param array{
     *     name: string,
     *     contactName: string,
     *     contactPhone: string,
     *     province: string,
     *     city: string,
     *     district: string,
     *     address: string,
     *     zipCode: string,
     *     businessHours: string,
     *     specialInstructions: string,
     *     companyName: string,
     *     isDefault: bool,
     *     sortOrder: int
     * } $addressData
     */
    private function processAddress(
        SymfonyStyle $io,
        array $addressData,
        bool $force,
        bool $inactive,
        bool $noDefault,
    ): string {
        if ($this->addressExists($addressData['name'])) {
            return $this->handleExistingAddress($io, $addressData, $force);
        }

        return $this->createNewAddress($io, $addressData, $inactive, $noDefault);
    }

    private function addressExists(string $name): bool
    {
        return null !== $this->returnAddressService->findByName($name);
    }

    /**
     * @param array{name: string} $addressData
     */
    private function handleExistingAddress(SymfonyStyle $io, array $addressData, bool $force): string
    {
        if ($force) {
            $io->text(sprintf('地址 "%s" 已存在，强制模式下跳过更新', $addressData['name']));
        } else {
            $io->text(sprintf('已跳过: %s - 已存在', $addressData['name']));
        }

        return 'skipped';
    }

    /**
     * @param array{
     *     name: string,
     *     contactName: string,
     *     contactPhone: string,
     *     province: string,
     *     city: string,
     *     district: string,
     *     address: string,
     *     zipCode: string,
     *     businessHours: string,
     *     specialInstructions: string,
     *     companyName: string,
     *     isDefault: bool,
     *     sortOrder: int
     * } $addressData
     */
    private function createNewAddress(
        SymfonyStyle $io,
        array $addressData,
        bool $inactive,
        bool $noDefault,
    ): string {
        try {
            $isDefault = $addressData['isDefault'] && !$noDefault && !$inactive;
            $isActive = !$inactive;

            $this->returnAddressService->createReturnAddress(
                $addressData['name'],
                $addressData['contactName'],
                $addressData['contactPhone'],
                $addressData['province'],
                $addressData['city'],
                $addressData['address'],
                $addressData['district'],
                $addressData['zipCode'],
                $addressData['businessHours'],
                $addressData['specialInstructions'],
                $addressData['companyName'],
                $isDefault,
                $isActive,
                $addressData['sortOrder']
            );

            $status = $this->buildStatusText($isDefault, $isActive);
            $io->text(sprintf('已创建: %s%s', $addressData['name'], $status));

            return 'created';
        } catch (\Exception $e) {
            $io->error(sprintf('创建地址 "%s" 失败: %s', $addressData['name'], $e->getMessage()));

            return 'skipped';
        }
    }

    private function buildStatusText(bool $isDefault, bool $isActive): string
    {
        $statusText = [];
        if ($isDefault) {
            $statusText[] = '默认';
        }
        if (!$isActive) {
            $statusText[] = '未激活';
        }

        return [] === $statusText ? '' : ' (' . implode(', ', $statusText) . ')';
    }

    /**
     * @param array{created: int, skipped: int} $stats
     */
    private function displayResults(SymfonyStyle $io, array $stats): void
    {
        $io->success(sprintf(
            '初始化完成！创建: %d，跳过: %d',
            $stats['created'],
            $stats['skipped']
        ));

        $this->displayCurrentStatus($io);
    }

    private function displayCurrentStatus(SymfonyStyle $io): void
    {
        $hasDefault = $this->returnAddressService->hasDefaultAddress();
        $activeCount = $this->returnAddressService->countActiveAddresses();

        $io->info(sprintf(
            '当前系统中共有 %d 个可用寄回地址%s。',
            $activeCount,
            $hasDefault ? '，已设置默认地址' : '，未设置默认地址'
        ));

        if (!$hasDefault && $activeCount > 0) {
            $io->note('建议通过管理界面设置一个默认寄回地址，以便用户查看。');
        }
    }
}
