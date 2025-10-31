<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\AftersalesStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesStatus::class)]
class AftersalesStatusTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('待审核', AftersalesStatus::PENDING->getLabel());
        $this->assertSame('已审核', AftersalesStatus::APPROVED->getLabel());
        $this->assertSame('已拒绝', AftersalesStatus::REJECTED->getLabel());
        $this->assertSame('处理中', AftersalesStatus::PROCESSING->getLabel());
        $this->assertSame('已完成', AftersalesStatus::COMPLETED->getLabel());
        $this->assertSame('已取消', AftersalesStatus::CANCELLED->getLabel());
    }

    public function testValues(): void
    {
        $this->assertSame('pending', AftersalesStatus::PENDING->value);
        $this->assertSame('approved', AftersalesStatus::APPROVED->value);
        $this->assertSame('rejected', AftersalesStatus::REJECTED->value);
        $this->assertSame('processing', AftersalesStatus::PROCESSING->value);
        $this->assertSame('completed', AftersalesStatus::COMPLETED->value);
        $this->assertSame('cancelled', AftersalesStatus::CANCELLED->value);
    }

    public function testToArray(): void
    {
        $this->assertSame(['value' => 'pending', 'label' => '待审核'], AftersalesStatus::PENDING->toArray());
        $this->assertSame(['value' => 'approved', 'label' => '已审核'], AftersalesStatus::APPROVED->toArray());
        $this->assertSame(['value' => 'rejected', 'label' => '已拒绝'], AftersalesStatus::REJECTED->toArray());
        $this->assertSame(['value' => 'processing', 'label' => '处理中'], AftersalesStatus::PROCESSING->toArray());
        $this->assertSame(['value' => 'completed', 'label' => '已完成'], AftersalesStatus::COMPLETED->toArray());
        $this->assertSame(['value' => 'cancelled', 'label' => '已取消'], AftersalesStatus::CANCELLED->toArray());
    }
}
