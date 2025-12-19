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
 * @internal
 */
#[CoversClass(OrderEventListener::class)]
#[RunTestsInSeparateProcesses]
final class OrderEventListenerTest extends AbstractEventSubscriberTestCase
{
    private AftersalesRepository $aftersalesRepository;

    private OrderEventListener $orderEventListener;

    protected function onSetUp(): void
    {
        // 从容器中获取真实的 Repository
        /** @var AftersalesRepository $aftersalesRepository */
        $aftersalesRepository = self::getService(AftersalesRepository::class);
        $this->aftersalesRepository = $aftersalesRepository;

        // 从容器中获取服务实例
        /** @var OrderEventListener $orderEventListener */
        $orderEventListener = self::getService(OrderEventListener::class);
        $this->orderEventListener = $orderEventListener;
    }

    /**
     * 创建测试用的 Aftersales 实体
     */
    private function createAftersales(
        string $referenceNumber,
        string $productId,
        AftersalesState $state = AftersalesState::PENDING_APPROVAL
    ): Aftersales {
        $aftersales = new Aftersales();
        $aftersales->setType(AftersalesType::REFUND_ONLY);
        $aftersales->setReferenceNumber($referenceNumber);
        $aftersales->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales->setDescription('测试售后申请');
        $aftersales->setProofImages([]);
        $aftersales->setState($state);
        $aftersales->setStage(AftersalesStage::APPLY);
        $aftersales->setProductId($productId);
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

    public function testOnGetOrderDetailWithEmptyOrderId(): void
    {
        $event = $this->createMock(GetOrderDetailEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn('')
        ;

        $event->expects($this->never())
            ->method('setAftersalesStatus')
        ;

        $this->orderEventListener->onGetOrderDetail($event);
    }

    public function testOnGetOrderDetailWithValidOrderId(): void
    {
        $orderId = 'ORDER123';

        // 创建真实的测试数据
        $aftersales1 = $this->createAftersales($orderId, 'product1', AftersalesState::PENDING_APPROVAL);
        $this->persistAndFlush($aftersales1);

        $aftersales2 = $this->createAftersales($orderId, 'product2', AftersalesState::APPROVED);
        $this->persistAndFlush($aftersales2);

        // 从数据库查询验证数据
        $aftersalesStatus = $this->aftersalesRepository->findAftersalesStatusByReferenceNumber($orderId);
        $this->assertCount(2, $aftersalesStatus);
        $this->assertArrayHasKey('product1', $aftersalesStatus);
        $this->assertArrayHasKey('product2', $aftersalesStatus);

        $event = $this->createMock(GetOrderDetailEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId)
        ;

        $event->expects($this->once())
            ->method('setAftersalesStatus')
            ->with($aftersalesStatus)
        ;

        $this->orderEventListener->onGetOrderDetail($event);
    }

    public function testOnCheckOrderRefundableWithEmptyOrderId(): void
    {
        $event = $this->createMock(CheckOrderRefundableEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn('')
        ;

        $event->expects($this->once())
            ->method('setCanRefund')
            ->with(false)
        ;

        $this->orderEventListener->onCheckOrderRefundable($event);
    }

    public function testOnCheckOrderRefundableWithEmptyOrderProducts(): void
    {
        $event = $this->createMock(CheckOrderRefundableEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn('ORDER123')
        ;

        $event->expects($this->once())
            ->method('getOrderProducts')
            ->willReturn([])
        ;

        $event->expects($this->once())
            ->method('setCanRefund')
            ->with(false)
        ;

        $this->orderEventListener->onCheckOrderRefundable($event);
    }

    public function testOnCheckOrderRefundableCanRefundWhenNoAftersalesRecord(): void
    {
        $orderId = 'ORDER123';
        $orderProducts = [
            'product1' => ['name' => 'Product 1'],
            'product2' => ['name' => 'Product 2'],
        ];

        // 不创建任何售后记录，数据库为空

        $event = $this->createMock(CheckOrderRefundableEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId)
        ;

        $event->expects($this->once())
            ->method('getOrderProducts')
            ->willReturn($orderProducts)
        ;

        $event->expects($this->once())
            ->method('setCanRefund')
            ->with(true)
        ;

        $this->orderEventListener->onCheckOrderRefundable($event);
    }

    public function testOnCheckOrderRefundableCanRefundWhenPartialAftersalesRecords(): void
    {
        $orderId = 'ORDER123';
        $orderProducts = [
            'product1' => ['name' => 'Product 1'],
            'product2' => ['name' => 'Product 2'],
        ];

        // 只为 product1 创建售后记录，product2 没有记录
        $aftersales1 = $this->createAftersales($orderId, 'product1', AftersalesState::PENDING_APPROVAL);
        $this->persistAndFlush($aftersales1);

        $event = $this->createMock(CheckOrderRefundableEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId)
        ;

        $event->expects($this->once())
            ->method('getOrderProducts')
            ->willReturn($orderProducts)
        ;

        $event->expects($this->once())
            ->method('setCanRefund')
            ->with(true)
        ;

        $this->orderEventListener->onCheckOrderRefundable($event);
    }

    public function testOnCheckOrderRefundableCannotRefundWhenAllProductsHaveAftersales(): void
    {
        $orderId = 'ORDER123';
        $orderProducts = [
            'product1' => ['name' => 'Product 1'],
            'product2' => ['name' => 'Product 2'],
        ];

        // 为两个商品都创建售后记录
        $aftersales1 = $this->createAftersales($orderId, 'product1', AftersalesState::APPROVED);
        $this->persistAndFlush($aftersales1);

        $aftersales2 = $this->createAftersales($orderId, 'product2', AftersalesState::PENDING_APPROVAL);
        $this->persistAndFlush($aftersales2);

        $event = $this->createMock(CheckOrderRefundableEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId)
        ;

        $event->expects($this->once())
            ->method('getOrderProducts')
            ->willReturn($orderProducts)
        ;

        $event->expects($this->once())
            ->method('setCanRefund')
            ->with(false)
        ;

        $this->orderEventListener->onCheckOrderRefundable($event);
    }
}
