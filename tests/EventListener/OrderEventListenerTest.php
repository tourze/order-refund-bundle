<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\OrderContracts\Event\CheckOrderRefundableEvent;
use Tourze\OrderContracts\Event\GetOrderDetailEvent;
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
    /** @var AftersalesRepository&MockObject */
    private AftersalesRepository $aftersalesRepository;

    private OrderEventListener $orderEventListener;

    protected function onSetUp(): void
    {
        $this->aftersalesRepository = $this->createMock(AftersalesRepository::class);

        // 将 mock 的 repository 注入到容器中
        self::getContainer()->set(AftersalesRepository::class, $this->aftersalesRepository);

        // 从容器中获取服务实例
        /** @var OrderEventListener $orderEventListener */
        $orderEventListener = self::getService(OrderEventListener::class);
        $this->orderEventListener = $orderEventListener;
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

        $this->aftersalesRepository->expects($this->never())
            ->method('findAftersalesStatusByReferenceNumber')
        ;

        $this->orderEventListener->onGetOrderDetail($event);
    }

    public function testOnGetOrderDetailWithValidOrderId(): void
    {
        $orderId = 'ORDER123';
        $expectedAftersalesStatus = [
            'product1' => ['status' => 'pending'],
            'product2' => ['status' => 'approved'],
        ];

        $event = $this->createMock(GetOrderDetailEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId)
        ;

        $this->aftersalesRepository->expects($this->once())
            ->method('findAftersalesStatusByReferenceNumber')
            ->with($orderId)
            ->willReturn($expectedAftersalesStatus)
        ;

        $event->expects($this->once())
            ->method('setAftersalesStatus')
            ->with($expectedAftersalesStatus)
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

        $this->aftersalesRepository->expects($this->never())
            ->method('findAftersalesStatusByReferenceNumber')
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

        $this->aftersalesRepository->expects($this->never())
            ->method('findAftersalesStatusByReferenceNumber')
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

        $event = $this->createMock(CheckOrderRefundableEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId)
        ;

        $event->expects($this->once())
            ->method('getOrderProducts')
            ->willReturn($orderProducts)
        ;

        $this->aftersalesRepository->expects($this->once())
            ->method('findAftersalesStatusByReferenceNumber')
            ->with($orderId)
            ->willReturn([])
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
        $aftersalesStatus = [
            'product1' => ['status' => 'pending'],
            // product2 没有记录，说明还可以发起售后
        ];

        $event = $this->createMock(CheckOrderRefundableEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId)
        ;

        $event->expects($this->once())
            ->method('getOrderProducts')
            ->willReturn($orderProducts)
        ;

        $this->aftersalesRepository->expects($this->once())
            ->method('findAftersalesStatusByReferenceNumber')
            ->with($orderId)
            ->willReturn($aftersalesStatus)
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
        $aftersalesStatus = [
            'product1' => ['status' => 'approved'],
            'product2' => ['status' => 'pending'],
        ];

        $event = $this->createMock(CheckOrderRefundableEvent::class);
        $event->expects($this->once())
            ->method('getOrderId')
            ->willReturn($orderId)
        ;

        $event->expects($this->once())
            ->method('getOrderProducts')
            ->willReturn($orderProducts)
        ;

        $this->aftersalesRepository->expects($this->once())
            ->method('findAftersalesStatusByReferenceNumber')
            ->with($orderId)
            ->willReturn($aftersalesStatus)
        ;

        $event->expects($this->once())
            ->method('setCanRefund')
            ->with(false)
        ;

        $this->orderEventListener->onCheckOrderRefundable($event);
    }
}
