<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesType::class)]
class AftersalesTypeTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('订单待支付状态下取消订单', AftersalesType::CANCEL->getLabel());
        $this->assertSame('仅退款', AftersalesType::REFUND_ONLY->getLabel());
        $this->assertSame('退货退款', AftersalesType::RETURN_REFUND->getLabel());
        $this->assertSame('退回原商品，发出新商品', AftersalesType::EXCHANGE->getLabel());
        $this->assertSame('商品破损缺件，直接补发', AftersalesType::RESEND->getLabel());
    }

    public function testValues(): void
    {
        $this->assertSame('cancel', AftersalesType::CANCEL->value);
        $this->assertSame('refund_only', AftersalesType::REFUND_ONLY->value);
        $this->assertSame('return_refund', AftersalesType::RETURN_REFUND->value);
        $this->assertSame('exchange', AftersalesType::EXCHANGE->value);
        $this->assertSame('resend', AftersalesType::RESEND->value);
    }

    public function testCases(): void
    {
        $cases = AftersalesType::cases();
        $this->assertCount(5, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $this->assertContains('cancel', $values);
        $this->assertContains('refund_only', $values);
        $this->assertContains('return_refund', $values);
        $this->assertContains('exchange', $values);
        $this->assertContains('resend', $values);
    }

    public function testToArray(): void
    {
        $array = AftersalesType::CANCEL->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('cancel', $array['value']);
        $this->assertEquals('订单待支付状态下取消订单', $array['label']);
    }
}
