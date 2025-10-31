<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Event\AftersalesCompletedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesCompletedEvent::class)]
class AftersalesCompletedEventTest extends AbstractEventTestCase
{
    private Aftersales $aftersales;

    protected function setUp(): void
    {
        $this->aftersales = new Aftersales();
        $this->aftersales->setType(AftersalesType::REFUND_ONLY);
        $this->aftersales->setState(AftersalesState::COMPLETED);
        $this->aftersales->setReason(RefundReason::QUALITY_ISSUE);
    }

    public function testEventCreation(): void
    {
        $completionData = ['result' => '退款已处理', 'operator' => 'admin'];
        $context = ['refund_amount' => 100.0];

        $event = new AftersalesCompletedEvent(
            $this->aftersales,
            $completionData,
            $context
        );

        $this->assertSame($this->aftersales, $event->getAftersales());
        $this->assertSame($completionData, $event->getCompletionData());
        $this->assertSame($context, $event->getContext());
    }

    public function testEventName(): void
    {
        $this->assertSame('aftersales.completed', AftersalesCompletedEvent::NAME);
    }
}
