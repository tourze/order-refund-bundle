<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\ExchangeStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ExchangeStatus::class)]
class ExchangeStatusTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('待处理', ExchangeStatus::PENDING->getLabel());
        $this->assertSame('APPROVED', ExchangeStatus::APPROVED->getLabel());
        $this->assertSame('REJECTED', ExchangeStatus::REJECTED->getLabel());
        $this->assertSame('RETURN_SHIPPED', ExchangeStatus::RETURN_SHIPPED->getLabel());
        $this->assertSame('RETURN_RECEIVED', ExchangeStatus::RETURN_RECEIVED->getLabel());
        $this->assertSame('EXCHANGE_SHIPPED', ExchangeStatus::EXCHANGE_SHIPPED->getLabel());
        $this->assertSame('已完成', ExchangeStatus::COMPLETED->getLabel());
        $this->assertSame('已取消', ExchangeStatus::CANCELLED->getLabel());
    }

    public function testValues(): void
    {
        $this->assertSame('PENDING', ExchangeStatus::PENDING->value);
        $this->assertSame('APPROVED', ExchangeStatus::APPROVED->value);
        $this->assertSame('REJECTED', ExchangeStatus::REJECTED->value);
        $this->assertSame('RETURN_SHIPPED', ExchangeStatus::RETURN_SHIPPED->value);
        $this->assertSame('RETURN_RECEIVED', ExchangeStatus::RETURN_RECEIVED->value);
        $this->assertSame('EXCHANGE_SHIPPED', ExchangeStatus::EXCHANGE_SHIPPED->value);
        $this->assertSame('COMPLETED', ExchangeStatus::COMPLETED->value);
        $this->assertSame('CANCELLED', ExchangeStatus::CANCELLED->value);
    }

    public function testCases(): void
    {
        $cases = ExchangeStatus::cases();
        $this->assertCount(8, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $this->assertContains('PENDING', $values);
        $this->assertContains('APPROVED', $values);
        $this->assertContains('REJECTED', $values);
        $this->assertContains('RETURN_SHIPPED', $values);
        $this->assertContains('RETURN_RECEIVED', $values);
        $this->assertContains('EXCHANGE_SHIPPED', $values);
        $this->assertContains('COMPLETED', $values);
        $this->assertContains('CANCELLED', $values);
    }

    public function testToArray(): void
    {
        $array = ExchangeStatus::PENDING->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('PENDING', $array['value']);
        $this->assertEquals('待处理', $array['label']);
    }
}
