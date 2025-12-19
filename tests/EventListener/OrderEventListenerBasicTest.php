<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderContracts\Event\CheckOrderRefundableEvent;
use Tourze\OrderContracts\Event\GetOrderDetailEvent;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\EventListener\OrderEventListener;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * OrderEventListener 基础测试
 *
 * @internal
 */
#[CoversClass(OrderEventListener::class)]
#[RunTestsInSeparateProcesses]
final class OrderEventListenerBasicTest extends AbstractEventSubscriberTestCase
{
    private AftersalesRepository $aftersalesRepository;

    protected function onSetUp(): void
    {
        // 基础集成测试设置
        $repository = self::getContainer()->get(AftersalesRepository::class);
        $this->assertInstanceOf(AftersalesRepository::class, $repository);
        $this->aftersalesRepository = $repository;
    }

    public function testOrderEventListenerCanBeInstantiated(): void
    {
        /** @var OrderEventListener $eventListener */
        $eventListener = self::getService(OrderEventListener::class);

        $this->assertInstanceOf(OrderEventListener::class, $eventListener);
    }

    public function testOnCheckOrderRefundableWithNoAftersalesData(): void
    {
        /** @var OrderEventListener $eventListener */
        $eventListener = self::getService(OrderEventListener::class);

        // 使用一个不存在售后数据的订单ID
        $orderId = 'test-order-no-aftersales-' . uniqid();

        $event = new CheckOrderRefundableEvent();
        $event->setOrderId($orderId);
        $event->setOrderProducts(['product1' => ['id' => 'product1', 'quantity' => 1]]);

        $eventListener->onCheckOrderRefundable($event);

        // 没有售后记录时，应该可以退款
        $this->assertTrue($event->getCanRefund());
    }

    public function testOnCheckOrderRefundableWithExistingAftersales(): void
    {
        /** @var OrderEventListener $eventListener */
        $eventListener = self::getService(OrderEventListener::class);

        // 创建真实的售后数据
        $orderId = 'test-order-with-aftersales-' . uniqid();
        $productId = 'product-123';

        $aftersales = new Aftersales();
        $aftersales->setType(AftersalesType::REFUND_ONLY);
        $aftersales->setReferenceNumber($orderId);
        $aftersales->setProductId($productId);
        $aftersales->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales->setDescription('测试售后');
        $aftersales->setProofImages([]);
        $aftersales->setState(AftersalesState::PENDING_APPROVAL);
        $aftersales->setStage(AftersalesStage::APPLY);
        $aftersales->setOrderProductId('order-product-' . uniqid());
        $aftersales->setSkuId('sku-' . uniqid());
        $aftersales->setProductName('测试商品');
        $aftersales->setSkuName('测试SKU');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('90.00');
        $aftersales->setRefundAmount('90.00');
        $aftersales->setOriginalRefundAmount('90.00');
        $aftersales->setActualRefundAmount('90.00');

        $this->aftersalesRepository->save($aftersales, true);

        $event = new CheckOrderRefundableEvent();
        $event->setOrderId($orderId);
        $event->setOrderProducts([$productId => ['id' => $productId, 'quantity' => 1]]);

        $eventListener->onCheckOrderRefundable($event);

        // 所有产品都有售后记录，不能再退款
        $this->assertFalse($event->getCanRefund());
    }

    public function testOnGetOrderDetailWithNoAftersales(): void
    {
        /** @var OrderEventListener $eventListener */
        $eventListener = self::getService(OrderEventListener::class);

        // 使用一个不存在售后数据的订单ID
        $orderId = 'test-order-detail-no-aftersales-' . uniqid();

        $event = new GetOrderDetailEvent();
        $event->setOrderId($orderId);

        $eventListener->onGetOrderDetail($event);

        // 验证事件监听器能正常处理事件，返回空数组
        $this->assertIsArray($event->getAftersalesStatus());
        $this->assertEmpty($event->getAftersalesStatus());
    }

    public function testOnGetOrderDetailWithAftersales(): void
    {
        /** @var OrderEventListener $eventListener */
        $eventListener = self::getService(OrderEventListener::class);

        // 创建真实的售后数据
        $orderId = 'test-order-detail-with-aftersales-' . uniqid();
        $productId1 = 'product-' . uniqid();
        $productId2 = 'product-' . uniqid();

        // 第一个售后记录
        $aftersales1 = new Aftersales();
        $aftersales1->setType(AftersalesType::REFUND_ONLY);
        $aftersales1->setReferenceNumber($orderId);
        $aftersales1->setProductId($productId1);
        $aftersales1->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales1->setDescription('测试售后1');
        $aftersales1->setProofImages([]);
        $aftersales1->setState(AftersalesState::PENDING_APPROVAL);
        $aftersales1->setStage(AftersalesStage::APPLY);
        $aftersales1->setOrderProductId('order-product-' . uniqid());
        $aftersales1->setSkuId('sku-' . uniqid());
        $aftersales1->setProductName('测试商品1');
        $aftersales1->setSkuName('测试SKU1');
        $aftersales1->setQuantity(1);
        $aftersales1->setOriginalPrice('100.00');
        $aftersales1->setPaidPrice('90.00');
        $aftersales1->setRefundAmount('90.00');
        $aftersales1->setOriginalRefundAmount('90.00');
        $aftersales1->setActualRefundAmount('90.00');

        // 第二个售后记录
        $aftersales2 = new Aftersales();
        $aftersales2->setType(AftersalesType::RETURN_REFUND);
        $aftersales2->setReferenceNumber($orderId);
        $aftersales2->setProductId($productId2);
        $aftersales2->setReason(RefundReason::DONT_WANT);
        $aftersales2->setDescription('测试售后2');
        $aftersales2->setProofImages([]);
        $aftersales2->setState(AftersalesState::APPROVED);
        $aftersales2->setStage(AftersalesStage::APPLY);
        $aftersales2->setOrderProductId('order-product-' . uniqid());
        $aftersales2->setSkuId('sku-' . uniqid());
        $aftersales2->setProductName('测试商品2');
        $aftersales2->setSkuName('测试SKU2');
        $aftersales2->setQuantity(2);
        $aftersales2->setOriginalPrice('200.00');
        $aftersales2->setPaidPrice('180.00');
        $aftersales2->setRefundAmount('180.00');
        $aftersales2->setOriginalRefundAmount('180.00');
        $aftersales2->setActualRefundAmount('180.00');

        $this->aftersalesRepository->save($aftersales1, false);
        $this->aftersalesRepository->save($aftersales2, true);

        $event = new GetOrderDetailEvent();
        $event->setOrderId($orderId);

        $eventListener->onGetOrderDetail($event);

        // 验证事件监听器能正常处理事件，返回售后状态
        $aftersalesStatus = $event->getAftersalesStatus();
        $this->assertIsArray($aftersalesStatus);
        $this->assertNotEmpty($aftersalesStatus);
        $this->assertArrayHasKey($productId1, $aftersalesStatus);
        $this->assertArrayHasKey($productId2, $aftersalesStatus);
        $this->assertContains('pending_approval', $aftersalesStatus[$productId1]);
        $this->assertContains('approved', $aftersalesStatus[$productId2]);
    }
}
