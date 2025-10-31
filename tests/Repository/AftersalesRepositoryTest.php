<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesRepository::class)]
#[RunTestsInSeparateProcesses]
final class AftersalesRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): Aftersales
    {
        $aftersales = new Aftersales();
        $aftersales->setType(AftersalesType::REFUND_ONLY);
        $aftersales->setReferenceNumber('TEST-ORDER-' . uniqid());
        $aftersales->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales->setDescription('测试售后申请');
        $aftersales->setProofImages([]);
        $aftersales->setState(AftersalesState::PENDING_APPROVAL);
        $aftersales->setStage(AftersalesStage::APPLY);
        $aftersales->setProductId('test-product-' . uniqid());
        $aftersales->setOrderProductId('test-order-product-' . uniqid());
        $aftersales->setSkuId('test-sku-' . uniqid());
        $aftersales->setProductName('测试商品');
        $aftersales->setSkuName('测试SKU');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('90.00');
        $aftersales->setRefundAmount('90.00');
        $aftersales->setOriginalRefundAmount('90.00');
        $aftersales->setActualRefundAmount('90.00');

        return $aftersales;
    }

    protected function getRepository(): AftersalesRepository
    {
        $repository = self::getContainer()->get(AftersalesRepository::class);
        $this->assertInstanceOf(AftersalesRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 子类可以在这里添加自定义初始化逻辑
        // 目前不需要额外的设置
    }

    public function testSaveMethod(): void
    {
        $aftersales = $this->createNewEntity();

        // 先保存不刷新
        $this->getRepository()->save($aftersales, false);
        $this->assertTrue(self::getEntityManager()->getUnitOfWork()->isInIdentityMap($aftersales));

        // 再刷新到数据库
        self::getEntityManager()->flush();
        $id = self::getEntityManager()->getUnitOfWork()->getSingleIdentifierValue($aftersales);
        $this->assertNotNull($id);
    }

    public function testSaveWithFlush(): void
    {
        $aftersales = $this->createNewEntity();

        // 直接保存并刷新
        $this->getRepository()->save($aftersales, true);

        $this->assertTrue(self::getEntityManager()->getUnitOfWork()->isInIdentityMap($aftersales));
        $id = self::getEntityManager()->getUnitOfWork()->getSingleIdentifierValue($aftersales);
        $this->assertNotNull($id);
    }

    public function testRemoveMethod(): void
    {
        $aftersales = $this->createNewEntity();
        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $id = self::getEntityManager()->getUnitOfWork()->getSingleIdentifierValue($aftersales);

        // 删除实体
        $this->getRepository()->remove($aftersales, true);

        // 验证已删除
        $found = $this->getRepository()->find($id);
        $this->assertNull($found);
    }

    public function testFindRefundHistoryBatchWithEmptyArray(): void
    {
        $result = $this->getRepository()->findRefundHistoryBatch([]);

        $this->assertSame([], $result);
    }

    public function testFindRefundHistoryBatchReturnsCorrectFormat(): void
    {
        // 创建测试数据
        $aftersales1 = $this->createNewEntity();
        $aftersales1->setOrderProductId('test-order-product-123');
        $aftersales1->setQuantity(2);
        $aftersales1->setApprovedAmount(19998); // 199.98 * 100
        $aftersales1->setState(AftersalesState::APPROVED);

        $aftersales2 = $this->createNewEntity();
        $aftersales2->setOrderProductId('test-order-product-123');
        $aftersales2->setQuantity(1);
        $aftersales2->setApprovedAmount(9999); // 99.99 * 100
        $aftersales2->setState(AftersalesState::COMPLETED);

        $aftersales3 = $this->createNewEntity();
        $aftersales3->setOrderProductId('test-order-product-456');
        $aftersales3->setQuantity(1);
        $aftersales3->setApprovedAmount(29999); // 299.99 * 100
        $aftersales3->setState(AftersalesState::APPROVED);

        self::getEntityManager()->persist($aftersales1);
        self::getEntityManager()->persist($aftersales2);
        self::getEntityManager()->persist($aftersales3);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findRefundHistoryBatch(['test-order-product-123', 'test-order-product-456', 'test-order-product-789']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test-order-product-123', $result);
        $this->assertArrayHasKey('test-order-product-456', $result);
        $this->assertArrayHasKey('test-order-product-789', $result);

        // 检查 123 有2条记录
        $this->assertCount(2, $result['test-order-product-123']);

        // 检查 456 有1条记录
        $this->assertCount(1, $result['test-order-product-456']);

        // 检查 789 为空数组
        $this->assertSame([], $result['test-order-product-789']);
    }

    public function testFindAftersalesStatusByReferenceNumber(): void
    {
        // 创建测试数据
        $aftersales1 = $this->createNewEntity();
        $aftersales1->setReferenceNumber('TEST-ORDER-123');
        $aftersales1->setProductId('prod-123');
        $aftersales1->setState(AftersalesState::PENDING_APPROVAL);

        $aftersales2 = $this->createNewEntity();
        $aftersales2->setReferenceNumber('TEST-ORDER-123');
        $aftersales2->setProductId('prod-123');
        $aftersales2->setState(AftersalesState::APPROVED);

        $aftersales3 = $this->createNewEntity();
        $aftersales3->setReferenceNumber('TEST-ORDER-123');
        $aftersales3->setProductId('prod-456');
        $aftersales3->setState(AftersalesState::COMPLETED);

        self::getEntityManager()->persist($aftersales1);
        self::getEntityManager()->persist($aftersales2);
        self::getEntityManager()->persist($aftersales3);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findAftersalesStatusByReferenceNumber('TEST-ORDER-123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('prod-123', $result);
        $this->assertArrayHasKey('prod-456', $result);

        // 检查 prod-123 有2个状态
        $this->assertCount(2, $result['prod-123']);
        // 枚举状态在数据库中存储为枚举对象，其value为字符串值
        $this->assertContains('pending_approval', $result['prod-123']);
        $this->assertContains('approved', $result['prod-123']);

        // 检查 prod-456 有1个状态
        $this->assertCount(1, $result['prod-456']);
        $this->assertContains('completed', $result['prod-456']);
    }

    public function testFindAftersalesStatusByReferenceNumberEmptyResult(): void
    {
        $result = $this->getRepository()->findAftersalesStatusByReferenceNumber('order-999');

        $this->assertSame([], $result);
    }

    public function testFindActiveAftersalesByOrderProductIds(): void
    {
        // 创建测试数据
        $aftersales1 = $this->createNewEntity();
        $aftersales1->setOrderProductId('order-prod-123');
        $aftersales1->setState(AftersalesState::PENDING_APPROVAL);

        $aftersales2 = $this->createNewEntity();
        $aftersales2->setOrderProductId('order-prod-123');
        $aftersales2->setState(AftersalesState::COMPLETED);

        $aftersales3 = $this->createNewEntity();
        $aftersales3->setOrderProductId('order-prod-456');
        $aftersales3->setState(AftersalesState::PENDING_REFUND);

        self::getEntityManager()->persist($aftersales1);
        self::getEntityManager()->persist($aftersales2);
        self::getEntityManager()->persist($aftersales3);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findActiveAftersalesByOrderProductIds(['order-prod-123', 'order-prod-456', 'order-prod-789']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('order-prod-123', $result);
        $this->assertArrayHasKey('order-prod-456', $result);

        // 检查 order-prod-123 有2个活跃状态
        $this->assertCount(2, $result['order-prod-123']);
        $this->assertContains('pending_approval', $result['order-prod-123']);
        $this->assertContains('completed', $result['order-prod-123']);

        // 检查 order-prod-456 有1个活跃状态
        $this->assertCount(1, $result['order-prod-456']);
        $this->assertContains('pending_refund', $result['order-prod-456']);

        // 检查 order-prod-789 没有活跃状态（不在结果中）
        $this->assertArrayNotHasKey('order-prod-789', $result);
    }

    public function testFindActiveAftersalesByOrderProductIdsWithEmptyArray(): void
    {
        $result = $this->getRepository()->findActiveAftersalesByOrderProductIds([]);

        $this->assertSame([], $result);
    }

    public function testCheckOrderAftersalesStatusWithCompletedAftersales(): void
    {
        // 创建测试数据 - 订单中的所有售后都已完成
        $referenceNumber = 'TEST-ORDER-' . uniqid();

        $aftersales1 = $this->createNewEntity();
        $aftersales1->setReferenceNumber($referenceNumber);
        $aftersales1->setOrderProductId('order-prod-123');
        $aftersales1->setQuantity(2);
        $aftersales1->setState(AftersalesState::COMPLETED);

        $aftersales2 = $this->createNewEntity();
        $aftersales2->setReferenceNumber($referenceNumber);
        $aftersales2->setOrderProductId('order-prod-456');
        $aftersales2->setQuantity(1);
        $aftersales2->setState(AftersalesState::COMPLETED);

        self::getEntityManager()->persist($aftersales1);
        self::getEntityManager()->persist($aftersales2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->checkOrderAftersalesStatus($referenceNumber);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allCompleted', $result);
        $this->assertArrayHasKey('completedCount', $result);
        $this->assertArrayHasKey('totalAftersalesCount', $result);
        $this->assertArrayHasKey('details', $result);

        // 所有售后都已完成
        $this->assertTrue($result['allCompleted']);
        $this->assertSame(2, $result['completedCount']);
        $this->assertSame(2, $result['totalAftersalesCount']);

        // 检查详情
        $this->assertArrayHasKey('order-prod-123', $result['details']);
        $this->assertArrayHasKey('order-prod-456', $result['details']);
        $this->assertTrue($result['details']['order-prod-123']['hasCompleted']);
        $this->assertTrue($result['details']['order-prod-456']['hasCompleted']);
        $this->assertSame(2, $result['details']['order-prod-123']['totalQuantity']);
        $this->assertSame(1, $result['details']['order-prod-456']['totalQuantity']);
    }

    public function testCheckOrderAftersalesStatusWithNoAftersales(): void
    {
        // 测试不存在的订单号
        $result = $this->getRepository()->checkOrderAftersalesStatus('NON-EXISTENT-ORDER');

        $this->assertFalse($result['allCompleted']);
        $this->assertSame(0, $result['completedCount']);
        $this->assertSame(0, $result['totalAftersalesCount']);
        $this->assertSame([], $result['details']);
    }
}
