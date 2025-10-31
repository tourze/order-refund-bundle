<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Event\AftersalesCancelledEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesCancelledEvent::class)]
class AftersalesCancelledEventTest extends AbstractEventTestCase
{
    private Aftersales $aftersales;

    protected function setUp(): void
    {
        $this->aftersales = new Aftersales();
        $this->aftersales->setType(AftersalesType::REFUND_ONLY);
        $this->aftersales->setState(AftersalesState::CANCELLED);
        $this->aftersales->setReason(RefundReason::QUALITY_ISSUE);
    }

    public function testEventCreation(): void
    {
        $cancelReason = '用户取消';
        $operator = 'admin';
        $context = ['timestamp' => time()];

        $event = new AftersalesCancelledEvent(
            $this->aftersales,
            $cancelReason,
            $operator,
            $context
        );

        $this->assertSame($this->aftersales, $event->getAftersales());
        $this->assertSame($cancelReason, $event->getCancelReason());
        $this->assertSame($operator, $event->getOperator());
        $this->assertSame($context, $event->getContext());
    }

    public function testEventName(): void
    {
        $this->assertSame('aftersales.cancelled', AftersalesCancelledEvent::NAME);
    }

    public function testEventWithoutOperator(): void
    {
        $event = new AftersalesCancelledEvent($this->aftersales, '系统取消');

        $this->assertNull($event->getOperator());
        $this->assertSame('系统取消', $event->getCancelReason());
    }
}
