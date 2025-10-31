<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ReturnAddress::class)]
class ReturnAddressTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
    }

    protected function createEntity(): ReturnAddress
    {
        return new ReturnAddress();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'name' => ['name', '公司退货地址'],
            'contactName' => ['contactName', '张三'],
            'contactPhone' => ['contactPhone', '13800138000'],
            'province' => ['province', '广东省'],
            'city' => ['city', '深圳市'],
            'district' => ['district', '南山区'],
            'address' => ['address', '科技园南区深南大道10000号'],
            'zipCode' => ['zipCode', '518000'],
            'sortOrder' => ['sortOrder', 100],
            'businessHours' => ['businessHours', '周一至周五 9:00-18:00'],
            'specialInstructions' => ['specialInstructions', '请在工作时间送达'],
            'companyName' => ['companyName', '测试公司'],
        ];
    }

    public function testReturnAddressCreation(): void
    {
        $returnAddress = new ReturnAddress();

        self::assertNull($returnAddress->getId());
        self::assertFalse($returnAddress->isDefault());
        self::assertTrue($returnAddress->isActive());
        self::assertEquals(0, $returnAddress->getSortOrder());
    }

    public function testSettersAndGetters(): void
    {
        $returnAddress = new ReturnAddress();

        $returnAddress->setName('测试地址');
        $returnAddress->setContactName('李四');
        $returnAddress->setContactPhone('13900139000');
        $returnAddress->setProvince('北京市');
        $returnAddress->setCity('北京市');
        $returnAddress->setDistrict('朝阳区');
        $returnAddress->setAddress('建国门外大街1号');
        $returnAddress->setZipCode('100000');
        $returnAddress->setIsDefault(true);
        $returnAddress->setIsActive(false);
        $returnAddress->setSortOrder(200);
        $returnAddress->setBusinessHours('24小时');
        $returnAddress->setSpecialInstructions('需要预约');
        $returnAddress->setCompanyName('北京测试公司');

        self::assertEquals('测试地址', $returnAddress->getName());
        self::assertEquals('李四', $returnAddress->getContactName());
        self::assertEquals('13900139000', $returnAddress->getContactPhone());
        self::assertEquals('北京市', $returnAddress->getProvince());
        self::assertEquals('北京市', $returnAddress->getCity());
        self::assertEquals('朝阳区', $returnAddress->getDistrict());
        self::assertEquals('建国门外大街1号', $returnAddress->getAddress());
        self::assertEquals('100000', $returnAddress->getZipCode());
        self::assertTrue($returnAddress->isDefault());
        self::assertFalse($returnAddress->isActive());
        self::assertEquals(200, $returnAddress->getSortOrder());
        self::assertEquals('24小时', $returnAddress->getBusinessHours());
        self::assertEquals('需要预约', $returnAddress->getSpecialInstructions());
        self::assertEquals('北京测试公司', $returnAddress->getCompanyName());
    }

    public function testGetFullAddress(): void
    {
        $returnAddress = new ReturnAddress();
        $returnAddress->setProvince('广东省');
        $returnAddress->setCity('广州市');
        $returnAddress->setDistrict('天河区');
        $returnAddress->setAddress('珠江新城花城大道123号');

        $expectedFullAddress = '广东省广州市天河区珠江新城花城大道123号';
        self::assertEquals($expectedFullAddress, $returnAddress->getFullAddress());
    }

    public function testGetFullAddressWithNullValues(): void
    {
        $returnAddress = new ReturnAddress();
        $returnAddress->setProvince('上海市');
        $returnAddress->setCity('上海市');
        // district 为 null
        $returnAddress->setAddress('外滩18号');

        $expectedFullAddress = '上海市上海市外滩18号';
        self::assertEquals($expectedFullAddress, $returnAddress->getFullAddress());
    }

    public function testGetFullAddressWithEmptyValues(): void
    {
        $returnAddress = new ReturnAddress();
        $returnAddress->setProvince('江苏省');
        $returnAddress->setCity('南京市');
        $returnAddress->setDistrict(''); // 空字符串
        $returnAddress->setAddress('中山路100号');

        $expectedFullAddress = '江苏省南京市中山路100号';
        self::assertEquals($expectedFullAddress, $returnAddress->getFullAddress());
    }

    public function testToStringWithName(): void
    {
        $returnAddress = new ReturnAddress();
        $returnAddress->setName('总部退货地址');
        $returnAddress->setProvince('浙江省');
        $returnAddress->setCity('杭州市');
        $returnAddress->setAddress('西湖区文二路391号');

        self::assertEquals('总部退货地址', (string) $returnAddress);
    }

    public function testToStringWithoutName(): void
    {
        $returnAddress = new ReturnAddress();
        $returnAddress->setProvince('四川省');
        $returnAddress->setCity('成都市');
        $returnAddress->setDistrict('锦江区');
        $returnAddress->setAddress('天府广场88号');

        $expectedString = '四川省成都市锦江区天府广场88号';
        self::assertEquals($expectedString, (string) $returnAddress);
    }

    public function testNullableFields(): void
    {
        $returnAddress = new ReturnAddress();

        // 测试可为空的字段
        self::assertNull($returnAddress->getDistrict());
        self::assertNull($returnAddress->getZipCode());
        self::assertNull($returnAddress->getBusinessHours());
        self::assertNull($returnAddress->getSpecialInstructions());
        self::assertNull($returnAddress->getCompanyName());

        // 设置为 null
        $returnAddress->setDistrict(null);
        $returnAddress->setZipCode(null);
        $returnAddress->setBusinessHours(null);
        $returnAddress->setSpecialInstructions(null);
        $returnAddress->setCompanyName(null);

        self::assertNull($returnAddress->getDistrict());
        self::assertNull($returnAddress->getZipCode());
        self::assertNull($returnAddress->getBusinessHours());
        self::assertNull($returnAddress->getSpecialInstructions());
        self::assertNull($returnAddress->getCompanyName());
    }

    public function testBooleanDefaults(): void
    {
        $returnAddress = new ReturnAddress();

        // 测试默认值
        self::assertFalse($returnAddress->isDefault());
        self::assertTrue($returnAddress->isActive());
        self::assertEquals(0, $returnAddress->getSortOrder());
    }
}
