<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderContracts\Event\CheckOrderRefundableEvent;
use Tourze\OrderContracts\Event\GetOrderDetailEvent;
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
    protected function onSetUp(): void
    {
        // 基础集成测试设置
    }

    public function testOrderEventListenerCanBeInstantiated(): void
    {
        /** @var OrderEventListener $eventListener */
        $eventListener = self::getService(OrderEventListener::class);

        $this->assertInstanceOf(OrderEventListener::class, $eventListener);
    }

    public function testAftersalesRepositoryMethodExists(): void
    {
        $aftersalesRepository = $this->createMock(AftersalesRepository::class);
        $aftersalesRepository->expects($this->once())
            ->method('findAftersalesStatusByReferenceNumber')
            ->with('test-order-id')
            ->willReturn([])
        ;

        // 模拟调用
        $result = $aftersalesRepository->findAftersalesStatusByReferenceNumber('test-order-id');
        $this->assertSame([], $result);
    }

    public function testOnCheckOrderRefundable(): void
    {
        /** @var OrderEventListener $eventListener */
        $eventListener = self::getService(OrderEventListener::class);

        $event = new CheckOrderRefundableEvent();
        $event->setOrderId('test-order-123');
        $event->setOrderProducts(['product1' => ['id' => 'product1', 'quantity' => 1]]);

        $eventListener->onCheckOrderRefundable($event);

        // 验证事件监听器能正常处理事件
        $this->assertIsBool($event->getCanRefund());
    }

    public function testOnGetOrderDetail(): void
    {
        /** @var OrderEventListener $eventListener */
        $eventListener = self::getService(OrderEventListener::class);

        $event = new GetOrderDetailEvent();
        $event->setOrderId('test-order-123');

        $eventListener->onGetOrderDetail($event);

        // 验证事件监听器能正常处理事件
        $this->assertIsArray($event->getAftersalesStatus());
    }
}
