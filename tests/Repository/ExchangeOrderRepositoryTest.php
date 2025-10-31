<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ExchangeOrder;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\ExchangeStatus;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Repository\ExchangeOrderRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ExchangeOrderRepository::class)]
#[RunTestsInSeparateProcesses]
class ExchangeOrderRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): ExchangeOrder
    {
        // 创建依赖的Aftersales实体
        $aftersales = $this->createValidAftersales();
        $aftersales->setType(AftersalesType::EXCHANGE);

        // 持久化Aftersales
        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        // 创建ExchangeOrder
        $exchangeOrder = new ExchangeOrder();
        $exchangeOrder->setAftersales($aftersales);
        $exchangeOrder->setExchangeNo('EX' . uniqid());
        $exchangeOrder->setStatus(ExchangeStatus::PENDING);
        $exchangeOrder->setExchangeReason('Test exchange reason');
        $exchangeOrder->setOriginalItems([['product_id' => '123', 'quantity' => 1]]);
        $exchangeOrder->setExchangeItems([['product_id' => '456', 'quantity' => 1]]);

        return $exchangeOrder;
    }

    protected function getRepository(): ExchangeOrderRepository
    {
        $repository = self::getContainer()->get(ExchangeOrderRepository::class);
        $this->assertInstanceOf(ExchangeOrderRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 在这里可以设置测试前的准备工作
    }

    public function testFindMethodReturnsNull(): void
    {
        $repository = $this->getRepository();

        $result = $repository->find('non-existent-id');

        $this->assertNull($result);
    }

    public function testFindOneByMethod(): void
    {
        $repository = $this->getRepository();

        // 先保存一个实体
        $entity = $this->createNewEntity();
        $repository->save($entity);

        $criteria = ['id' => $entity->getId(), 'status' => ExchangeStatus::PENDING];
        $result = $repository->findOneBy($criteria);

        $this->assertInstanceOf(ExchangeOrder::class, $result);
        $this->assertEquals($entity->getId(), $result->getId());
    }

    public function testFindOneByMethodReturnsNull(): void
    {
        $repository = $this->getRepository();

        $criteria = ['id' => 'non-existent'];
        $result = $repository->findOneBy($criteria);

        $this->assertNull($result);
    }

    public function testFindByMethod(): void
    {
        $repository = $this->getRepository();

        $criteria = ['status' => ExchangeStatus::PENDING];
        $orderBy = ['createTime' => 'DESC'];
        $limit = 10;
        $offset = 0;

        // 先保存一些测试数据
        $entity1 = $this->createNewEntity();
        $entity1->setStatus(ExchangeStatus::PENDING);
        $repository->save($entity1);

        $entity2 = $this->createNewEntity();
        $entity2->setStatus(ExchangeStatus::PENDING);
        $repository->save($entity2);

        $result = $repository->findBy($criteria, $orderBy, $limit, $offset);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
    }

    public function testFindByMethodReturnsEmptyArray(): void
    {
        $repository = $this->getRepository();

        $criteria = ['status' => 'non_existent_status'];
        $result = $repository->findBy($criteria);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindAllMethod(): void
    {
        $repository = $this->getRepository();

        // 先保存一些测试数据
        $entity1 = $this->createNewEntity();
        $repository->save($entity1);

        $entity2 = $this->createNewEntity();
        $repository->save($entity2);

        $entity3 = $this->createNewEntity();
        $repository->save($entity3);

        $result = $repository->findAll();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testCountMethod(): void
    {
        $repository = $this->getRepository();

        $criteria = ['status' => ExchangeStatus::PENDING];

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $entity->setStatus(ExchangeStatus::PENDING);
        $repository->save($entity);

        $result = $repository->count($criteria);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(1, $result);
    }

    public function testCountMethodWithNoCriteria(): void
    {
        $repository = $this->getRepository();

        $result = $repository->count([]);

        $this->assertIsInt($result);
    }

    public function testRepositoryBehaviorWithMultipleOperations(): void
    {
        $repository = $this->getRepository();

        // 保存第一个实体
        $entity1 = $this->createNewEntity();
        $repository->save($entity1, false);

        // 保存第二个实体
        $entity2 = $this->createNewEntity();
        $repository->save($entity2, false);

        // 查找实体
        $found = $repository->find($entity1->getId());
        $this->assertInstanceOf(ExchangeOrder::class, $found);
        $this->assertEquals($entity1->getId(), $found->getId());

        // 删除实体
        $repository->remove($entity1, true);
    }

    public function testRemoveMethod(): void
    {
        $repository = $this->getRepository();

        // 先保存一个实体
        $entity = $this->createNewEntity();
        $repository->save($entity, false);

        // 然后删除它
        $repository->remove($entity, true);

        // 验证实体已被删除
        $this->assertNull($repository->find($entity->getId()));
    }

    public function testFindByAftersalesIdReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findByAftersalesId('non-existent-id');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByExchangeNoReturnsNull(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回null
        $result = $repository->findByExchangeNo('non-existent-no');
        $this->assertNull($result);
    }

    public function testFindPendingMerchantActionReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findPendingMerchantAction();
        $this->assertIsArray($result);
    }

    public function testFindTimeoutPendingReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findTimeoutPending();
        $this->assertIsArray($result);
    }

    public function testFindByUserIdReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findByUserId('test-user-id');
        $this->assertIsArray($result);
    }

    public function testFindPendingUserActionReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findPendingUserAction();
        $this->assertIsArray($result);
    }

    public function testFindProcessingExchangesReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findProcessingExchanges();
        $this->assertIsArray($result);
    }

    public function testFindWithPriceDifferenceReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findWithPriceDifference();
        $this->assertIsArray($result);
    }

    private function createValidAftersales(): Aftersales
    {
        $aftersales = new Aftersales();
        $aftersales->setType(AftersalesType::EXCHANGE);
        $aftersales->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales->setReferenceNumber('REF-' . uniqid());
        $aftersales->setOrderProductId('order_product_' . uniqid());
        $aftersales->setProductId('product_' . uniqid());
        $aftersales->setSkuId('sku_' . uniqid());
        $aftersales->setProductName('Test Product Name');
        $aftersales->setSkuName('Test SKU Name');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('90.00');
        $aftersales->setRefundAmount('90.00');
        $aftersales->setOriginalRefundAmount('90.00');
        $aftersales->setActualRefundAmount('90.00');

        return $aftersales;
    }
}
