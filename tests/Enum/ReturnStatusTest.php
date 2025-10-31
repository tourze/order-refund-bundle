<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\ReturnStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ReturnStatus::class)]
class ReturnStatusTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('待处理', ReturnStatus::PENDING->getLabel());
        $this->assertSame('已发货', ReturnStatus::SHIPPED->getLabel());
        $this->assertSame('运送中', ReturnStatus::IN_TRANSIT->getLabel());
        $this->assertSame('已收到', ReturnStatus::RECEIVED->getLabel());
        $this->assertSame('已检验', ReturnStatus::INSPECTED->getLabel());
        $this->assertSame('已拒绝', ReturnStatus::REJECTED->getLabel());
        $this->assertSame('已取消', ReturnStatus::CANCELLED->getLabel());
    }

    public function testValues(): void
    {
        $this->assertSame('PENDING', ReturnStatus::PENDING->value);
        $this->assertSame('SHIPPED', ReturnStatus::SHIPPED->value);
        $this->assertSame('IN_TRANSIT', ReturnStatus::IN_TRANSIT->value);
        $this->assertSame('RECEIVED', ReturnStatus::RECEIVED->value);
        $this->assertSame('INSPECTED', ReturnStatus::INSPECTED->value);
        $this->assertSame('REJECTED', ReturnStatus::REJECTED->value);
        $this->assertSame('CANCELLED', ReturnStatus::CANCELLED->value);
    }

    public function testCases(): void
    {
        $cases = ReturnStatus::cases();
        $this->assertCount(7, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $this->assertContains('PENDING', $values);
        $this->assertContains('SHIPPED', $values);
        $this->assertContains('IN_TRANSIT', $values);
        $this->assertContains('RECEIVED', $values);
        $this->assertContains('INSPECTED', $values);
        $this->assertContains('REJECTED', $values);
        $this->assertContains('CANCELLED', $values);
    }

    public function testToArray(): void
    {
        $array = ReturnStatus::PENDING->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('PENDING', $array['value']);
        $this->assertEquals('待处理', $array['label']);
    }
}
