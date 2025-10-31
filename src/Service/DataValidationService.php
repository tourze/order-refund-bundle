<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;

/**
 * 数据验证服务
 */
class DataValidationService
{
    /**
     * 验证订单数据
     */
    /**
     * @return array<string>
     */
    public function validateOrderData(OrderDataDTO $orderData): array
    {
        return $orderData->validate();
    }

    /**
     * 验证商品数据
     */
    /**
     * @return array<string>
     */
    public function validateProductData(ProductDataDTO $productData): array
    {
        return $productData->validate();
    }

    /**
     * 批量验证商品数据
     *
     * @param ProductDataDTO[] $productDataList
     */
    /**
     * @param array<ProductDataDTO> $productDataList
     * @return array<string>
     */
    public function validateProductDataList(array $productDataList): array
    {
        $errors = [];

        if ([] === $productDataList) {
            $errors[] = '商品数据不能为空';

            return $errors;
        }

        foreach ($productDataList as $index => $productData) {
            $productErrors = $this->validateProductData($productData);
            if ([] !== $productErrors) {
                foreach ($productErrors as $error) {
                    $errors[] = "商品 #{$index}: {$error}";
                }
            }
        }

        return $errors;
    }

    /**
     * 验证退款请求数据
     *
     * @param array<string, int> $refundItems 商品ID => 退款数量
     * @param ProductDataDTO[] $orderProducts
     */
    /**
     * @param array<string, int> $refundItems
     * @param array<ProductDataDTO> $orderProducts
     * @return array<string>
     */
    public function validateRefundRequest(array $refundItems, array $orderProducts): array
    {
        $errors = [];

        if ([] === $refundItems) {
            $errors[] = '退款商品不能为空';

            return $errors;
        }

        // 创建商品ID映射
        $productMap = [];
        foreach ($orderProducts as $product) {
            $productMap[$product->productId] = $product;
        }

        foreach ($refundItems as $productId => $refundQuantity) {
            if (!isset($productMap[$productId])) {
                $errors[] = "商品 {$productId} 不存在于订单中";
                continue;
            }

            $orderProduct = $productMap[$productId];

            if ($refundQuantity <= 0) {
                $errors[] = "商品 {$productId} 的退款数量必须大于0";
            }

            if ($refundQuantity > $orderProduct->orderQuantity) {
                $errors[] = "商品 {$productId} 的退款数量({$refundQuantity})不能超过订单数量({$orderProduct->orderQuantity})";
            }
        }

        return $errors;
    }

    /**
     * 验证售后申请数据的完整性 - 适配单商品模式
     */
    /**
     * @return array<string>
     */
    public function validateAftersalesData(
        OrderDataDTO $orderData,
        ProductDataDTO $productData,
        string $orderProductId,
        int $quantity,
        AftersalesType $aftersalesType,
        RefundReason $reason,
    ): array {
        $errors = [];

        // 验证基础数据
        $errors = array_merge($errors, $this->validateOrderData($orderData));
        $errors = array_merge($errors, $this->validateProductData($productData));

        // 验证订单商品ID
        if ('' === $orderProductId) {
            $errors[] = '订单商品ID不能为空';
        }

        // 验证数量
        if ($quantity <= 0) {
            $errors[] = '申请数量必须大于0';
        }

        if ($quantity > $productData->orderQuantity) {
            $errors[] = "申请数量({$quantity})不能超过订单数量({$productData->orderQuantity})";
        }

        // AftersalesType 是枚举类，不需要检查 empty
        // 已通过类型约束保证非空

        // RefundReason 是枚举类，不需要检查 empty
        // 已通过类型约束保证非空

        // 验证业务逻辑
        $refundAmount = $productData->paidPrice * $quantity;
        if ($refundAmount <= 0) {
            $errors[] = '退款金额必须大于0';
        }

        return $errors;
    }
}
