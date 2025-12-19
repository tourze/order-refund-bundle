<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;
use Tourze\OrderRefundBundle\Service\ExpressTrackingService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ExpressTrackingService::class)]
#[RunTestsInSeparateProcesses]
final class ExpressTrackingServiceTest extends AbstractIntegrationTestCase
{
    private ExpressTrackingService $service;

    private ExpressCompanyRepository $expressCompanyRepository;

    protected function onSetUp(): void
    {
        $this->expressCompanyRepository = self::getService(ExpressCompanyRepository::class);
        $this->service = self::getService(ExpressTrackingService::class);

        // 清空测试数据
        $this->clearTable();
    }

    private function clearTable(): void
    {
        $entityManager = self::getEntityManager();
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM order_express_companies');
    }

    private function createTestCompany(
        string $code,
        string $name,
        ?string $trackingUrlTemplate = null,
        bool $isActive = true,
        int $sortOrder = 0,
    ): ExpressCompany {
        $company = new ExpressCompany();
        $company->setCode($code);
        $company->setName($name);
        $company->setTrackingUrlTemplate($trackingUrlTemplate);
        $company->setIsActive($isActive);
        $company->setSortOrder($sortOrder);

        $this->expressCompanyRepository->save($company, true);

        return $company;
    }

    public function testGenerateTrackingUrlWithValidCompanyCode(): void
    {
        $this->createTestCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/%s');

        $result = $this->service->generateTrackingUrl('SF', 'SF1234567890');

        self::assertSame('https://www.sf-express.com/track/SF1234567890', $result);
    }

    public function testGenerateTrackingUrlWithValidCompanyName(): void
    {
        // 创建一个公司，不使用名称作为代码，以便测试按名称查找的逻辑
        $this->createTestCompany('YTO', '圆通速递', 'https://www.yto.net.cn/query/%s');

        $result = $this->service->generateTrackingUrl('圆通速递', 'YTO1234567890');

        self::assertSame('https://www.yto.net.cn/query/YTO1234567890', $result);
    }

    #[DataProvider('invalidParametersProvider')]
    public function testGenerateTrackingUrlWithInvalidParameters(
        string $expressCompanyName,
        string $trackingNo,
        ?string $expected,
    ): void {
        $result = $this->service->generateTrackingUrl($expressCompanyName, $trackingNo);
        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, string, null}>
     */
    public static function invalidParametersProvider(): array
    {
        return [
            'empty_company_name' => ['', 'SF1234567890', null],
            'empty_tracking_no' => ['SF', '', null],
            'both_empty' => ['', '', null],
        ];
    }

    public function testGenerateTrackingUrlWithNonExistentCompany(): void
    {
        // 不创建任何公司，直接测试

        $result = $this->service->generateTrackingUrl('NONEXISTENT', 'TEST1234567890');

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlWithCompanyHavingNullTemplate(): void
    {
        $this->createTestCompany('SF', '顺丰速运', null);

        $result = $this->service->generateTrackingUrl('SF', 'SF1234567890');

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlWithCompanyHavingEmptyTemplate(): void
    {
        $this->createTestCompany('SF', '顺丰速运', '');

        $result = $this->service->generateTrackingUrl('SF', 'SF1234567890');

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlForReturnWithValidData(): void
    {
        $this->createTestCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/%s');

        $returnOrder = new ReturnOrder();
        $returnOrder->setExpressCompany('SF');
        $returnOrder->setTrackingNo('SF1234567890');

        $result = $this->service->generateTrackingUrlForReturn($returnOrder);

        self::assertSame('https://www.sf-express.com/track/SF1234567890', $result);
    }

    public function testGenerateTrackingUrlForReturnWithNullExpressCompany(): void
    {
        $returnOrder = new ReturnOrder();
        $returnOrder->setExpressCompany(null);
        $returnOrder->setTrackingNo('SF1234567890');

        $result = $this->service->generateTrackingUrlForReturn($returnOrder);

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlForReturnWithNullTrackingNo(): void
    {
        $returnOrder = new ReturnOrder();
        $returnOrder->setExpressCompany('SF');
        $returnOrder->setTrackingNo(null);

        $result = $this->service->generateTrackingUrlForReturn($returnOrder);

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlForReturnWithBothNull(): void
    {
        $returnOrder = new ReturnOrder();
        $returnOrder->setExpressCompany(null);
        $returnOrder->setTrackingNo(null);

        $result = $this->service->generateTrackingUrlForReturn($returnOrder);

        self::assertNull($result);
    }

    public function testValidateExpressCompanyWithActiveCompany(): void
    {
        $this->createTestCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/%s', true);

        $result = $this->service->validateExpressCompany('SF');

        self::assertTrue($result);
    }

    public function testValidateExpressCompanyWithInactiveCompany(): void
    {
        $this->createTestCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/%s', false);

        $result = $this->service->validateExpressCompany('SF');

        self::assertFalse($result);
    }

    public function testValidateExpressCompanyWithNonExistentCompany(): void
    {
        // 不创建任何公司，直接测试

        $result = $this->service->validateExpressCompany('NONEXISTENT');

        self::assertFalse($result);
    }

    public function testValidateExpressCompanyByName(): void
    {
        // 创建一个代码和名称不同的快递公司，以确保按名称查找的逻辑被测试
        $this->createTestCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/%s', true);

        $result = $this->service->validateExpressCompany('顺丰速运');

        self::assertTrue($result);
    }

    public function testGetAvailableCompanies(): void
    {
        $this->createTestCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/%s', true, 1);
        $this->createTestCompany('YTO', '圆通速递', 'https://www.yto.net.cn/query/%s', true, 2);
        $this->createTestCompany('INACTIVE', '已停用快递', null, false, 0);

        $result = $this->service->getAvailableCompanies();

        // 只应返回启用的公司，按 sortOrder 排序
        self::assertCount(2, $result);
        self::assertSame('SF', $result[0]->getCode());
        self::assertSame('YTO', $result[1]->getCode());
    }

    public function testFindCompanyByCodePriority(): void
    {
        // 创建一个代码为 SF 的公司
        $this->createTestCompany('SF', '顺丰速运', 'https://code.example.com/%s');

        // 测试：当提供代码 SF 时，应该按代码查找并优先返回
        $result = $this->service->generateTrackingUrl('SF', 'TEST123');

        self::assertSame('https://code.example.com/TEST123', $result);
    }

    public function testGenerateTrackingUrlHandlesSprintfCorrectly(): void
    {
        // 测试单个占位符的情况
        $this->createTestCompany('TEST', '测试快递', 'https://example.com/track?code=%s');

        $result = $this->service->generateTrackingUrl('TEST', 'ABC123');

        self::assertSame('https://example.com/track?code=ABC123', $result);
    }

    public function testValidateExpressCompanySearchesByBothCodeAndName(): void
    {
        // 创建一个代码和名称不同的公司，用于测试代码查找失败后按名称查找的逻辑
        $this->createTestCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/%s', true);

        // 用名称查找（而非代码），应该能找到
        $result = $this->service->validateExpressCompany('顺丰速运');

        self::assertTrue($result);
    }
}
