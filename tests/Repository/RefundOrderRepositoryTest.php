<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\RefundOrder;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\PaymentMethod;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Enum\RefundStatus;
use Tourze\OrderRefundBundle\Repository\RefundOrderRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(RefundOrderRepository::class)]
#[RunTestsInSeparateProcesses]
class RefundOrderRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): RefundOrder
    {
        $refundOrder = new RefundOrder();
        $refundOrder->setRefundAmount('100.00');
        $refundOrder->setStatus(RefundStatus::PENDING);
        $refundOrder->setPaymentMethod(PaymentMethod::WECHAT_PAY);
        $refundOrder->setOriginalTransactionNo('TXN' . uniqid());

        // 创建关联的 Aftersales 实体
        $aftersales = $this->createValidAftersales();
        $aftersales->setDescription('Test refund reason');

        // 持久化Aftersales
        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $refundOrder->setAftersales($aftersales);

        return $refundOrder;
    }

    protected function getRepository(): RefundOrderRepository
    {
        $repository = self::getContainer()->get(RefundOrderRepository::class);
        $this->assertInstanceOf(RefundOrderRepository::class, $repository);

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

        $criteria = ['id' => $entity->getId(), 'status' => RefundStatus::PENDING];
        $result = $repository->findOneBy($criteria);

        $this->assertInstanceOf(RefundOrder::class, $result);
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

        $criteria = ['status' => RefundStatus::PENDING];
        $orderBy = ['createTime' => 'DESC'];
        $limit = 10;
        $offset = 0;

        // 先保存一些测试数据
        $entity1 = $this->createNewEntity();
        $entity1->setStatus(RefundStatus::PENDING);
        $repository->save($entity1);

        $entity2 = $this->createNewEntity();
        $entity2->setStatus(RefundStatus::PENDING);
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

        $criteria = ['status' => RefundStatus::PENDING];

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $entity->setStatus(RefundStatus::PENDING);
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
        $this->assertInstanceOf(RefundOrder::class, $found);
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

    public function testFindPendingRefunds(): void
    {
        $repository = $this->getRepository();
        $limit = 50;

        // 先保存一些测试数据
        $entity1 = $this->createNewEntity();
        $entity1->setStatus(RefundStatus::PENDING);
        $repository->save($entity1);

        $entity2 = $this->createNewEntity();
        $entity2->setStatus(RefundStatus::PENDING);
        $repository->save($entity2);

        $entity3 = $this->createNewEntity();
        $entity3->setStatus(RefundStatus::PENDING);
        $repository->save($entity3);

        $result = $repository->findPendingRefunds($limit);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(3, count($result));
    }

    public function testFindPendingRefundsWithDefaultLimit(): void
    {
        $repository = $this->getRepository();

        // 先保存一个测试数据
        $entity = $this->createNewEntity();
        $entity->setStatus(RefundStatus::PENDING);
        $repository->save($entity);

        $result = $repository->findPendingRefunds();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindRetryableRefunds(): void
    {
        $repository = $this->getRepository();
        $limit = 20;

        // 先保存一些测试数据
        $entity1 = $this->createNewEntity();
        $entity1->setStatus(RefundStatus::FAILED);
        $repository->save($entity1);

        $entity2 = $this->createNewEntity();
        $entity2->setStatus(RefundStatus::FAILED);
        $repository->save($entity2);

        $result = $repository->findRetryableRefunds($limit);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result));
    }

    public function testFindRetryableRefundsWithDefaultLimit(): void
    {
        $repository = $this->getRepository();

        $result = $repository->findRetryableRefunds();

        $this->assertIsArray($result);
    }

    public function testGetRefundStatsByPaymentMethod(): void
    {
        $repository = $this->getRepository();

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $repository->getRefundStatsByPaymentMethod($startDate, $endDate);

        $this->assertIsArray($result);
    }

    public function testFindTimeoutRefunds(): void
    {
        $repository = $this->getRepository();
        $timeoutHours = 48;

        // 创建一个超时的实体
        $entity = $this->createNewEntity();
        $entity->setStatus(RefundStatus::PENDING);
        $entity->setCreateTime(new \DateTimeImmutable('-50 hours'));
        $repository->save($entity);

        $result = $repository->findTimeoutRefunds($timeoutHours);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindTimeoutRefundsWithDefaultTimeout(): void
    {
        $repository = $this->getRepository();

        // 创建一个超时的实体
        $entity = $this->createNewEntity();
        $entity->setStatus(RefundStatus::PENDING);
        $entity->setCreateTime(new \DateTimeImmutable('-25 hours'));
        $repository->save($entity);

        $result = $repository->findTimeoutRefunds();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByRefundNo(): void
    {
        $repository = $this->getRepository();

        // 先保存一个实体
        $entity = $this->createNewEntity();
        $repository->save($entity);

        $refundNo = $entity->getRefundNo();
        $this->assertNotNull($refundNo);
        $result = $repository->findByRefundNo($refundNo);

        $this->assertInstanceOf(RefundOrder::class, $result);
        $this->assertEquals($entity->getRefundNo(), $result->getRefundNo());
    }

    public function testFindByRefundNoReturnsNull(): void
    {
        $repository = $this->getRepository();

        $result = $repository->findByRefundNo('NON_EXISTENT_REFUND');

        $this->assertNull($result);
    }

    public function testFindByAftersalesId(): void
    {
        $repository = $this->getRepository();

        // 先保存一些测试数据
        $entity1 = $this->createNewEntity();
        $repository->save($entity1);

        $entity2 = $this->createNewEntity();
        $repository->save($entity2);

        $aftersales = $entity1->getAftersales();
        $this->assertNotNull($aftersales);
        $aftersalesId = $aftersales->getId();
        $this->assertNotNull($aftersalesId);
        $result = $repository->findByAftersalesId($aftersalesId);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testRepositoryMethodsReturningEmptyResults(): void
    {
        $repository = $this->getRepository();

        // 测试查询方法返回空数组的情况
        $result = $repository->findPendingRefunds();
        $this->assertIsArray($result);

        // 测试统计方法返回空结果
        $startDate = new \DateTimeImmutable('2020-01-01');
        $endDate = new \DateTimeImmutable('2020-01-02');

        $stats = $repository->getRefundStatsByPaymentMethod($startDate, $endDate);
        $this->assertIsArray($stats);

        // 测试查找售后申请退款订单返回空
        $aftersalesResult = $repository->findByAftersalesId('non-existent-aftersales');
        $this->assertIsArray($aftersalesResult);
        $this->assertEmpty($aftersalesResult);
    }

    private function createValidAftersales(): Aftersales
    {
        $aftersales = new Aftersales();
        $aftersales->setType(AftersalesType::REFUND_ONLY);
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
