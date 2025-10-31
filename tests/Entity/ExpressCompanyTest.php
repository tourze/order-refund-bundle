<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ExpressCompany::class)]
class ExpressCompanyTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
    }

    protected function createEntity(): ExpressCompany
    {
        return new ExpressCompany();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'code' => ['code', 'SF'],
            'name' => ['name', '顺丰速运'],
            'trackingUrlTemplate' => ['trackingUrlTemplate', 'https://www.sf-express.com/chn/sc/dynamic_function/waybill/#search/bill-number/{code}'],
            'sortOrder' => ['sortOrder', 100],
            'description' => ['description', '全国性快递公司'],
        ];
    }

    public function testExpressCompanyCreation(): void
    {
        $company = new ExpressCompany();

        self::assertNull($company->getId());
        self::assertNull($company->getCode());
        self::assertNull($company->getName());
        self::assertNull($company->getTrackingUrlTemplate());
        self::assertTrue($company->isActive()); // 默认值为true
        self::assertSame(0, $company->getSortOrder()); // 默认值为0
        self::assertNull($company->getDescription());
    }

    public function testBasicSettersAndGetters(): void
    {
        $company = new ExpressCompany();

        $company->setCode('YTO');
        $company->setName('圆通速递');
        $company->setTrackingUrlTemplate('https://www.yto.net.cn/query/{code}');
        $company->setIsActive(false);
        $company->setSortOrder(200);
        $company->setDescription('民营快递公司');

        self::assertSame('YTO', $company->getCode());
        self::assertSame('圆通速递', $company->getName());
        self::assertSame('https://www.yto.net.cn/query/{code}', $company->getTrackingUrlTemplate());
        self::assertFalse($company->isActive());
        self::assertSame(200, $company->getSortOrder());
        self::assertSame('民营快递公司', $company->getDescription());
    }

    public function testToStringWithName(): void
    {
        $company = new ExpressCompany();
        $company->setName('申通快递');

        $result = (string) $company;

        self::assertSame('申通快递', $result);
    }

    public function testToStringWithCodeWhenNameIsNull(): void
    {
        $company = new ExpressCompany();
        $company->setCode('STO');

        $result = (string) $company;

        self::assertSame('STO', $result);
    }

    public function testToStringWithIdWhenNameAndCodeAreNull(): void
    {
        $company = new ExpressCompany();

        // 由于我们无法直接设置ID，这个测试验证当name和code都为null时的行为
        $result = (string) $company;

        // 当name和code都为null时，应该返回ID的字符串形式
        // 但在测试环境中ID为null，所以会返回空字符串
        self::assertSame('', $result);
    }

    public function testDefaultValues(): void
    {
        $company = new ExpressCompany();

        // 验证默认值
        self::assertTrue($company->isActive());
        self::assertSame(0, $company->getSortOrder());
    }

    public function testActiveStatusManagement(): void
    {
        $company = new ExpressCompany();

        // 默认应该是激活状态
        self::assertTrue($company->isActive());

        // 设置为非激活状态
        $company->setIsActive(false);
        self::assertFalse($company->isActive());

        // 重新激活
        $company->setIsActive(true);
        self::assertTrue($company->isActive());
    }

    public function testSortOrderManagement(): void
    {
        $company = new ExpressCompany();

        // 默认排序应该是0
        self::assertSame(0, $company->getSortOrder());

        // 设置排序值
        $company->setSortOrder(500);
        self::assertSame(500, $company->getSortOrder());

        // 设置负数排序值
        $company->setSortOrder(-1);
        self::assertSame(-1, $company->getSortOrder());
    }

    public function testTrackingUrlTemplate(): void
    {
        $company = new ExpressCompany();

        // 初始应该为null
        self::assertNull($company->getTrackingUrlTemplate());

        // 设置跟踪URL模板
        $template = 'https://example.com/track?number={code}';
        $company->setTrackingUrlTemplate($template);
        self::assertSame($template, $company->getTrackingUrlTemplate());

        // 设置为null
        $company->setTrackingUrlTemplate(null);
        self::assertNull($company->getTrackingUrlTemplate());
    }

    public function testDescriptionManagement(): void
    {
        $company = new ExpressCompany();

        // 初始应该为null
        self::assertNull($company->getDescription());

        // 设置描述
        $description = '提供全国快递服务';
        $company->setDescription($description);
        self::assertSame($description, $company->getDescription());

        // 设置为null
        $company->setDescription(null);
        self::assertNull($company->getDescription());
    }
}
