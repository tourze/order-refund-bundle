<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\PaymentMethod;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PaymentMethod::class)]
class PaymentMethodTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('ALIPAY', PaymentMethod::ALIPAY->getLabel());
        $this->assertSame('WECHAT_PAY', PaymentMethod::WECHAT_PAY->getLabel());
        $this->assertSame('UNION_PAY', PaymentMethod::UNION_PAY->getLabel());
        $this->assertSame('CREDIT_CARD', PaymentMethod::CREDIT_CARD->getLabel());
        $this->assertSame('BANK_TRANSFER', PaymentMethod::BANK_TRANSFER->getLabel());
        $this->assertSame('BALANCE', PaymentMethod::BALANCE->getLabel());
        $this->assertSame('POINTS', PaymentMethod::POINTS->getLabel());
        $this->assertSame('优惠券', PaymentMethod::COUPON->getLabel());
    }

    public function testValues(): void
    {
        $this->assertSame('ALIPAY', PaymentMethod::ALIPAY->value);
        $this->assertSame('WECHAT_PAY', PaymentMethod::WECHAT_PAY->value);
        $this->assertSame('UNION_PAY', PaymentMethod::UNION_PAY->value);
        $this->assertSame('CREDIT_CARD', PaymentMethod::CREDIT_CARD->value);
        $this->assertSame('BANK_TRANSFER', PaymentMethod::BANK_TRANSFER->value);
        $this->assertSame('BALANCE', PaymentMethod::BALANCE->value);
        $this->assertSame('POINTS', PaymentMethod::POINTS->value);
        $this->assertSame('COUPON', PaymentMethod::COUPON->value);
    }

    public function testCases(): void
    {
        $cases = PaymentMethod::cases();
        $this->assertCount(8, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $this->assertContains('ALIPAY', $values);
        $this->assertContains('WECHAT_PAY', $values);
        $this->assertContains('UNION_PAY', $values);
        $this->assertContains('CREDIT_CARD', $values);
        $this->assertContains('BANK_TRANSFER', $values);
        $this->assertContains('BALANCE', $values);
        $this->assertContains('POINTS', $values);
        $this->assertContains('COUPON', $values);
    }

    public function testToArray(): void
    {
        $array = PaymentMethod::ALIPAY->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('ALIPAY', $array['value']);
        $this->assertEquals('ALIPAY', $array['label']);
    }
}
