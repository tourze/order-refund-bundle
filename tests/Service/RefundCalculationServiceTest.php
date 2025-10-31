<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Service\RefundCalculationService;

/**
 * @internal
 */
#[CoversClass(RefundCalculationService::class)]
class RefundCalculationServiceTest extends TestCase
{
    public function testCalculateItemRefund(): void
    {
        $service = new RefundCalculationService();

        $orderProduct = $this->createMockOrderProduct(100.0, 10.0);
        $result = $service->calculateItemRefund($orderProduct, 2);

        $this->assertSame(180.0, $result);
    }

    public function testCalculateItemRefundWithoutDiscount(): void
    {
        $service = new RefundCalculationService();

        $orderProduct = $this->createMockOrderProduct(100.0, 0.0);
        $result = $service->calculateItemRefund($orderProduct, 3);

        $this->assertSame(300.0, $result);
    }

    public function testCalculatePointsRefund(): void
    {
        $service = new RefundCalculationService();

        $orderProduct = $this->createMockOrderProduct(100.0, 0.0, 50);
        $result = $service->calculatePointsRefund($orderProduct, 2);

        $this->assertSame(100, $result);
    }

    public function testCalculatePointsRefundWithoutPoints(): void
    {
        $service = new RefundCalculationService();

        $orderProduct = $this->createMockOrderProduct(100.0, 0.0, 0);
        $result = $service->calculatePointsRefund($orderProduct, 5);

        $this->assertSame(0, $result);
    }

    public function testCalculateTotalRefund(): void
    {
        $service = new RefundCalculationService();

        $item1 = $this->createMockOrderProduct(100.0, 10.0, 20);
        $item2 = $this->createMockOrderProduct(50.0, 5.0, 10);

        $items = [
            ['orderProduct' => $item1, 'quantity' => 2],
            ['orderProduct' => $item2, 'quantity' => 3],
        ];

        $result = $service->calculateTotalRefund($items);

        $this->assertSame(315.0, $result['amount']);
        $this->assertSame(70, $result['points']);
    }

    private function createMockOrderProduct(float $price, float $discount, int $points = 0): object
    {
        return new class($price, $discount, $points) {
            public function __construct(
                private readonly float $price,
                private readonly float $discount,
                private readonly int $points,
            ) {
            }

            public function getPrice(): float
            {
                return $this->price;
            }

            public function getDiscount(): float
            {
                return $this->discount;
            }

            public function getPoints(): int
            {
                return $this->points;
            }
        };
    }
}
