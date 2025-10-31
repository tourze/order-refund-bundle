<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\RefundStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(RefundStatus::class)]
class RefundStatusTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('待处理', RefundStatus::PENDING->getLabel());
        $this->assertSame('处理中', RefundStatus::PROCESSING->getLabel());
        $this->assertSame('退款成功', RefundStatus::SUCCESS->getLabel());
        $this->assertSame('退款失败', RefundStatus::FAILED->getLabel());
        $this->assertSame('已取消', RefundStatus::CANCELLED->getLabel());
    }

    public function testValues(): void
    {
        $this->assertSame('PENDING', RefundStatus::PENDING->value);
        $this->assertSame('PROCESSING', RefundStatus::PROCESSING->value);
        $this->assertSame('SUCCESS', RefundStatus::SUCCESS->value);
        $this->assertSame('FAILED', RefundStatus::FAILED->value);
        $this->assertSame('CANCELLED', RefundStatus::CANCELLED->value);
    }

    public function testCases(): void
    {
        $cases = RefundStatus::cases();
        $this->assertCount(5, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $this->assertContains('PENDING', $values);
        $this->assertContains('PROCESSING', $values);
        $this->assertContains('SUCCESS', $values);
        $this->assertContains('FAILED', $values);
        $this->assertContains('CANCELLED', $values);
    }

    public function testToArray(): void
    {
        $array = RefundStatus::PENDING->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('PENDING', $array['value']);
        $this->assertEquals('待处理', $array['label']);
    }
}
