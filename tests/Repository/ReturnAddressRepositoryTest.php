<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\OrderRefundBundle\Repository\ReturnAddressRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ReturnAddressRepository::class)]
#[RunTestsInSeparateProcesses]
final class ReturnAddressRepositoryTest extends AbstractRepositoryTestCase
{
    private ReturnAddressRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ReturnAddressRepository::class);
    }

    protected function createNewEntity(): ReturnAddress
    {
        $entity = new ReturnAddress();
        $entity->setName('测试地址' . uniqid());
        $entity->setProvince('测试省');
        $entity->setCity('测试市');
        $entity->setDistrict('测试区');
        $entity->setAddress('测试详细地址');
        $entity->setContactName('测试联系人');
        $entity->setContactPhone('13800138000');

        return $entity;
    }

    protected function getRepository(): ReturnAddressRepository
    {
        return $this->repository;
    }

    public function testSave(): void
    {
        $entity = new ReturnAddress();
        $entity->setName('总部仓库');
        $entity->setProvince('广东省');
        $entity->setCity('深圳市');
        $entity->setDistrict('南山区');
        $entity->setAddress('科技园南区');
        // $entity->setPostalCode('518000'); // 方法不存在，移除
        $entity->setContactName('张三');
        $entity->setContactPhone('13800138000');
        $entity->setIsActive(true);
        $entity->setIsDefault(false);
        $entity->setSortOrder(100);

        $this->repository->save($entity, true);

        $this->assertNotNull($entity->getId());

        // 验证数据已保存到数据库
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertInstanceOf(ReturnAddress::class, $savedEntity);
        $this->assertSame('总部仓库', $savedEntity->getName());
        $this->assertSame('广东省', $savedEntity->getProvince());
        $this->assertTrue($savedEntity->isActive());
    }

    public function testRemove(): void
    {
        $entity = new ReturnAddress();
        $entity->setName('测试地址');
        $entity->setProvince('测试省');
        $entity->setCity('测试市');
        $entity->setAddress('测试详细地址');
        $entity->setContactName('测试联系人');
        $entity->setContactPhone('13800138000');

        $this->repository->save($entity, true);
        $entityId = $entity->getId();

        $this->assertNotNull($entityId);

        // 删除实体
        $this->repository->remove($entity, true);

        // 验证实体已被删除
        $deletedEntity = $this->repository->find($entityId);
        $this->assertNull($deletedEntity);
    }

    public function testFindDefaultAddress(): void
    {
        $this->clearTable();

        // 创建非默认地址
        $nonDefaultAddress = new ReturnAddress();
        $nonDefaultAddress->setName('非默认地址');
        $nonDefaultAddress->setProvince('测试省');
        $nonDefaultAddress->setCity('测试市');
        $nonDefaultAddress->setAddress('测试详细地址');
        $nonDefaultAddress->setContactName('测试联系人');
        $nonDefaultAddress->setContactPhone('13800138000');
        $nonDefaultAddress->setIsActive(true);
        $nonDefaultAddress->setIsDefault(false);

        // 创建默认地址
        $defaultAddress = new ReturnAddress();
        $defaultAddress->setName('默认地址');
        $defaultAddress->setProvince('测试省');
        $defaultAddress->setCity('测试市');
        $defaultAddress->setAddress('测试详细地址');
        $defaultAddress->setContactName('测试联系人');
        $defaultAddress->setContactPhone('13800138000');
        $defaultAddress->setIsActive(true);
        $defaultAddress->setIsDefault(true);

        // 创建非活跃的默认地址（不应该被找到）
        $inactiveDefaultAddress = new ReturnAddress();
        $inactiveDefaultAddress->setName('非活跃默认地址');
        $inactiveDefaultAddress->setProvince('测试省');
        $inactiveDefaultAddress->setCity('测试市');
        $inactiveDefaultAddress->setAddress('测试详细地址');
        $inactiveDefaultAddress->setContactName('测试联系人');
        $inactiveDefaultAddress->setContactPhone('13800138000');
        $inactiveDefaultAddress->setIsActive(false);
        $inactiveDefaultAddress->setIsDefault(true);

        $this->repository->save($nonDefaultAddress, false);
        $this->repository->save($defaultAddress, false);
        $this->repository->save($inactiveDefaultAddress, true);

        // 测试查找默认地址
        $found = $this->repository->findDefaultAddress();
        $this->assertInstanceOf(ReturnAddress::class, $found);
        $this->assertSame('默认地址', $found->getName());
        $this->assertTrue($found->isActive());
        $this->assertTrue($found->isDefault());
    }

    public function testFindDefaultAddressReturnsNullWhenNoneExists(): void
    {
        $this->clearTable();

        $address = new ReturnAddress();
        $address->setName('非默认地址');
        $address->setProvince('测试省');
        $address->setCity('测试市');
        $address->setAddress('测试详细地址');
        $address->setContactName('测试联系人');
        $address->setContactPhone('13800138000');
        $address->setIsActive(true);
        $address->setIsDefault(false);

        $this->repository->save($address, true);

        $found = $this->repository->findDefaultAddress();
        $this->assertNull($found);
    }

    public function testFindActiveAddresses(): void
    {
        $this->clearTable();

        // 创建活跃地址
        $activeAddress1 = new ReturnAddress();
        $activeAddress1->setName('B仓库');
        $activeAddress1->setProvince('广东省');
        $activeAddress1->setCity('深圳市');
        $activeAddress1->setAddress('测试详细地址');
        $activeAddress1->setContactName('测试联系人');
        $activeAddress1->setContactPhone('13800138000');
        $activeAddress1->setIsActive(true);
        $activeAddress1->setSortOrder(2);

        $activeAddress2 = new ReturnAddress();
        $activeAddress2->setName('A仓库');
        $activeAddress2->setProvince('广东省');
        $activeAddress2->setCity('深圳市');
        $activeAddress2->setAddress('测试详细地址');
        $activeAddress2->setContactName('测试联系人');
        $activeAddress2->setContactPhone('13800138000');
        $activeAddress2->setIsActive(true);
        $activeAddress2->setSortOrder(1);

        // 创建非活跃地址
        $inactiveAddress = new ReturnAddress();
        $inactiveAddress->setName('已停用仓库');
        $inactiveAddress->setProvince('广东省');
        $inactiveAddress->setCity('深圳市');
        $inactiveAddress->setAddress('测试详细地址');
        $inactiveAddress->setContactName('测试联系人');
        $inactiveAddress->setContactPhone('13800138000');
        $inactiveAddress->setIsActive(false);

        $this->repository->save($activeAddress1, false);
        $this->repository->save($activeAddress2, false);
        $this->repository->save($inactiveAddress, true);

        // 测试查询结果
        $result = $this->repository->findActiveAddresses();

        $this->assertCount(2, $result);
        // 应该按sortOrder ASC, name ASC排序
        $this->assertSame('A仓库', $result[0]->getName());
        $this->assertSame('B仓库', $result[1]->getName());
    }

    public function testSetDefaultAddress(): void
    {
        $this->clearTable();

        // 创建现有的默认地址
        $currentDefault = new ReturnAddress();
        $currentDefault->setName('当前默认');
        $currentDefault->setProvince('测试省');
        $currentDefault->setCity('测试市');
        $currentDefault->setAddress('测试详细地址');
        $currentDefault->setContactName('测试联系人');
        $currentDefault->setContactPhone('13800138000');
        $currentDefault->setIsActive(true);
        $currentDefault->setIsDefault(true);

        // 创建将要设为默认的地址
        $newDefault = new ReturnAddress();
        $newDefault->setName('新默认');
        $newDefault->setProvince('测试省');
        $newDefault->setCity('测试市');
        $newDefault->setAddress('测试详细地址');
        $newDefault->setContactName('测试联系人');
        $newDefault->setContactPhone('13800138000');
        $newDefault->setIsActive(false); // 初始为非活跃状态
        $newDefault->setIsDefault(false);

        $this->repository->save($currentDefault, false);
        $this->repository->save($newDefault, true);

        // 设置新的默认地址
        $this->repository->setDefaultAddress($newDefault);

        // 清除实体管理器缓存，以便重新从数据库加载实体
        self::getEntityManager()->clear();

        // 验证旧的默认地址不再是默认
        $updatedCurrentDefault = $this->repository->find($currentDefault->getId());
        $this->assertInstanceOf(ReturnAddress::class, $updatedCurrentDefault);
        $this->assertFalse($updatedCurrentDefault->isDefault());

        // 验证新地址成为默认且被激活
        $updatedNewDefault = $this->repository->find($newDefault->getId());
        $this->assertInstanceOf(ReturnAddress::class, $updatedNewDefault);
        $this->assertTrue($updatedNewDefault->isDefault());
        $this->assertTrue($updatedNewDefault->isActive());
    }

    public function testClearAllDefaultAddresses(): void
    {
        // 创建多个默认地址（虽然不应该同时存在）
        $default1 = new ReturnAddress();
        $default1->setName('默认1');
        $default1->setProvince('测试省');
        $default1->setCity('测试市');
        $default1->setAddress('测试详细地址');
        $default1->setContactName('测试联系人');
        $default1->setContactPhone('13800138000');
        $default1->setIsDefault(true);

        $default2 = new ReturnAddress();
        $default2->setName('默认2');
        $default2->setProvince('测试省');
        $default2->setCity('测试市');
        $default2->setAddress('测试详细地址');
        $default2->setContactName('测试联系人');
        $default2->setContactPhone('13800138000');
        $default2->setIsDefault(true);

        $this->repository->save($default1, false);
        $this->repository->save($default2, true);

        // 清除所有默认标记
        $this->repository->clearAllDefaultAddresses();

        // 清除实体管理器缓存，以便重新从数据库加载实体
        self::getEntityManager()->clear();

        // 验证所有地址都不再是默认
        $updated1 = $this->repository->find($default1->getId());
        $updated2 = $this->repository->find($default2->getId());

        $this->assertInstanceOf(ReturnAddress::class, $updated1);
        $this->assertInstanceOf(ReturnAddress::class, $updated2);
        $this->assertFalse($updated1->isDefault());
        $this->assertFalse($updated2->isDefault());
    }

    /**
     * @param array<string> $expectedNames
     */
    #[DataProvider('regionProvider')]
    public function testFindByRegion(string $province, ?string $city, array $expectedNames): void
    {
        $this->clearTable();

        // 创建测试数据
        $addresses = [
            ['name' => '广州仓库', 'province' => '广东省', 'city' => '广州市', 'sortOrder' => 2],
            ['name' => '深圳仓库', 'province' => '广东省', 'city' => '深圳市', 'sortOrder' => 1],
            ['name' => '上海仓库', 'province' => '上海市', 'city' => '上海市', 'sortOrder' => 1],
            ['name' => '非活跃仓库', 'province' => '广东省', 'city' => '广州市', 'sortOrder' => 1, 'isActive' => false],
        ];

        $createdAddresses = [];
        foreach ($addresses as $addressData) {
            $address = new ReturnAddress();
            $address->setName($addressData['name']);
            $address->setProvince($addressData['province']);
            $address->setCity($addressData['city']);
            $address->setAddress('测试详细地址');
            $address->setContactName('测试联系人');
            $address->setContactPhone('13800138000');
            $address->setSortOrder($addressData['sortOrder']);
            $address->setIsActive($addressData['isActive'] ?? true);
            $this->repository->save($address, false);
            $createdAddresses[] = $address;
        }
        $lastAddress = end($createdAddresses);
        if ($lastAddress instanceof ReturnAddress) {
            $this->repository->save($lastAddress, true);
        }

        // 执行查询
        $result = $this->repository->findByRegion($province, $city);
        $actualNames = array_map(fn ($addr) => $addr->getName(), $result);

        $this->assertSame($expectedNames, $actualNames);
    }

    /**
     * @return array<string, array{string, string|null, array<string>}>
     */
    public static function regionProvider(): array
    {
        return [
            'all_guangdong' => ['广东省', null, ['深圳仓库', '广州仓库']],
            'guangzhou_only' => ['广东省', '广州市', ['广州仓库']],
            'shenzhen_only' => ['广东省', '深圳市', ['深圳仓库']],
            'shanghai' => ['上海市', '上海市', ['上海仓库']],
            'non_existent' => ['不存在省', null, []],
        ];
    }

    public function testCountActiveAddresses(): void
    {
        $this->clearTable();

        $activeAddress1 = new ReturnAddress();
        $activeAddress1->setName('活跃1');
        $activeAddress1->setProvince('测试省');
        $activeAddress1->setCity('测试市');
        $activeAddress1->setAddress('测试详细地址');
        $activeAddress1->setContactName('测试联系人');
        $activeAddress1->setContactPhone('13800138000');
        $activeAddress1->setIsActive(true);

        $activeAddress2 = new ReturnAddress();
        $activeAddress2->setName('活跃2');
        $activeAddress2->setProvince('测试省');
        $activeAddress2->setCity('测试市');
        $activeAddress2->setAddress('测试详细地址');
        $activeAddress2->setContactName('测试联系人');
        $activeAddress2->setContactPhone('13800138000');
        $activeAddress2->setIsActive(true);

        $inactiveAddress = new ReturnAddress();
        $inactiveAddress->setName('非活跃');
        $inactiveAddress->setProvince('测试省');
        $inactiveAddress->setCity('测试市');
        $inactiveAddress->setAddress('测试详细地址');
        $inactiveAddress->setContactName('测试联系人');
        $inactiveAddress->setContactPhone('13800138000');
        $inactiveAddress->setIsActive(false);

        $this->repository->save($activeAddress1, false);
        $this->repository->save($activeAddress2, false);
        $this->repository->save($inactiveAddress, true);

        $count = $this->repository->countActiveAddresses();
        $this->assertSame(2, $count);
    }

    public function testHasDefaultAddress(): void
    {
        $this->clearTable();

        // 初始时没有默认地址
        $this->assertFalse($this->repository->hasDefaultAddress());

        // 创建非默认地址
        $nonDefault = new ReturnAddress();
        $nonDefault->setName('非默认');
        $nonDefault->setProvince('测试省');
        $nonDefault->setCity('测试市');
        $nonDefault->setAddress('测试详细地址');
        $nonDefault->setContactName('测试联系人');
        $nonDefault->setContactPhone('13800138000');
        $nonDefault->setIsActive(true);
        $nonDefault->setIsDefault(false);

        $this->repository->save($nonDefault, true);
        $this->assertFalse($this->repository->hasDefaultAddress());

        // 创建默认地址
        $default = new ReturnAddress();
        $default->setName('默认');
        $default->setProvince('测试省');
        $default->setCity('测试市');
        $default->setAddress('测试详细地址');
        $default->setContactName('测试联系人');
        $default->setContactPhone('13800138000');
        $default->setIsActive(true);
        $default->setIsDefault(true);

        $this->repository->save($default, true);
        $this->assertTrue($this->repository->hasDefaultAddress());
    }

    public function testFindFirstActiveAddress(): void
    {
        $this->clearTable();

        $address1 = new ReturnAddress();
        $address1->setName('B地址');
        $address1->setProvince('测试省');
        $address1->setCity('测试市');
        $address1->setAddress('测试详细地址');
        $address1->setContactName('测试联系人');
        $address1->setContactPhone('13800138000');
        $address1->setIsActive(true);
        $address1->setSortOrder(2);

        $address2 = new ReturnAddress();
        $address2->setName('A地址');
        $address2->setProvince('测试省');
        $address2->setCity('测试市');
        $address2->setAddress('测试详细地址');
        $address2->setContactName('测试联系人');
        $address2->setContactPhone('13800138000');
        $address2->setIsActive(true);
        $address2->setSortOrder(1);

        $inactiveAddress = new ReturnAddress();
        $inactiveAddress->setName('非活跃');
        $inactiveAddress->setProvince('测试省');
        $inactiveAddress->setCity('测试市');
        $inactiveAddress->setAddress('测试详细地址');
        $inactiveAddress->setContactName('测试联系人');
        $inactiveAddress->setContactPhone('13800138000');
        $inactiveAddress->setIsActive(false);
        $inactiveAddress->setSortOrder(0);

        $this->repository->save($address1, false);
        $this->repository->save($address2, false);
        $this->repository->save($inactiveAddress, true);

        $first = $this->repository->findFirstActiveAddress();
        $this->assertInstanceOf(ReturnAddress::class, $first);
        $this->assertSame('A地址', $first->getName());
    }

    public function testFindFirstActiveAddressReturnsNullWhenNoActive(): void
    {
        $this->clearTable();

        $inactiveAddress = new ReturnAddress();
        $inactiveAddress->setName('非活跃');
        $inactiveAddress->setProvince('测试省');
        $inactiveAddress->setCity('测试市');
        $inactiveAddress->setAddress('测试详细地址');
        $inactiveAddress->setContactName('测试联系人');
        $inactiveAddress->setContactPhone('13800138000');
        $inactiveAddress->setIsActive(false);

        $this->repository->save($inactiveAddress, true);

        $first = $this->repository->findFirstActiveAddress();
        $this->assertNull($first);
    }

    /**
     * @param array<int> $ids
     */
    #[DataProvider('updateActiveStatusProvider')]
    public function testUpdateActiveStatus(array $ids, bool $isActive, int $expectedUpdated): void
    {
        $this->clearTable();

        // 创建测试数据
        $addresses = [];
        for ($i = 1; $i <= 3; ++$i) {
            $address = new ReturnAddress();
            $address->setName("地址{$i}");
            $address->setProvince('测试省');
            $address->setCity('测试市');
            $address->setAddress('测试详细地址');
            $address->setContactName('测试联系人');
            $address->setContactPhone('13800138000');
            $address->setIsActive(!$isActive); // 初始状态相反
            $this->repository->save($address, false);
            $addresses[] = $address;
        }
        $lastAddress = end($addresses);
        if ($lastAddress instanceof ReturnAddress) {
            $this->repository->save($lastAddress, true);
        }

        // 获取实际的ID
        /** @var array<int> $actualIds */
        $actualIds = array_slice(array_map(fn ($a) => (int) $a->getId(), $addresses), 0, count($ids));

        // 执行批量更新
        $updated = $this->repository->updateActiveStatus($actualIds, $isActive);
        $this->assertSame($expectedUpdated, $updated);

        // 清除实体管理器缓存，以便重新从数据库加载实体
        self::getEntityManager()->clear();

        // 验证更新结果
        for ($i = 0; $i < count($actualIds); ++$i) {
            $address = $this->repository->find($actualIds[$i]);
            $this->assertInstanceOf(ReturnAddress::class, $address);
            $this->assertSame($isActive, $address->isActive());
        }
    }

    /**
     * @return array<string, array{array<int>, bool, int}>
     */
    public static function updateActiveStatusProvider(): array
    {
        return [
            'empty_array' => [[], true, 0],
            'single_id_activate' => [[0], true, 1],
            'single_id_deactivate' => [[0], false, 1],
            'multiple_ids_activate' => [[0, 1], true, 2],
            'multiple_ids_deactivate' => [[0, 1, 2], false, 3],
        ];
    }

    public function testFindByName(): void
    {
        $address = new ReturnAddress();
        $address->setName('唯一名称');
        $address->setProvince('测试省');
        $address->setCity('测试市');
        $address->setAddress('测试详细地址');
        $address->setContactName('测试联系人');
        $address->setContactPhone('13800138000');
        $address->setIsActive(true);

        $this->repository->save($address, true);

        $found = $this->repository->findByName('唯一名称');
        $this->assertInstanceOf(ReturnAddress::class, $found);
        $this->assertSame('唯一名称', $found->getName());

        $notFound = $this->repository->findByName('不存在的名称');
        $this->assertNull($notFound);
    }

    public function testFindByNameIgnoresInactiveAddresses(): void
    {
        $inactiveAddress = new ReturnAddress();
        $inactiveAddress->setName('非活跃地址');
        $inactiveAddress->setProvince('测试省');
        $inactiveAddress->setCity('测试市');
        $inactiveAddress->setAddress('测试详细地址');
        $inactiveAddress->setContactName('测试联系人');
        $inactiveAddress->setContactPhone('13800138000');
        $inactiveAddress->setIsActive(false);

        $this->repository->save($inactiveAddress, true);

        $found = $this->repository->findByName('非活跃地址');
        $this->assertNull($found);
    }

    public function testIsNameExists(): void
    {
        $address = new ReturnAddress();
        $address->setName('已存在');
        $address->setProvince('测试省');
        $address->setCity('测试市');
        $address->setAddress('测试详细地址');
        $address->setContactName('测试联系人');
        $address->setContactPhone('13800138000');

        $this->repository->save($address, true);
        $addressId = $address->getId();

        // 测试名称存在
        $this->assertTrue($this->repository->isNameExists('已存在'));

        // 测试名称不存在
        $this->assertFalse($this->repository->isNameExists('不存在'));

        // 测试排除自身ID
        $this->assertFalse($this->repository->isNameExists('已存在', (int) $addressId));

        // 测试排除其他ID
        $this->assertTrue($this->repository->isNameExists('已存在', 999));
    }

    private function clearTable(): void
    {
        $entityManager = self::getEntityManager();
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM order_return_addresses');
    }
}
