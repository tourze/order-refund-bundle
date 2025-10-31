<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

/**
 * 退款金额计算服务
 */
class RefundCalculationService
{
    public function calculateItemRefund(object $orderProduct, int $quantity): float
    {
        $price = method_exists($orderProduct, 'getPrice')
            ? $orderProduct->getPrice()
            : (property_exists($orderProduct, 'price') ? $orderProduct->price : null);
        $unitPrice = is_numeric($price) ? (float) $price : 0.0;

        $discountValue = method_exists($orderProduct, 'getDiscount')
            ? $orderProduct->getDiscount()
            : (property_exists($orderProduct, 'discount') ? $orderProduct->discount : null);
        $discount = is_numeric($discountValue) ? (float) $discountValue : 0.0;

        $actualUnitPrice = $unitPrice - $discount;

        return $actualUnitPrice * $quantity;
    }

    public function calculatePointsRefund(object $orderProduct, int $quantity): int
    {
        $points = method_exists($orderProduct, 'getPoints')
            ? $orderProduct->getPoints()
            : (property_exists($orderProduct, 'points') ? $orderProduct->points : null);
        $unitPoints = is_numeric($points) ? (int) $points : 0;

        return $unitPoints * $quantity;
    }

    /**
     * @param array<array{orderProduct: object, quantity: int}> $items
     * @return array{amount: float, points: int}
     */
    public function calculateTotalRefund(array $items): array
    {
        $totalAmount = 0.0;
        $totalPoints = 0;

        foreach ($items as $item) {
            $totalAmount += $this->calculateItemRefund($item['orderProduct'], $item['quantity']);
            $totalPoints += $this->calculatePointsRefund($item['orderProduct'], $item['quantity']);
        }

        return [
            'amount' => $totalAmount,
            'points' => $totalPoints,
        ];
    }
}
