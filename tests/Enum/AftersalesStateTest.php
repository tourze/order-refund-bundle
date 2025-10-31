<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesState::class)]
class AftersalesStateTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        self::assertSame('pending_approval', AftersalesState::PENDING_APPROVAL->value);
        self::assertSame('approved', AftersalesState::APPROVED->value);
        self::assertSame('rejected', AftersalesState::REJECTED->value);
        self::assertSame('completed', AftersalesState::COMPLETED->value);
        self::assertSame('cancelled', AftersalesState::CANCELLED->value);
    }

    public function testGetLabel(): void
    {
        self::assertSame('审核中', AftersalesState::PENDING_APPROVAL->getLabel());
        self::assertSame('已通过', AftersalesState::APPROVED->getLabel());
        self::assertSame('已拒绝', AftersalesState::REJECTED->getLabel());
        self::assertSame('已完成', AftersalesState::COMPLETED->getLabel());
        self::assertSame('已关闭', AftersalesState::CANCELLED->getLabel());
    }

    public function testGetAllStates(): void
    {
        $states = AftersalesState::cases();

        self::assertCount(12, $states);
        self::assertContains(AftersalesState::PENDING_APPROVAL, $states);
        self::assertContains(AftersalesState::APPROVED, $states);
        self::assertContains(AftersalesState::COMPLETED, $states);
    }

    public function testSpecificStateToSelectItem(): void
    {
        $item = AftersalesState::PENDING_APPROVAL->toSelectItem();

        self::assertIsArray($item);
        self::assertArrayHasKey('value', $item);
        self::assertArrayHasKey('label', $item);
        self::assertSame('pending_approval', $item['value']);
        self::assertSame('审核中', $item['label']);
    }

    public function testGenOptions(): void
    {
        $options = AftersalesState::genOptions();

        self::assertIsArray($options);
        self::assertCount(12, $options);

        foreach ($options as $option) {
            self::assertArrayHasKey('value', $option);
            self::assertArrayHasKey('label', $option);
        }
    }

    public function testToArrayShouldReturnValueLabelPairs(): void
    {
        $array = AftersalesState::PENDING_APPROVAL->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('pending_approval', $array['value']);
        $this->assertEquals('审核中', $array['label']);
    }
}
