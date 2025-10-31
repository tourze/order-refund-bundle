<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Return;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Procedure\Return\GetExpressCompaniesProcedure;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;

/**
 * @internal
 */
#[CoversClass(GetExpressCompaniesProcedure::class)]
#[RunTestsInSeparateProcesses]
class GetExpressCompaniesProcedureTest extends AbstractProcedureTestCase
{
    private GetExpressCompaniesProcedure $procedure;

    private ExpressCompanyRepository&MockObject $expressCompanyRepository;

    protected function onSetUp(): void
    {
        $this->expressCompanyRepository = $this->createMock(ExpressCompanyRepository::class);
        self::getContainer()->set(ExpressCompanyRepository::class, $this->expressCompanyRepository);
        $this->procedure = self::getService(GetExpressCompaniesProcedure::class);
    }

    public function testExecuteReturnsEmptyArrayWhenNoCompanies(): void
    {
        $this->expressCompanyRepository
            ->method('findActiveCompanies')
            ->willReturn([])
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        self::assertArrayHasKey('companies', $result);
        self::assertArrayHasKey('total', $result);
        self::assertSame([], $result['companies']);
        self::assertSame(0, $result['total']);
    }

    public function testExecuteReturnsSingleCompany(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('getCode')->willReturn('SF');
        $company->method('getName')->willReturn('顺丰速运');
        $company->method('getTrackingUrlTemplate')->willReturn('https://www.sf-express.com/track/{code}');

        $this->expressCompanyRepository
            ->method('findActiveCompanies')
            ->willReturn([$company])
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        self::assertArrayHasKey('companies', $result);
        self::assertArrayHasKey('total', $result);
        self::assertSame(1, $result['total']);

        $companies = $result['companies'];
        self::assertIsArray($companies);
        self::assertCount(1, $companies);

        $companyData = $companies[0];
        self::assertIsArray($companyData);
        self::assertArrayHasKey('code', $companyData);
        self::assertArrayHasKey('name', $companyData);
        self::assertArrayHasKey('trackingUrl', $companyData);
        self::assertSame('SF', $companyData['code']);
        self::assertSame('顺丰速运', $companyData['name']);
        self::assertSame('https://www.sf-express.com/track/{code}', $companyData['trackingUrl']);
    }

    public function testExecuteReturnsMultipleCompanies(): void
    {
        $company1 = $this->createMock(ExpressCompany::class);
        $company1->method('getCode')->willReturn('SF');
        $company1->method('getName')->willReturn('顺丰速运');
        $company1->method('getTrackingUrlTemplate')->willReturn('https://www.sf-express.com/track/{code}');

        $company2 = $this->createMock(ExpressCompany::class);
        $company2->method('getCode')->willReturn('YTO');
        $company2->method('getName')->willReturn('圆通速递');
        $company2->method('getTrackingUrlTemplate')->willReturn('https://www.yto.net.cn/query/{code}');

        $company3 = $this->createMock(ExpressCompany::class);
        $company3->method('getCode')->willReturn('STO');
        $company3->method('getName')->willReturn('申通快递');
        $company3->method('getTrackingUrlTemplate')->willReturn(null);

        $companies = [$company1, $company2, $company3];

        $this->expressCompanyRepository
            ->method('findActiveCompanies')
            ->willReturn($companies)
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        self::assertArrayHasKey('companies', $result);
        self::assertArrayHasKey('total', $result);
        self::assertSame(3, $result['total']);

        $companies = $result['companies'];
        self::assertIsArray($companies);
        self::assertCount(3, $companies);

        // 验证第一个公司
        $companyData1 = $companies[0];
        self::assertIsArray($companyData1);
        self::assertSame('SF', $companyData1['code']);
        self::assertSame('顺丰速运', $companyData1['name']);
        self::assertSame('https://www.sf-express.com/track/{code}', $companyData1['trackingUrl']);

        // 验证第二个公司
        $companyData2 = $companies[1];
        self::assertIsArray($companyData2);
        self::assertSame('YTO', $companyData2['code']);
        self::assertSame('圆通速递', $companyData2['name']);
        self::assertSame('https://www.yto.net.cn/query/{code}', $companyData2['trackingUrl']);

        // 验证第三个公司（trackingUrl为null）
        $companyData3 = $companies[2];
        self::assertIsArray($companyData3);
        self::assertSame('STO', $companyData3['code']);
        self::assertSame('申通快递', $companyData3['name']);
        self::assertNull($companyData3['trackingUrl']);
    }

    public function testExecuteHandlesNullValues(): void
    {
        $company = $this->createMock(ExpressCompany::class);
        $company->method('getCode')->willReturn(null);
        $company->method('getName')->willReturn(null);
        $company->method('getTrackingUrlTemplate')->willReturn(null);

        $this->expressCompanyRepository
            ->method('findActiveCompanies')
            ->willReturn([$company])
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        self::assertSame(1, $result['total']);

        $companies = $result['companies'];
        self::assertIsArray($companies);
        self::assertCount(1, $companies);

        $companyData = $companies[0];
        self::assertIsArray($companyData);
        self::assertNull($companyData['code']);
        self::assertNull($companyData['name']);
        self::assertNull($companyData['trackingUrl']);
    }

    public function testExecuteCallsRepositoryCorrectly(): void
    {
        $this->expressCompanyRepository
            ->expects(self::once())
            ->method('findActiveCompanies')
            ->willReturn([])
        ;

        $this->procedure->execute();
    }

    public function testExecuteReturnsCorrectArrayStructure(): void
    {
        $this->expressCompanyRepository
            ->method('findActiveCompanies')
            ->willReturn([])
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        self::assertArrayHasKey('companies', $result);
        self::assertArrayHasKey('total', $result);
        self::assertCount(2, $result); // 只有这两个键
    }

    public function testExecuteReturnsConsistentTotalCount(): void
    {
        $companies = [
            $this->createMock(ExpressCompany::class),
            $this->createMock(ExpressCompany::class),
            $this->createMock(ExpressCompany::class),
        ];

        foreach ($companies as $i => $company) {
            $company->method('getCode')->willReturn("CODE{$i}");
            $company->method('getName')->willReturn("名称{$i}");
            $company->method('getTrackingUrlTemplate')->willReturn("https://example.com/{$i}");
        }

        $this->expressCompanyRepository
            ->method('findActiveCompanies')
            ->willReturn($companies)
        ;

        $result = $this->procedure->execute();

        self::assertIsArray($result);
        self::assertSame(count($companies), $result['total']);

        $resultCompanies = $result['companies'];
        self::assertIsArray($resultCompanies);
        self::assertSame(count($companies), count($resultCompanies));
    }
}
