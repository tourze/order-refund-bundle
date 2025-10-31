<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;
use Tourze\OrderRefundBundle\Service\ExpressTrackingService;

/**
 * @internal
 */
#[CoversClass(ExpressTrackingService::class)]
class ExpressTrackingServiceTest extends TestCase
{
    private ExpressTrackingService $service;

    private ExpressCompanyRepository&MockObject $expressCompanyRepository;

    protected function setUp(): void
    {
        $this->expressCompanyRepository = $this->createMock(ExpressCompanyRepository::class);
        $this->service = new ExpressTrackingService($this->expressCompanyRepository);
    }

    public function testGenerateTrackingUrlWithValidCompanyCode(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('getTrackingUrlTemplate')->willReturn('https://www.sf-express.com/track/%s');

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('SF')
            ->willReturn($company)
        ;

        $result = $this->service->generateTrackingUrl('SF', 'SF1234567890');

        self::assertSame('https://www.sf-express.com/track/SF1234567890', $result);
    }

    public function testGenerateTrackingUrlWithValidCompanyName(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('getTrackingUrlTemplate')->willReturn('https://www.yto.net.cn/query/%s');

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('圆通速递')
            ->willReturn(null)
        ;

        $this->expressCompanyRepository
            ->method('findOneBy')
            ->with(['name' => '圆通速递', 'isActive' => true])
            ->willReturn($company)
        ;

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
        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('NONEXISTENT')
            ->willReturn(null)
        ;

        $this->expressCompanyRepository
            ->method('findOneBy')
            ->with(['name' => 'NONEXISTENT', 'isActive' => true])
            ->willReturn(null)
        ;

        $result = $this->service->generateTrackingUrl('NONEXISTENT', 'TEST1234567890');

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlWithCompanyHavingNullTemplate(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('getTrackingUrlTemplate')->willReturn(null);

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('SF')
            ->willReturn($company)
        ;

        $result = $this->service->generateTrackingUrl('SF', 'SF1234567890');

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlWithCompanyHavingEmptyTemplate(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('getTrackingUrlTemplate')->willReturn('');

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('SF')
            ->willReturn($company)
        ;

        $result = $this->service->generateTrackingUrl('SF', 'SF1234567890');

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlForReturnWithValidData(): void
    {
        $returnOrder = $this->createMock(ReturnOrder::class);
        $returnOrder->method('getExpressCompany')->willReturn('SF');
        $returnOrder->method('getTrackingNo')->willReturn('SF1234567890');

        $company = $this->createMock(ExpressCompany::class);
        $company->method('getTrackingUrlTemplate')->willReturn('https://www.sf-express.com/track/%s');

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('SF')
            ->willReturn($company)
        ;

        $result = $this->service->generateTrackingUrlForReturn($returnOrder);

        self::assertSame('https://www.sf-express.com/track/SF1234567890', $result);
    }

    public function testGenerateTrackingUrlForReturnWithNullExpressCompany(): void
    {
        $returnOrder = $this->createMock(ReturnOrder::class);
        $returnOrder->method('getExpressCompany')->willReturn(null);
        $returnOrder->method('getTrackingNo')->willReturn('SF1234567890');

        $result = $this->service->generateTrackingUrlForReturn($returnOrder);

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlForReturnWithNullTrackingNo(): void
    {
        $returnOrder = $this->createMock(ReturnOrder::class);
        $returnOrder->method('getExpressCompany')->willReturn('SF');
        $returnOrder->method('getTrackingNo')->willReturn(null);

        $result = $this->service->generateTrackingUrlForReturn($returnOrder);

        self::assertNull($result);
    }

    public function testGenerateTrackingUrlForReturnWithBothNull(): void
    {
        $returnOrder = $this->createMock(ReturnOrder::class);
        $returnOrder->method('getExpressCompany')->willReturn(null);
        $returnOrder->method('getTrackingNo')->willReturn(null);

        $result = $this->service->generateTrackingUrlForReturn($returnOrder);

        self::assertNull($result);
    }

    public function testValidateExpressCompanyWithActiveCompany(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('isActive')->willReturn(true);

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('SF')
            ->willReturn($company)
        ;

        $result = $this->service->validateExpressCompany('SF');

        self::assertTrue($result);
    }

    public function testValidateExpressCompanyWithInactiveCompany(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('isActive')->willReturn(false);

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('SF')
            ->willReturn($company)
        ;

        $result = $this->service->validateExpressCompany('SF');

        self::assertFalse($result);
    }

    public function testValidateExpressCompanyWithNonExistentCompany(): void
    {
        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('NONEXISTENT')
            ->willReturn(null)
        ;

        $this->expressCompanyRepository
            ->method('findOneBy')
            ->with(['name' => 'NONEXISTENT', 'isActive' => true])
            ->willReturn(null)
        ;

        $result = $this->service->validateExpressCompany('NONEXISTENT');

        self::assertFalse($result);
    }

    public function testValidateExpressCompanyByName(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('isActive')->willReturn(true);

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('顺丰速运')
            ->willReturn(null)
        ;

        $this->expressCompanyRepository
            ->method('findOneBy')
            ->with(['name' => '顺丰速运', 'isActive' => true])
            ->willReturn($company)
        ;

        $result = $this->service->validateExpressCompany('顺丰速运');

        self::assertTrue($result);
    }

    public function testGetAvailableCompanies(): void
    {
        $company1 = $this->createMock(ExpressCompany::class);
        $company2 = $this->createMock(ExpressCompany::class);
        $companies = [$company1, $company2];

        $this->expressCompanyRepository
            ->expects(self::once())
            ->method('findActiveCompanies')
            ->willReturn($companies)
        ;

        $result = $this->service->getAvailableCompanies();

        self::assertSame($companies, $result);
        self::assertCount(2, $result);
    }

    public function testFindCompanyByCodePriority(): void
    {
        $companyByCode = $this->createMock(ExpressCompany::class);
        $companyByCode->method('getTrackingUrlTemplate')->willReturn('https://code.example.com/%s');

        $companyByName = $this->createMock(ExpressCompany::class);
        $companyByName->method('getTrackingUrlTemplate')->willReturn('https://name.example.com/%s');

        // 设置按代码查找返回结果，按名称查找不应该被调用
        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('SF')
            ->willReturn($companyByCode)
        ;

        $this->expressCompanyRepository
            ->expects(self::never())
            ->method('findOneBy')
        ;

        $result = $this->service->generateTrackingUrl('SF', 'TEST123');

        self::assertSame('https://code.example.com/TEST123', $result);
    }

    public function testGenerateTrackingUrlHandlesSprintfCorrectly(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        // 测试单个占位符的情况
        $company->method('getTrackingUrlTemplate')->willReturn('https://example.com/track?code=%s');

        $this->expressCompanyRepository
            ->method('findByCode')
            ->with('TEST')
            ->willReturn($company)
        ;

        $result = $this->service->generateTrackingUrl('TEST', 'ABC123');

        self::assertSame('https://example.com/track?code=ABC123', $result);
    }

    public function testValidateExpressCompanySearchesByBothCodeAndName(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('isActive')->willReturn(true);

        // 首先按代码查找失败
        $this->expressCompanyRepository
            ->expects(self::once())
            ->method('findByCode')
            ->with('顺丰速运')
            ->willReturn(null)
        ;

        // 然后按名称查找成功
        $this->expressCompanyRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => '顺丰速运', 'isActive' => true])
            ->willReturn($company)
        ;

        $result = $this->service->validateExpressCompany('顺丰速运');

        self::assertTrue($result);
    }
}
