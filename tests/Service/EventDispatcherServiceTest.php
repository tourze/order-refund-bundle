<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Event\AftersalesCancelledEvent;
use Tourze\OrderRefundBundle\Event\AftersalesCompletedEvent;
use Tourze\OrderRefundBundle\Event\AftersalesCreatedEvent;
use Tourze\OrderRefundBundle\Event\AftersalesProcessingEvent;
use Tourze\OrderRefundBundle\Event\AftersalesStatusChangedEvent;
use Tourze\OrderRefundBundle\Service\EventDispatcherService;

/**
 * @internal
 */
#[CoversClass(EventDispatcherService::class)]
class EventDispatcherServiceTest extends TestCase
{
    private EventDispatcherService $service;

    private EventDispatcher $eventDispatcher;

    /** @var array<object> */
    private array $dispatchedEvents;

    protected function setUp(): void
    {
        $this->dispatchedEvents = [];
        $this->eventDispatcher = new EventDispatcher();
        $this->service = new EventDispatcherService($this->eventDispatcher);

        // 添加监听器来捕获分发的事件
        $this->eventDispatcher->addListener(
            AftersalesCreatedEvent::NAME,
            fn (object $event) => $this->dispatchedEvents[] = $event
        );

        $this->eventDispatcher->addListener(
            AftersalesStatusChangedEvent::NAME,
            fn (object $event) => $this->dispatchedEvents[] = $event
        );

        $this->eventDispatcher->addListener(
            AftersalesCancelledEvent::NAME,
            fn (object $event) => $this->dispatchedEvents[] = $event
        );

        $this->eventDispatcher->addListener(
            AftersalesCompletedEvent::NAME,
            fn (object $event) => $this->dispatchedEvents[] = $event
        );

        $this->eventDispatcher->addListener(
            AftersalesProcessingEvent::NAME,
            fn (object $event) => $this->dispatchedEvents[] = $event
        );
    }

    public function testDispatchAftersalesCreated(): void
    {
        $aftersales = new Aftersales();
        $orderData = ['orderNumber' => 'ORD001'];
        $productData = [['productId' => 'prod001']];
        $context = ['user' => 'test'];

        $this->service->dispatchAftersalesCreated(
            $aftersales,
            $orderData,
            $productData,
            $context
        );

        $this->assertCount(1, $this->dispatchedEvents);
        $event = $this->dispatchedEvents[0];
        $this->assertInstanceOf(AftersalesCreatedEvent::class, $event);
        $this->assertSame($aftersales, $event->getAftersales());
        $this->assertEquals($orderData, $event->getOrderData());
    }

    public function testDispatchStatusChanged(): void
    {
        $aftersales = new Aftersales();
        $oldStatus = 'pending';
        $newStatus = 'approved';
        $action = 'approve';

        $this->service->dispatchStatusChanged(
            $aftersales,
            $oldStatus,
            $newStatus,
            $action
        );

        $this->assertCount(1, $this->dispatchedEvents);
        $event = $this->dispatchedEvents[0];
        $this->assertInstanceOf(AftersalesStatusChangedEvent::class, $event);
        $this->assertEquals($oldStatus, $event->getOldStatus());
        $this->assertEquals($newStatus, $event->getNewStatus());
        $this->assertEquals($action, $event->getAction());
    }

    public function testDispatchConditionalSuccess(): void
    {
        $aftersales = new Aftersales();

        $eventData = [
            'orderData' => ['orderNumber' => 'ORD001'],
            'productData' => [['productId' => 'prod001']],
            'context' => ['user' => 'test'],
        ];

        $conditions = ['enabled' => true];

        $result = $this->service->dispatchConditional(
            'created',
            $aftersales,
            $eventData,
            $conditions
        );

        $this->assertTrue($result);
        $this->assertCount(1, $this->dispatchedEvents);
    }

    public function testDispatchConditionalDisabled(): void
    {
        $aftersales = new Aftersales();

        $eventData = [
            'orderData' => ['orderNumber' => 'ORD001'],
            'productData' => [['productId' => 'prod001']],
        ];

        $conditions = ['enabled' => false];

        $result = $this->service->dispatchConditional(
            'created',
            $aftersales,
            $eventData,
            $conditions
        );

        $this->assertFalse($result);
        $this->assertCount(0, $this->dispatchedEvents);
    }

    public function testDispatchBatchStatusChanged(): void
    {
        $aftersales1 = new Aftersales();
        $aftersales2 = new Aftersales();

        $this->service->dispatchBatchStatusChanged(
            [$aftersales1, $aftersales2],
            'pending',
            'approved',
            'batch_approve'
        );

        $this->assertCount(2, $this->dispatchedEvents);

        foreach ($this->dispatchedEvents as $event) {
            $this->assertInstanceOf(AftersalesStatusChangedEvent::class, $event);
            $this->assertEquals('batch_approve', $event->getAction());
            $this->assertTrue($event->getContextValue('batch_operation'));
        }
    }

    public function testDispatchCancelled(): void
    {
        $aftersales = new Aftersales();
        $cancelReason = 'Customer requested cancellation';
        $operator = 'admin_user';
        $context = ['cancel_type' => 'manual'];

        $this->service->dispatchCancelled(
            $aftersales,
            $cancelReason,
            $operator,
            $context
        );

        $this->assertCount(1, $this->dispatchedEvents);
        $event = $this->dispatchedEvents[0];
        $this->assertInstanceOf(AftersalesCancelledEvent::class, $event);
        $this->assertSame($aftersales, $event->getAftersales());
        $this->assertEquals($cancelReason, $event->getCancelReason());
        $this->assertEquals($operator, $event->getOperator());
    }

    public function testDispatchCompleted(): void
    {
        $aftersales = new Aftersales();
        $completionData = ['refund_amount' => 100.0, 'completion_time' => '2023-01-01 10:00:00'];
        $context = ['completion_type' => 'automatic'];

        $this->service->dispatchCompleted(
            $aftersales,
            $completionData,
            $context
        );

        $this->assertCount(1, $this->dispatchedEvents);
        $event = $this->dispatchedEvents[0];
        $this->assertInstanceOf(AftersalesCompletedEvent::class, $event);
        $this->assertSame($aftersales, $event->getAftersales());
        $this->assertEquals($completionData, $event->getCompletionData());
    }

    public function testDispatchProcessing(): void
    {
        $aftersales = new Aftersales();
        $processingType = 'refund_processing';
        $processingData = ['step' => 'verification', 'progress' => 50];
        $context = ['processor' => 'system'];

        $this->service->dispatchProcessing(
            $aftersales,
            $processingType,
            $processingData,
            $context
        );

        $this->assertCount(1, $this->dispatchedEvents);
        $event = $this->dispatchedEvents[0];
        $this->assertInstanceOf(AftersalesProcessingEvent::class, $event);
        $this->assertSame($aftersales, $event->getAftersales());
        $this->assertEquals($processingType, $event->getProcessingType());
        $this->assertEquals($processingData, $event->getProcessingData());
    }
}
