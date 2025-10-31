<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Event\AftersalesCreatedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesCreatedEvent::class)]
class AftersalesCreatedEventTest extends AbstractEventTestCase
{
    public function testEventCreation(): void
    {
        $aftersales = new Aftersales();
        $orderData = ['orderNumber' => 'ORD001'];
        $productData = [['productId' => 'prod001']];
        $context = ['user' => 'test'];

        $event = new AftersalesCreatedEvent(
            $aftersales,
            $orderData,
            $productData,
            $context
        );

        $this->assertSame($aftersales, $event->getAftersales());
        $this->assertEquals($orderData, $event->getOrderData());
        $this->assertEquals($productData, $event->getProductData());
        $this->assertEquals($context, $event->getContext());
        $this->assertEquals(AftersalesCreatedEvent::NAME, 'aftersales.created');
    }

    public function testContextAccess(): void
    {
        $aftersales = new Aftersales();
        $context = ['user' => 'test', 'source' => 'web'];

        $event = new AftersalesCreatedEvent(
            $aftersales,
            [],
            [],
            $context
        );

        $this->assertEquals('test', $event->getContextValue('user'));
        $this->assertEquals('web', $event->getContextValue('source'));
        $this->assertNull($event->getContextValue('nonexistent'));
        $this->assertEquals('default', $event->getContextValue('nonexistent', 'default'));
    }
}
