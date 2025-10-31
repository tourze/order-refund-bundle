<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DTO;

/**
 * 退款计算结果
 */
readonly class RefundCalculationResult
{
    /**
     * @param RefundCalculationItem[] $items 商品退款信息列表
     * @param array<string> $validationErrors 验证错误列表
     * @param array<string> $refundRules 退款规则说明
     */
    public function __construct(
        public string $contractId,               // 订单ID
        public string $orderNumber,              // 订单号
        public string $totalRefundableAmount,    // 本次总可退金额
        public bool $canRefund,                  // 整体是否可退款
        public array $items = [],                // 商品退款信息列表
        public array $validationErrors = [],     // 验证错误列表
        public array $refundRules = [],          // 退款规则说明
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
            'contractId' => $this->contractId,
            'orderNumber' => $this->orderNumber,
            'items' => array_map(fn (RefundCalculationItem $item) => $item->toArray(), $this->items),
            'totalRefundableAmount' => $this->totalRefundableAmount,
            'canRefund' => $this->canRefund,
            'validationErrors' => $this->validationErrors,
            'refundRules' => $this->refundRules,
        ];
    }
}
