<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Repository\AftersalesOrderRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesOrderRepository::class)]
#[RunTestsInSeparateProcesses]
class AftersalesOrderRepositoryTest extends AbstractRepositoryTestCase
{
    private AftersalesOrderRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AftersalesOrderRepository::class);
    }

    protected function getRepository(): AftersalesOrderRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): AftersalesOrder
    {
        // 创建依赖的Aftersales实体
        $aftersales = $this->createValidAftersales();

        // 持久化Aftersales
        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        // 创建AftersalesOrder
        $aftersalesOrder = new AftersalesOrder();
        $aftersalesOrder->setAftersales($aftersales);
        $aftersalesOrder->setOrderNumber('TEST-ORDER-' . time());
        $aftersalesOrder->setOrderStatus('pending');
        $aftersalesOrder->setOrderCreateTime(new \DateTimeImmutable());
        $aftersalesOrder->setUserId('USER_' . uniqid());
        $aftersalesOrder->setTotalAmount('100.0');

        return $aftersalesOrder;
    }

    public function testFindByOrderNumber(): void
    {
        $orderNumber = 'TEST-ORDER-002';

        // 创建依赖的Aftersales实体
        $aftersales = $this->createValidAftersales();

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $aftersalesOrder = new AftersalesOrder();
        $aftersalesOrder->setAftersales($aftersales);
        $aftersalesOrder->setOrderNumber($orderNumber);
        $aftersalesOrder->setOrderStatus('paid');
        $aftersalesOrder->setOrderCreateTime(new \DateTimeImmutable());
        $aftersalesOrder->setUserId('USER_' . uniqid());
        $aftersalesOrder->setTotalAmount('200.0');

        self::getEntityManager()->persist($aftersalesOrder);
        self::getEntityManager()->flush();

        $results = $this->repository->findByOrderNumber($orderNumber);

        $this->assertNotEmpty($results);
        $this->assertContainsOnlyInstancesOf(AftersalesOrder::class, $results);
        $this->assertSame($orderNumber, $results[0]->getOrderNumber());
    }

    public function testSaveMethod(): void
    {
        // 创建依赖的Aftersales实体
        $aftersales = $this->createValidAftersales();

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $aftersalesOrder = new AftersalesOrder();
        $aftersalesOrder->setAftersales($aftersales);
        $aftersalesOrder->setOrderNumber('TEST-ORDER-003');
        $aftersalesOrder->setOrderStatus('pending');
        $aftersalesOrder->setOrderCreateTime(new \DateTimeImmutable());
        $aftersalesOrder->setUserId('USER_' . uniqid());
        $aftersalesOrder->setTotalAmount('300.0');

        $this->repository->save($aftersalesOrder, true);

        $found = $this->repository->find($aftersalesOrder->getId());
        $this->assertInstanceOf(AftersalesOrder::class, $found);
        $this->assertSame('TEST-ORDER-003', $found->getOrderNumber());
    }

    public function testSaveMethodWithoutFlush(): void
    {
        // 创建依赖的Aftersales实体
        $aftersales = $this->createValidAftersales();

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $aftersalesOrder = new AftersalesOrder();
        $aftersalesOrder->setAftersales($aftersales);
        $aftersalesOrder->setOrderNumber('TEST-ORDER-004');
        $aftersalesOrder->setOrderStatus('pending');
        $aftersalesOrder->setOrderCreateTime(new \DateTimeImmutable());
        $aftersalesOrder->setUserId('USER_' . uniqid());
        $aftersalesOrder->setTotalAmount('400.0');

        $this->repository->save($aftersalesOrder, false);
        self::getEntityManager()->flush();

        $found = $this->repository->find($aftersalesOrder->getId());
        $this->assertInstanceOf(AftersalesOrder::class, $found);
        $this->assertSame('TEST-ORDER-004', $found->getOrderNumber());
    }

    public function testRemoveMethod(): void
    {
        // 创建依赖的Aftersales实体
        $aftersales = $this->createValidAftersales();

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $aftersalesOrder = new AftersalesOrder();
        $aftersalesOrder->setAftersales($aftersales);
        $aftersalesOrder->setOrderNumber('TEST-ORDER-005');
        $aftersalesOrder->setOrderStatus('paid');
        $aftersalesOrder->setOrderCreateTime(new \DateTimeImmutable());
        $aftersalesOrder->setUserId('USER_' . uniqid());
        $aftersalesOrder->setTotalAmount('500.0');

        self::getEntityManager()->persist($aftersalesOrder);
        self::getEntityManager()->flush();

        $id = $aftersalesOrder->getId();
        $this->repository->remove($aftersalesOrder, true);

        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testRemoveMethodWithoutFlush(): void
    {
        // 创建依赖的Aftersales实体
        $aftersales = $this->createValidAftersales();

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $aftersalesOrder = new AftersalesOrder();
        $aftersalesOrder->setAftersales($aftersales);
        $aftersalesOrder->setOrderNumber('TEST-ORDER-006');
        $aftersalesOrder->setOrderStatus('paid');
        $aftersalesOrder->setOrderCreateTime(new \DateTimeImmutable());
        $aftersalesOrder->setUserId('USER_' . uniqid());
        $aftersalesOrder->setTotalAmount('600.0');

        self::getEntityManager()->persist($aftersalesOrder);
        self::getEntityManager()->flush();

        $id = $aftersalesOrder->getId();
        $this->repository->remove($aftersalesOrder, false);
        self::getEntityManager()->flush();

        $found = $this->repository->find($id);
        $this->assertNull($found);
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
