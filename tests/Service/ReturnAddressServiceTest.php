<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\OrderRefundBundle\Repository\ReturnAddressRepository;
use Tourze\OrderRefundBundle\Service\ReturnAddressService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ReturnAddressService::class)]
#[RunTestsInSeparateProcesses]
final class ReturnAddressServiceTest extends AbstractIntegrationTestCase
{
    private ReturnAddressRepository $repository;

    private ReturnAddressService $service;

    private ReturnAddress $defaultAddress;

    private ReturnAddress $activeAddress;

    private ReturnAddress $inactiveAddress;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ReturnAddressRepository::class);
        $this->service = self::getService(ReturnAddressService::class);

        // 清理数据库
        $this->clearTable();

        // 创建测试数据
        $this->defaultAddress = $this->createAddress(
            name: '默认地址',
            isDefault: true,
            isActive: true,
            sortOrder: 1
        );

        $this->activeAddress = $this->createAddress(
            name: '活跃地址',
            isDefault: false,
            isActive: true,
            sortOrder: 2
        );

        $this->inactiveAddress = $this->createAddress(
            name: '非活跃地址',
            isDefault: false,
            isActive: false,
            sortOrder: 3
        );
    }

    private function createAddress(
        string $name,
        bool $isDefault = false,
        bool $isActive = true,
        int $sortOrder = 0,
        ?string $province = '广东省',
        ?string $city = '深圳市',
        ?string $district = '南山区',
        ?string $address = '测试街道123号',
        ?string $contactName = '张三',
        ?string $contactPhone = '13800138000',
        ?string $zipCode = '518000',
        ?string $businessHours = '9:00-18:00',
        ?string $specialInstructions = '请联系张三',
        ?string $companyName = '测试公司',
    ): ReturnAddress {
        $entity = new ReturnAddress();
        $entity->setName($name);
        $entity->setProvince($province);
        $entity->setCity($city);
        $entity->setDistrict($district);
        $entity->setAddress($address);
        $entity->setContactName($contactName);
        $entity->setContactPhone($contactPhone);
        $entity->setZipCode($zipCode);
        $entity->setBusinessHours($businessHours);
        $entity->setSpecialInstructions($specialInstructions);
        $entity->setCompanyName($companyName);
        $entity->setIsDefault($isDefault);
        $entity->setIsActive($isActive);
        $entity->setSortOrder($sortOrder);

        $this->repository->save($entity, true);

        return $entity;
    }

    private function clearTable(): void
    {
        $entityManager = self::getEntityManager();
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM order_return_addresses');
    }

    public function testGetReturnAddressForAftersalesReturnsDefault(): void
    {
        $result = $this->service->getReturnAddressForAftersales();

        $this->assertSame($this->defaultAddress->getId(), $result->getId());
        $this->assertSame('默认地址', $result->getName());
    }

    public function testGetReturnAddressForAftersalesReturnsFirstActiveWhenNoDefault(): void
    {
        // 清理并创建没有默认地址的数据
        $this->clearTable();
        $firstActiveAddress = $this->createAddress(
            name: '第一个活跃地址',
            isDefault: false,
            isActive: true,
            sortOrder: 1
        );

        $result = $this->service->getReturnAddressForAftersales();

        $this->assertSame($firstActiveAddress->getId(), $result->getId());
        $this->assertSame('第一个活跃地址', $result->getName());
    }

    public function testFormatAddressForApiReturnsNullForNullInput(): void
    {
        $result = $this->service->formatAddressForApi(null);

        $this->assertNull($result);
    }

    public function testFormatAddressForApiReturnsFormattedArray(): void
    {
        $result = $this->service->formatAddressForApi($this->defaultAddress);

        $this->assertIsArray($result);
        $this->assertSame($this->defaultAddress->getId(), $result['id']);
        $this->assertSame('默认地址', $result['name']);
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
        $address = new ReturnAddress();
        $address->setName(''); // 空名称
        $address->setContactName('张三');
        $address->setContactPhone('13800138000');
        $address->setProvince('广东省');
        $address->setCity('深圳市');
        $address->setAddress('测试街道123号');

        $result = $this->service->validateAddressData($address);

        $this->assertFalse($result);
    }

    public function testValidateAddressDataReturnsTrueForCompleteAddress(): void
    {
        $result = $this->service->validateAddressData($this->defaultAddress);

        $this->assertTrue($result);
    }

    public function testGetAvailableAddresses(): void
    {
        $result = $this->service->getAvailableAddresses();

        // 应该只返回活跃的地址，不包括非活跃地址
        $this->assertCount(2, $result);
        $names = array_map(fn ($addr) => $addr->getName(), $result);
        $this->assertContains('默认地址', $names);
        $this->assertContains('活跃地址', $names);
        $this->assertNotContains('非活跃地址', $names);
    }

    public function testGetAvailableAddressesForApi(): void
    {
        $result = $this->service->getAvailableAddressesForApi();

        $this->assertCount(2, $result);
        // 验证第一个地址（按sortOrder排序）
        $this->assertSame($this->defaultAddress->getId(), $result[0]['id']);
        $this->assertSame('默认地址', $result[0]['name']);
        // 验证第二个地址
        $this->assertSame($this->activeAddress->getId(), $result[1]['id']);
        $this->assertSame('活跃地址', $result[1]['name']);
    }

    public function testSetDefaultAddress(): void
    {
        // 设置活跃地址为默认
        $this->service->setDefaultAddress($this->activeAddress);

        // 清除EntityManager缓存以获取最新数据
        self::getEntityManager()->clear();

        // 验证设置成功
        $result = $this->repository->findDefaultAddress();
        $this->assertSame($this->activeAddress->getId(), $result->getId());

        // 验证旧的默认地址不再是默认
        $oldDefault = $this->repository->find($this->defaultAddress->getId());
        $this->assertFalse($oldDefault->isDefault());
    }

    public function testGetAddressesByRegion(): void
    {
        $result = $this->service->getAddressesByRegion('广东省', '深圳市');

        // 应该返回广东省深圳市的活跃地址
        $this->assertCount(2, $result);
    }

    public function testHasAvailableAddressReturnsTrue(): void
    {
        $result = $this->service->hasAvailableAddress();

        $this->assertTrue($result);
    }

    public function testHasAvailableAddressReturnsFalse(): void
    {
        // 清空所有地址
        $this->clearTable();

        $result = $this->service->hasAvailableAddress();

        $this->assertFalse($result);
    }

    public function testGetDefaultOrFirstAddress(): void
    {
        $result = $this->service->getDefaultOrFirstAddress();

        $this->assertSame($this->defaultAddress->getId(), $result->getId());
    }

    public function testGetDefaultAddressForApi(): void
    {
        $result = $this->service->getDefaultAddressForApi();

        $this->assertIsArray($result);
        $this->assertSame($this->defaultAddress->getId(), $result['id']);
        $this->assertSame('默认地址', $result['name']);
    }

    public function testCreateReturnAddressAsDefault(): void
    {
        $result = $this->service->createReturnAddress(
            '新默认地址',
            '李四',
            '13900139000',
            '广东省',
            '广州市',
            '天河路100号',
            '天河区',
            '510000',
            '8:00-17:00',
            '请提前联系',
            '新公司',
            true, // isDefault
            true,
            10
        );

        $this->assertInstanceOf(ReturnAddress::class, $result);
        $this->assertTrue($result->isDefault());
        $this->assertNotNull($result->getId());

        // 清除EntityManager缓存以获取最新数据
        self::getEntityManager()->clear();

        // 验证旧的默认地址不再是默认
        $oldDefault = $this->repository->find($this->defaultAddress->getId());
        $this->assertFalse($oldDefault->isDefault());
    }

    public function testCreateReturnAddressAsNonDefault(): void
    {
        $result = $this->service->createReturnAddress(
            '新普通地址',
            '王五',
            '13700137000',
            '北京市',
            '北京市',
            '朝阳路200号',
            '朝阳区',
            '100000',
            '9:00-18:00',
            '工作日配送',
            '北京公司',
            false, // isDefault
            true,
            20
        );

        $this->assertInstanceOf(ReturnAddress::class, $result);
        $this->assertFalse($result->isDefault());
        $this->assertNotNull($result->getId());

        // 验证原默认地址仍然是默认
        $oldDefault = $this->repository->find($this->defaultAddress->getId());
        $this->assertTrue($oldDefault->isDefault());
    }

    public function testFindByName(): void
    {
        $result = $this->service->findByName('默认地址');

        $this->assertSame($this->defaultAddress->getId(), $result->getId());
    }

    public function testHasDefaultAddressReturnsTrue(): void
    {
        $result = $this->service->hasDefaultAddress();

        $this->assertTrue($result);
    }

    public function testHasDefaultAddressReturnsFalse(): void
    {
        // 清空所有地址
        $this->clearTable();

        $result = $this->service->hasDefaultAddress();

        $this->assertFalse($result);
    }

    public function testCountActiveAddresses(): void
    {
        $result = $this->service->countActiveAddresses();

        // setUp 中创建了2个活跃地址
        $this->assertSame(2, $result);
    }
}
