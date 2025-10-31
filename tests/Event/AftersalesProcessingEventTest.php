<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Event\AftersalesProcessingEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesProcessingEvent::class)]
class AftersalesProcessingEventTest extends AbstractEventTestCase
{
    private Aftersales $aftersales;

    protected function setUp(): void
    {
        $this->aftersales = new Aftersales();
        $this->aftersales->setType(AftersalesType::REFUND_ONLY);
        $this->aftersales->setState(AftersalesState::PENDING_REFUND);
        $this->aftersales->setReason(RefundReason::QUALITY_ISSUE);
    }

    public function testEventCreation(): void
    {
        $processingType = 'refund_processing';
        $processingData = ['step' => '开始退款处理', 'operator' => 'admin'];
        $context = ['step' => 1];

        $event = new AftersalesProcessingEvent(
            $this->aftersales,
            $processingType,
            $processingData,
            $context
        );

        $this->assertSame($this->aftersales, $event->getAftersales());
        $this->assertSame($processingType, $event->getProcessingType());
        $this->assertSame($processingData, $event->getProcessingData());
        $this->assertSame($context, $event->getContext());
    }

    public function testEventName(): void
    {
        $this->assertSame('aftersales.processing', AftersalesProcessingEvent::NAME);
    }
}
