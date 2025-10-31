<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;

/**
 * 价格计算服务
 */
final class PriceCalculator
{
    private const DEFAULT_CURRENCY = 'CNY';

    /**
     * 计算订单总金额
     */
    public function calculateTotalAmount(Contract $contract): float
    {
        $total = 0.0;

        foreach ($contract->getPrices() as $price) {
            $total += (float) ($price->getMoney() ?? '0');
        }

        return $total;
    }

    /**
     * 获取商品原价
     */
    public function getOriginalPrice(OrderProduct $orderProduct): float
    {
        return $this->extractPriceFromOrderProduct($orderProduct, 'money');
    }

    /**
     * 获取商品实付价格
     */
    public function getPaidPrice(OrderProduct $orderProduct): float
    {
        return $this->extractPriceFromOrderProduct($orderProduct, 'money');
    }

    /**
     * 获取商品单价
     */
    public function getUnitPrice(OrderProduct $orderProduct): float
    {
        return $this->extractPriceFromOrderProduct($orderProduct, 'unitPrice');
    }

    /**
     * 从订单商品中提取价格
     */
    private function extractPriceFromOrderProduct(OrderProduct $orderProduct, string $priceField): float
    {
        foreach ($orderProduct->getPrices() as $price) {
            if (self::DEFAULT_CURRENCY === $price->getCurrency()) {
                $value = 'unitPrice' === $priceField
                    ? $price->getUnitPrice()
                    : $price->getMoney();

                return (float) ($value ?? '0');
            }
        }

        return 0.0;
    }
}
