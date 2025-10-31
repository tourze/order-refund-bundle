<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Event\AftersalesStatusChangedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesStatusChangedEvent::class)]
class AftersalesStatusChangedEventTest extends AbstractEventTestCase
{
    public function testEventCreation(): void
    {
        $aftersales = new Aftersales();
        $oldStatus = 'pending';
        $newStatus = 'approved';
        $action = 'approve';
        $context = ['operator' => 'admin'];

        $event = new AftersalesStatusChangedEvent(
            $aftersales,
            $oldStatus,
            $newStatus,
            $action,
            $context
        );

        $this->assertSame($aftersales, $event->getAftersales());
        $this->assertEquals($oldStatus, $event->getOldStatus());
        $this->assertEquals($newStatus, $event->getNewStatus());
        $this->assertEquals($action, $event->getAction());
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals(AftersalesStatusChangedEvent::NAME, 'aftersales.status_changed');
    }

    public function testStatusTransitionData(): void
    {
        $aftersales = new Aftersales();

        $event = new AftersalesStatusChangedEvent(
            $aftersales,
            'pending',
            'rejected',
            'reject',
            ['reason' => 'invalid_request']
        );

        $this->assertEquals('pending', $event->getOldStatus());
        $this->assertEquals('rejected', $event->getNewStatus());
        $this->assertEquals('reject', $event->getAction());
        $this->assertEquals('invalid_request', $event->getContextValue('reason'));
    }
}
