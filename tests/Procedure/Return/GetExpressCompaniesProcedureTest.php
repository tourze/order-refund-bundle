<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Return;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Param\Return\GetExpressCompaniesProcedureParam;
use Tourze\OrderRefundBundle\Procedure\Return\GetExpressCompaniesProcedure;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(GetExpressCompaniesProcedure::class)]
#[RunTestsInSeparateProcesses]
final class GetExpressCompaniesProcedureTest extends AbstractProcedureTestCase
{
    private GetExpressCompaniesProcedure $procedure;

    protected function onSetUp(): void
    {
        // 清理现有的快递公司数据
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . ExpressCompany::class)->execute();
        $em->flush();
        $em->clear();

        $this->procedure = self::getService(GetExpressCompaniesProcedure::class);
    }

    private function createExpressCompany(string $code, string $name, ?string $trackingUrl = null, bool $isActive = true): ExpressCompany
    {
        $company = new ExpressCompany();
        $company->setCode($code);
        $company->setName($name);
        $company->setTrackingUrlTemplate($trackingUrl);
        $company->setIsActive($isActive);

        self::getEntityManager()->persist($company);

        return $company;
    }

    public function testExecuteReturnsEmptyArrayWhenNoCompanies(): void
    {
        self::getEntityManager()->flush();

        $result = $this->procedure->execute(new GetExpressCompaniesProcedureParam());

        self::assertIsArray($result->data);
        self::assertArrayHasKey('companies', $result->data);
        self::assertArrayHasKey('total', $result->data);
        self::assertSame([], $result->data['companies']);
        self::assertSame(0, $result->data['total']);
    }

    public function testExecuteReturnsSingleCompany(): void
    {
        $this->createExpressCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/{code}');
        self::getEntityManager()->flush();

        $result = $this->procedure->execute(new GetExpressCompaniesProcedureParam());

        self::assertIsArray($result->data);
        self::assertArrayHasKey('companies', $result->data);
        self::assertArrayHasKey('total', $result->data);
        self::assertSame(1, $result->data['total']);

        $companies = $result->data['companies'];
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
        $this->createExpressCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/{code}');
        $this->createExpressCompany('YTO', '圆通速递', 'https://www.yto.net.cn/query/{code}');
        $this->createExpressCompany('STO', '申通快递', null);
        self::getEntityManager()->flush();

        $result = $this->procedure->execute(new GetExpressCompaniesProcedureParam());

        self::assertIsArray($result->data);
        self::assertArrayHasKey('companies', $result->data);
        self::assertArrayHasKey('total', $result->data);
        self::assertSame(3, $result->data['total']);

        $companies = $result->data['companies'];
        self::assertIsArray($companies);
        self::assertCount(3, $companies);

        // 将结果按 code 索引
        $companiesByCode = [];
        foreach ($companies as $company) {
            $companiesByCode[$company['code']] = $company;
        }

        // 验证顺丰
        self::assertArrayHasKey('SF', $companiesByCode);
        self::assertSame('顺丰速运', $companiesByCode['SF']['name']);
        self::assertSame('https://www.sf-express.com/track/{code}', $companiesByCode['SF']['trackingUrl']);

        // 验证圆通
        self::assertArrayHasKey('YTO', $companiesByCode);
        self::assertSame('圆通速递', $companiesByCode['YTO']['name']);
        self::assertSame('https://www.yto.net.cn/query/{code}', $companiesByCode['YTO']['trackingUrl']);

        // 验证申通（trackingUrl为null）
        self::assertArrayHasKey('STO', $companiesByCode);
        self::assertSame('申通快递', $companiesByCode['STO']['name']);
        self::assertNull($companiesByCode['STO']['trackingUrl']);
    }

    public function testExecuteHandlesNullTrackingUrl(): void
    {
        $this->createExpressCompany('ZTO', '中通快递', null);
        self::getEntityManager()->flush();

        $result = $this->procedure->execute(new GetExpressCompaniesProcedureParam());

        self::assertIsArray($result->data);
        self::assertSame(1, $result->data['total']);

        $companies = $result->data['companies'];
        self::assertIsArray($companies);
        self::assertCount(1, $companies);

        $companyData = $companies[0];
        self::assertIsArray($companyData);
        self::assertSame('ZTO', $companyData['code']);
        self::assertSame('中通快递', $companyData['name']);
        self::assertNull($companyData['trackingUrl']);
    }

    public function testExecuteOnlyReturnsActiveCompanies(): void
    {
        $this->createExpressCompany('SF', '顺丰速运', 'https://www.sf-express.com/track/{code}', true);
        $this->createExpressCompany('YTO', '圆通速递', 'https://www.yto.net.cn/query/{code}', false);
        self::getEntityManager()->flush();

        $result = $this->procedure->execute(new GetExpressCompaniesProcedureParam());

        self::assertIsArray($result->data);
        self::assertSame(1, $result->data['total']);

        $companies = $result->data['companies'];
        self::assertIsArray($companies);
        self::assertCount(1, $companies);
        self::assertSame('SF', $companies[0]['code']);
    }

    public function testExecuteReturnsCorrectArrayStructure(): void
    {
        self::getEntityManager()->flush();

        $result = $this->procedure->execute(new GetExpressCompaniesProcedureParam());

        self::assertIsArray($result->data);
        self::assertArrayHasKey('companies', $result->data);
        self::assertArrayHasKey('total', $result->data);
        self::assertCount(2, $result->data); // 只有这两个键
    }

    public function testExecuteReturnsConsistentTotalCount(): void
    {
        $this->createExpressCompany('CODE0', '名称0', 'https://example.com/0');
        $this->createExpressCompany('CODE1', '名称1', 'https://example.com/1');
        $this->createExpressCompany('CODE2', '名称2', 'https://example.com/2');
        self::getEntityManager()->flush();

        $result = $this->procedure->execute(new GetExpressCompaniesProcedureParam());

        self::assertIsArray($result->data);
        self::assertSame(3, $result->data['total']);

        $resultCompanies = $result->data['companies'];
        self::assertIsArray($resultCompanies);
        self::assertCount(3, $resultCompanies);
    }
}
