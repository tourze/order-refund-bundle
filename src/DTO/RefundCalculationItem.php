<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DTO;

/**
 * 单个商品退款计算信息
 */
readonly class RefundCalculationItem
{
    /**
     * @param array<string> $errors 该商品的错误信息
     */
    public function __construct(
        public string $orderProductId,           // 订单商品ID
        public string $productName,              // 商品名称
        public string $skuName,                  // SKU名称
        public int $orderQuantity,               // 订单总数量
        public int $refundedQuantity,            // 已退数量
        public int $maxRefundableQuantity,       // 最大可退数量
        public int $requestQuantity,             // 本次申请数量
        public string $unitPrice,                // 单价(实付)
        public string $refundableAmount,         // 本次可退金额
        public string $totalRefundedAmount,      // 该商品累计已退金额
        public string $mainThumb,      // 主图
        public bool $canRefund,                  // 该商品是否可退
        public array $errors = [],               // 该商品的错误信息
    ) {
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'orderProductId' => $this->orderProductId,
            'productName' => $this->productName,
            'skuName' => $this->skuName,
            'mainThumb' => $this->mainThumb,
            'orderQuantity' => $this->orderQuantity,
            'refundedQuantity' => $this->refundedQuantity,
            'maxRefundableQuantity' => $this->maxRefundableQuantity,
            'requestQuantity' => $this->requestQuantity,
            'unitPrice' => $this->unitPrice,
            'refundableAmount' => $this->refundableAmount,
            'totalRefundedAmount' => $this->totalRefundedAmount,
            'canRefund' => $this->canRefund,
            'errors' => $this->errors,
        ];
    }
}
