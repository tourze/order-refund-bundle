<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;

/**
 * 快照服务 - 负责创建订单和商品的快照
 */
readonly class SnapshotService
{
    /**
     * 创建订单快照
     */
    public function createOrderSnapshot(Aftersales $aftersales, OrderDataDTO $orderData): AftersalesOrder
    {
        $snapshot = new AftersalesOrder();
        $snapshot->setAftersales($aftersales);
        $snapshot->setOrderNumber($orderData->orderNumber);
        $snapshot->setOrderStatus($orderData->orderStatus);
        $snapshot->setOrderCreateTime(
            $orderData->orderCreateTime instanceof \DateTimeImmutable
                ? $orderData->orderCreateTime
                : \DateTimeImmutable::createFromInterface($orderData->orderCreateTime)
        );
        $snapshot->setUserId($orderData->userId);
        $snapshot->setTotalAmount((string) $orderData->totalAmount);
        $snapshot->setExtra($orderData->extra);

        return $snapshot;
    }

    /**
     * 创建商品快照 - 直接设置Aftersales实体的商品字段
     */
    public function createProductSnapshot(
        Aftersales $aftersales,
        ProductDataDTO $productData,
        int $refundQuantity,
    ): void {
        // 直接设置 Aftersales 实体的商品相关字段
        $aftersales->setProductId($productData->productId);
        $aftersales->setSkuId($productData->skuId);
        $aftersales->setProductName($productData->productName);
        $aftersales->setSkuName($productData->skuName);
        $aftersales->setOriginalPrice((string) $productData->originalPrice);
        $aftersales->setPaidPrice((string) $productData->paidPrice);
        $aftersales->setQuantity($refundQuantity);

        // 计算退款金额
        $refundAmount = $productData->paidPrice * $refundQuantity;
        $aftersales->setRefundAmount((string) $refundAmount);
        $aftersales->setOriginalRefundAmount((string) $refundAmount);
        $aftersales->setActualRefundAmount((string) $refundAmount);

        // 设置商品快照数据到 productSnapshot 字段
        $productSnapshot = [
            'productId' => $productData->productId,
            'skuId' => $productData->skuId,
            'productName' => $productData->productName,
            'skuName' => $productData->skuName,
            'originalPrice' => $productData->originalPrice,
            'paidPrice' => $productData->paidPrice,
            'discountAmount' => $productData->discountAmount,
            'orderQuantity' => $productData->orderQuantity,
            'refundQuantity' => $refundQuantity,
            'attributes' => $productData->attributes,
            'refundAmount' => $refundAmount,
        ];
        $aftersales->setProductSnapshot($productSnapshot);
    }

    /**
     * 批量创建商品快照 - 注意：现在只支持单个商品，因为Aftersales实体设计为单商品
     *
     * @param ProductDataDTO[] $productDataList
     * @param array<string, int> $refundQuantities 商品ID => 退款数量
     * @return array<string, mixed>[] 返回处理结果的信息数组
     */
    public function createProductSnapshots(
        Aftersales $aftersales,
        array $productDataList,
        array $refundQuantities,
    ): array {
        $results = [];

        foreach ($productDataList as $productData) {
            $refundQuantity = $refundQuantities[$productData->productId] ?? 0;
            if ($refundQuantity > 0) {
                $this->createProductSnapshot($aftersales, $productData, $refundQuantity);
                $results[] = [
                    'productId' => $productData->productId,
                    'refundQuantity' => $refundQuantity,
                    'refundAmount' => (string) ($productData->paidPrice * $refundQuantity),
                    'processed' => true,
                ];
                // 注意：Aftersales实体设计为单商品，所以只处理第一个有效商品
                break;
            }
        }

        return $results;
    }

    /**
     * 验证快照数据完整性
     *
     * @return array<string>
     */
    public function validateSnapshot(AftersalesOrder $orderSnapshot, Aftersales $aftersales): array
    {
        $errors = [];

        $errors = array_merge($errors, $this->validateOrderSnapshot($orderSnapshot));
        $errors = array_merge($errors, $this->validateProductData($aftersales));

        return array_merge($errors, $this->validateQuantities($aftersales));
    }

    /**
     * @return array<string>
     */
    private function validateOrderSnapshot(AftersalesOrder $orderSnapshot): array
    {
        $errors = [];

        if (null === $orderSnapshot->getOrderNumber() || '' === $orderSnapshot->getOrderNumber()) {
            $errors[] = '订单编号不能为空';
        }

        if (null === $orderSnapshot->getUserId() || '' === $orderSnapshot->getUserId()) {
            $errors[] = '用户ID不能为空';
        }

        return $errors;
    }

    /**
     * @return array<string>
     */
    private function validateProductData(Aftersales $aftersales): array
    {
        $errors = [];

        if (null === $aftersales->getProductId() || '' === $aftersales->getProductId()) {
            $errors[] = '商品ID不能为空';
        }

        if (null === $aftersales->getQuantity() || $aftersales->getQuantity() <= 0) {
            $errors[] = '退款数量必须大于0';
        }

        return $errors;
    }

    /**
     * @return array<string>
     */
    private function validateQuantities(Aftersales $aftersales): array
    {
        $productSnapshot = $aftersales->getProductSnapshot();
        if (null === $productSnapshot) {
            return [];
        }

        $orderQuantityRaw = $productSnapshot['orderQuantity'] ?? 0;
        $orderQuantity = is_int($orderQuantityRaw)
            ? $orderQuantityRaw
            : (is_numeric($orderQuantityRaw) ? (int) $orderQuantityRaw : 0);
        $refundQuantity = $aftersales->getQuantity() ?? 0;

        if ($refundQuantity > $orderQuantity && $orderQuantity > 0) {
            return [
                sprintf(
                    '商品 %s 的退款数量(%d)不能超过订单数量(%d)',
                    $aftersales->getProductId() ?? '',
                    $refundQuantity,
                    $orderQuantity
                ),
            ];
        }

        return [];
    }
}
