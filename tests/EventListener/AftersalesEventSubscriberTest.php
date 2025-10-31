<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Event\AftersalesCancelledEvent;
use Tourze\OrderRefundBundle\Event\AftersalesCompletedEvent;
use Tourze\OrderRefundBundle\Event\AftersalesCreatedEvent;
use Tourze\OrderRefundBundle\Event\AftersalesStatusChangedEvent;
use Tourze\OrderRefundBundle\EventListener\AftersalesEventSubscriber;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesEventSubscriber::class)]
#[RunTestsInSeparateProcesses]
class AftersalesEventSubscriberTest extends AbstractEventSubscriberTestCase
{
    private AftersalesEventSubscriber $listener;

    private LoggerInterface&MockObject $logger;

    protected function onSetUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        // 通过容器设置具体的monolog logger服务，然后从容器获取EventListener实例
        self::getContainer()->set('monolog.logger.order_refund', $this->logger);
        $this->listener = self::getService(AftersalesEventSubscriber::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = AftersalesEventSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(AftersalesCreatedEvent::NAME, $events);
        $this->assertArrayHasKey(AftersalesStatusChangedEvent::NAME, $events);
        $this->assertArrayHasKey(AftersalesCompletedEvent::NAME, $events);
        $this->assertArrayHasKey(AftersalesCancelledEvent::NAME, $events);

        $this->assertEquals('onAftersalesCreated', $events[AftersalesCreatedEvent::NAME]);
        $this->assertEquals('onAftersalesStatusChanged', $events[AftersalesStatusChangedEvent::NAME]);
        $this->assertEquals('onAftersalesCompleted', $events[AftersalesCompletedEvent::NAME]);
        $this->assertEquals('onAftersalesCancelled', $events[AftersalesCancelledEvent::NAME]);
    }

    public function testOnAftersalesCreated(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setReferenceNumber('ORD001');
        $aftersales->setType(AftersalesType::REFUND_ONLY);
        $aftersales->setReason(RefundReason::DONT_WANT);

        $event = new AftersalesCreatedEvent(
            $aftersales,
            ['orderNumber' => 'ORD001'],
            [['productId' => 'prod001']],
            ['user' => 'test']
        );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('售后申请已创建', static::callback(function ($context) {
                if ( ! is_array($context)) {
                    return false;
                }

                return isset($context['reference_number'])
                    && isset($context['aftersales_type'], $context['reason'], $context['context']);
            }))
        ;

        $this->listener->onAftersalesCreated($event);
    }

    public function testOnAftersalesStatusChanged(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setReferenceNumber('ORD001');

        $event = new AftersalesStatusChangedEvent(
            $aftersales,
            'pending',
            'approved',
            'approve',
            ['operator' => 'admin']
        );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('售后状态已变更', static::callback(function ($context) {
                if ( ! is_array($context)) {
                    return false;
                }

                return isset($context['reference_number'])
                    && 'pending' === $context['old_status']
                    && 'approved' === $context['new_status']
                    && 'approve' === $context['action'];
            }))
        ;

        $this->listener->onAftersalesStatusChanged($event);
    }

    public function testOnAftersalesCompleted(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setReferenceNumber('ORD001');

        $completionData = [
            'total_refund_amount' => 100.0,
            'completed_items' => [
                ['productId' => 'prod001', 'quantity' => 2],
            ],
        ];

        $event = new AftersalesCompletedEvent(
            $aftersales,
            $completionData,
            ['operator' => 'admin']
        );

        // 期望info方法被调用两次：一次是"售后申请已完成"，一次是"售后类型无需返还库存"
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->with(
                static::logicalOr(
                    static::equalTo('售后申请已完成'),
                    static::equalTo('售后类型无需返还库存')
                ),
                static::callback(fn ($value) => is_array($value))
            )
        ;

        $this->listener->onAftersalesCompleted($event);
    }

    public function testOnAftersalesCancelled(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setReferenceNumber('ORD001');

        $event = new AftersalesCancelledEvent(
            $aftersales,
            'user_request',
            'admin',
            ['timestamp' => new \DateTime()]
        );

        $this->logger->expects($this->once())
            ->method('info')
            ->with('售后申请已取消', static::callback(function ($context) {
                if ( ! is_array($context)) {
                    return false;
                }

                return isset($context['reference_number'])
                    && 'user_request' === $context['cancel_reason']
                    && 'admin' === $context['operator'];
            }))
        ;

        $this->listener->onAftersalesCancelled($event);
    }
}
