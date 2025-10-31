<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * OMS商品数据映射服务
 * 负责将OMS商品数据映射到售后实体
 */
readonly class OmsProductDataMapper
{
    /**
     * 设置售后商品信息到Aftersales实体
     * @param Aftersales $aftersales
     * @param array<int, array<string, mixed>> $products
     */
    public function setAftersalesProductData(Aftersales $aftersales, array $products): void
    {
        $firstProduct = $products[0] ?? [];
        if ([] === $firstProduct) {
            return;
        }

        $this->setBasicProductInfo($aftersales, $firstProduct);
        $this->setProductPricing($aftersales, $firstProduct);
        $aftersales->setProductSnapshot(['products' => $products]);
    }

    /**
     * 设置基本商品信息
     * @param Aftersales $aftersales
     * @param array<string, mixed> $product
     */
    private function setBasicProductInfo(Aftersales $aftersales, array $product): void
    {
        $orderProductId = $this->resolveOrderProductId($product);
        $productId = $this->resolveProductId($product);
        $productName = $this->extractStringValue($product, 'productName', 'Unknown Product');
        $skuId = $this->resolveSkuId($product, $productId);
        $skuName = $this->resolveSkuName($product, $productName);

        $aftersales->setOrderProductId($orderProductId);
        $aftersales->setProductId($productId);
        $aftersales->setSkuId($skuId);
        $aftersales->setProductName($productName);
        $aftersales->setSkuName($skuName);

        $quantity = isset($product['quantity']) && is_int($product['quantity']) ? $product['quantity'] : 1;
        $aftersales->setQuantity($quantity);
    }

    /**
     * 解析订单商品ID
     * @param array<string, mixed> $product
     * @return string
     */
    private function resolveOrderProductId(array $product): string
    {
        $orderProductId = $this->extractStringValue($product, 'orderProductId', '');
        if ('' !== $orderProductId) {
            return $orderProductId;
        }

        $productCode = $this->extractStringValue($product, 'productCode', '');

        return '' !== $productCode ? $productCode : 'unknown-order-product';
    }

    /**
     * 解析商品ID
     * @param array<string, mixed> $product
     * @return string
     */
    private function resolveProductId(array $product): string
    {
        $productId = $this->extractStringValue($product, 'productId', '');
        if ('' !== $productId) {
            return $productId;
        }

        $productCode = $this->extractStringValue($product, 'productCode', '');

        return '' !== $productCode ? $productCode : 'unknown-product';
    }

    /**
     * 解析SKU ID
     * @param array<string, mixed> $product
     * @param string $productId
     * @return string
     */
    private function resolveSkuId(array $product, string $productId): string
    {
        $skuId = $this->extractStringValue($product, 'skuId', '');

        return '' !== $skuId ? $skuId : ($productId . '-sku');
    }

    /**
     * 解析SKU名称
     * @param array<string, mixed> $product
     * @param string $productName
     * @return string
     */
    private function resolveSkuName(array $product, string $productName): string
    {
        $skuName = $this->extractStringValue($product, 'skuName', '');

        return '' !== $skuName ? $skuName : ($productName . ' (Default SKU)');
    }

    /**
     * 设置商品价格信息
     * @param Aftersales $aftersales
     * @param array<string, mixed> $product
     */
    private function setProductPricing(Aftersales $aftersales, array $product): void
    {
        $originalPrice = $this->extractStringValue($product, 'originalPrice', '0.00');
        $paidPrice = $this->extractStringValue($product, 'paidPrice', '0.00');

        $aftersales->setOriginalPrice($originalPrice);
        $aftersales->setPaidPrice($paidPrice);

        $refundAmount = $this->calculateRefundAmount($product);
        $aftersales->setRefundAmount($refundAmount);
        $aftersales->setOriginalRefundAmount($refundAmount);
        $aftersales->setActualRefundAmount($refundAmount);
    }

    /**
     * 计算退款金额
     * @param array<string, mixed> $product
     * @return string
     */
    private function calculateRefundAmount(array $product): string
    {
        $refundAmount = $this->extractStringValue($product, 'refundAmount', '');
        if ('' !== $refundAmount) {
            return $refundAmount;
        }

        if (isset($product['amount']) && is_numeric($product['amount'])) {
            return number_format((float) $product['amount'] / 100, 2, '.', '');
        }

        return '0.00';
    }

    /**
     * 安全提取字符串值
     * @param array<string, mixed> $data
     * @param string $key
     * @param string $default
     * @return string
     */
    private function extractStringValue(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }
}
