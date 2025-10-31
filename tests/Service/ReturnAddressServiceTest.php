<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\OrderRefundBundle\Repository\ReturnAddressRepository;
use Tourze\OrderRefundBundle\Service\ReturnAddressService;

/**
 * @internal
 */
#[CoversClass(ReturnAddressService::class)]
class ReturnAddressServiceTest extends TestCase
{
    private MockObject&ReturnAddressRepository $repository;

    private ReturnAddressService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ReturnAddressRepository::class);
        $this->service = new ReturnAddressService($this->repository);
    }

    public function testGetReturnAddressForAftersalesReturnsDefault(): void
    {
        $defaultAddress = $this->createMock(ReturnAddress::class);
        $aftersales = $this->createMock(Aftersales::class);

        $this->repository->expects($this->once())
            ->method('findDefaultAddress')
            ->willReturn($defaultAddress)
        ;

        $this->repository->expects($this->never())
            ->method('findFirstActiveAddress')
        ;

        $result = $this->service->getReturnAddressForAftersales($aftersales);

        $this->assertSame($defaultAddress, $result);
    }

    public function testGetReturnAddressForAftersalesReturnsFirstActiveWhenNoDefault(): void
    {
        $firstActiveAddress = $this->createMock(ReturnAddress::class);

        $this->repository->expects($this->once())
            ->method('findDefaultAddress')
            ->willReturn(null)
        ;

        $this->repository->expects($this->once())
            ->method('findFirstActiveAddress')
            ->willReturn($firstActiveAddress)
        ;

        $result = $this->service->getReturnAddressForAftersales();

        $this->assertSame($firstActiveAddress, $result);
    }

    public function testFormatAddressForApiReturnsNullForNullInput(): void
    {
        $result = $this->service->formatAddressForApi(null);

        $this->assertNull($result);
    }

    public function testFormatAddressForApiReturnsFormattedArray(): void
    {
        $address = $this->createMock(ReturnAddress::class);
        $address->method('getId')->willReturn('123');
        $address->method('getName')->willReturn('测试地址');
        $address->method('getContactName')->willReturn('张三');
        $address->method('getContactPhone')->willReturn('13800138000');
        $address->method('getFullAddress')->willReturn('广东省深圳市南山区测试街道123号');
        $address->method('getProvince')->willReturn('广东省');
        $address->method('getCity')->willReturn('深圳市');
        $address->method('getDistrict')->willReturn('南山区');
        $address->method('getAddress')->willReturn('测试街道123号');
        $address->method('getZipCode')->willReturn('518000');
        $address->method('getBusinessHours')->willReturn('9:00-18:00');
        $address->method('getSpecialInstructions')->willReturn('请联系张三');
        $address->method('getCompanyName')->willReturn('测试公司');

        $result = $this->service->formatAddressForApi($address);

        $this->assertIsArray($result);
        $this->assertSame('123', $result['id']);
        $this->assertSame('测试地址', $result['name']);
        $this->assertSame('张三', $result['contactName']);
        $this->assertSame('13800138000', $result['contactPhone']);
        $this->assertSame('广东省深圳市南山区测试街道123号', $result['fullAddress']);
        $this->assertSame('广东省', $result['province']);
        $this->assertSame('深圳市', $result['city']);
        $this->assertSame('南山区', $result['district']);
        $this->assertSame('测试街道123号', $result['address']);
        $this->assertSame('518000', $result['zipCode']);
        $this->assertSame('9:00-18:00', $result['businessHours']);
        $this->assertSame('请联系张三', $result['specialInstructions']);
        $this->assertSame('测试公司', $result['companyName']);
    }

    public function testValidateAddressDataReturnsFalseForIncompleteAddress(): void
    {
        $address = $this->createMock(ReturnAddress::class);
        $address->method('getName')->willReturn('');
        $address->method('getContactName')->willReturn('张三');
        $address->method('getContactPhone')->willReturn('13800138000');
        $address->method('getProvince')->willReturn('广东省');
        $address->method('getCity')->willReturn('深圳市');
        $address->method('getAddress')->willReturn('测试街道123号');

        $result = $this->service->validateAddressData($address);

        $this->assertFalse($result);
    }

    public function testValidateAddressDataReturnsTrueForCompleteAddress(): void
    {
        $address = $this->createMock(ReturnAddress::class);
        $address->method('getName')->willReturn('测试地址');
        $address->method('getContactName')->willReturn('张三');
        $address->method('getContactPhone')->willReturn('13800138000');
        $address->method('getProvince')->willReturn('广东省');
        $address->method('getCity')->willReturn('深圳市');
        $address->method('getAddress')->willReturn('测试街道123号');

        $result = $this->service->validateAddressData($address);

        $this->assertTrue($result);
    }

    public function testGetAvailableAddresses(): void
    {
        $addresses = [
            $this->createMock(ReturnAddress::class),
            $this->createMock(ReturnAddress::class),
        ];

        $this->repository->expects($this->once())
            ->method('findActiveAddresses')
            ->willReturn($addresses)
        ;

        $result = $this->service->getAvailableAddresses();

        $this->assertSame($addresses, $result);
    }

    public function testGetAvailableAddressesForApi(): void
    {
        $address1 = $this->createMock(ReturnAddress::class);
        $address1->method('getId')->willReturn('1');
        $address1->method('getName')->willReturn('地址1');
        $address1->method('getContactName')->willReturn('张三');
        $address1->method('getContactPhone')->willReturn('13800138000');
        $address1->method('getFullAddress')->willReturn('地址1详情');
        $address1->method('getProvince')->willReturn('省1');
        $address1->method('getCity')->willReturn('市1');
        $address1->method('getDistrict')->willReturn('区1');
        $address1->method('getAddress')->willReturn('街道1');
        $address1->method('getZipCode')->willReturn('000001');
        $address1->method('getBusinessHours')->willReturn('9:00-18:00');
        $address1->method('getSpecialInstructions')->willReturn('说明1');
        $address1->method('getCompanyName')->willReturn('公司1');

        $address2 = $this->createMock(ReturnAddress::class);
        $address2->method('getId')->willReturn('2');
        $address2->method('getName')->willReturn('地址2');
        $address2->method('getContactName')->willReturn('李四');
        $address2->method('getContactPhone')->willReturn('13900139000');
        $address2->method('getFullAddress')->willReturn('地址2详情');
        $address2->method('getProvince')->willReturn('省2');
        $address2->method('getCity')->willReturn('市2');
        $address2->method('getDistrict')->willReturn('区2');
        $address2->method('getAddress')->willReturn('街道2');
        $address2->method('getZipCode')->willReturn('000002');
        $address2->method('getBusinessHours')->willReturn('8:00-17:00');
        $address2->method('getSpecialInstructions')->willReturn('说明2');
        $address2->method('getCompanyName')->willReturn('公司2');

        $this->repository->expects($this->once())
            ->method('findActiveAddresses')
            ->willReturn([$address1, $address2])
        ;

        $result = $this->service->getAvailableAddressesForApi();

        $this->assertCount(2, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('地址1', $result[0]['name']);
        $this->assertSame('2', $result[1]['id']);
        $this->assertSame('地址2', $result[1]['name']);
    }

    public function testSetDefaultAddress(): void
    {
        $address = $this->createMock(ReturnAddress::class);

        $this->repository->expects($this->once())
            ->method('setDefaultAddress')
            ->with($address)
        ;

        $this->service->setDefaultAddress($address);
    }

    public function testGetAddressesByRegion(): void
    {
        $addresses = [
            $this->createMock(ReturnAddress::class),
        ];

        $this->repository->expects($this->once())
            ->method('findByRegion')
            ->with('广东省', '深圳市')
            ->willReturn($addresses)
        ;

        $result = $this->service->getAddressesByRegion('广东省', '深圳市');

        $this->assertSame($addresses, $result);
    }

    public function testHasAvailableAddressReturnsTrue(): void
    {
        $this->repository->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(5)
        ;

        $result = $this->service->hasAvailableAddress();

        $this->assertTrue($result);
    }

    public function testHasAvailableAddressReturnsFalse(): void
    {
        $this->repository->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(0)
        ;

        $result = $this->service->hasAvailableAddress();

        $this->assertFalse($result);
    }

    public function testGetDefaultOrFirstAddress(): void
    {
        $address = $this->createMock(ReturnAddress::class);

        $this->repository->expects($this->once())
            ->method('findDefaultAddress')
            ->willReturn($address)
        ;

        $result = $this->service->getDefaultOrFirstAddress();

        $this->assertSame($address, $result);
    }

    public function testGetDefaultAddressForApi(): void
    {
        $address = $this->createMock(ReturnAddress::class);
        $address->method('getId')->willReturn('123');
        $address->method('getName')->willReturn('默认地址');
        $address->method('getContactName')->willReturn('张三');
        $address->method('getContactPhone')->willReturn('13800138000');
        $address->method('getFullAddress')->willReturn('完整地址');
        $address->method('getProvince')->willReturn('广东省');
        $address->method('getCity')->willReturn('深圳市');
        $address->method('getDistrict')->willReturn('南山区');
        $address->method('getAddress')->willReturn('街道地址');
        $address->method('getZipCode')->willReturn('518000');
        $address->method('getBusinessHours')->willReturn('9:00-18:00');
        $address->method('getSpecialInstructions')->willReturn('特殊说明');
        $address->method('getCompanyName')->willReturn('公司名称');

        $this->repository->expects($this->once())
            ->method('findDefaultAddress')
            ->willReturn($address)
        ;

        $result = $this->service->getDefaultAddressForApi();

        $this->assertIsArray($result);
        $this->assertSame('123', $result['id']);
        $this->assertSame('默认地址', $result['name']);
    }

    public function testCreateReturnAddressAsDefault(): void
    {
        $this->repository->expects($this->once())
            ->method('setDefaultAddress')
            ->with(static::isInstanceOf(ReturnAddress::class))
        ;

        $this->repository->expects($this->never())
            ->method('save')
        ;

        $result = $this->service->createReturnAddress(
            '测试地址',
            '张三',
            '13800138000',
            '广东省',
            '深圳市',
            '测试街道123号',
            '南山区',
            '518000',
            '9:00-18:00',
            '特殊说明',
            '测试公司',
            true, // isDefault
            true,
            1
        );

        $this->assertInstanceOf(ReturnAddress::class, $result);
    }

    public function testCreateReturnAddressAsNonDefault(): void
    {
        $this->repository->expects($this->never())
            ->method('setDefaultAddress')
        ;

        $this->repository->expects($this->once())
            ->method('save')
            ->with(static::isInstanceOf(ReturnAddress::class), true)
        ;

        $result = $this->service->createReturnAddress(
            '测试地址',
            '张三',
            '13800138000',
            '广东省',
            '深圳市',
            '测试街道123号',
            '南山区',
            '518000',
            '9:00-18:00',
            '特殊说明',
            '测试公司',
            false, // isDefault
            true,
            1
        );

        $this->assertInstanceOf(ReturnAddress::class, $result);
    }

    public function testFindByName(): void
    {
        $address = $this->createMock(ReturnAddress::class);

        $this->repository->expects($this->once())
            ->method('findByName')
            ->with('测试地址')
            ->willReturn($address)
        ;

        $result = $this->service->findByName('测试地址');

        $this->assertSame($address, $result);
    }

    public function testHasDefaultAddressReturnsTrue(): void
    {
        $address = $this->createMock(ReturnAddress::class);

        $this->repository->expects($this->once())
            ->method('findDefaultAddress')
            ->willReturn($address)
        ;

        $result = $this->service->hasDefaultAddress();

        $this->assertTrue($result);
    }

    public function testHasDefaultAddressReturnsFalse(): void
    {
        $this->repository->expects($this->once())
            ->method('findDefaultAddress')
            ->willReturn(null)
        ;

        $result = $this->service->hasDefaultAddress();

        $this->assertFalse($result);
    }

    public function testCountActiveAddresses(): void
    {
        $this->repository->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(3)
        ;

        $result = $this->service->countActiveAddresses();

        $this->assertSame(3, $result);
    }
}
