<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;

/**
 * 规则引擎服务 - 处理售后业务规则验证
 */
class RuleEngineService
{
    /**
     * 检查是否可以创建售后申请
     */
    public function canCreateAftersales(OrderDataDTO $orderData): bool
    {
        // 检查订单状态
        if (!$this->isValidOrderStatus($orderData->orderStatus)) {
            return false;
        }

        // 检查时间限制
        if (!$this->isWithinTimeLimit($orderData)) {
            return false;
        }

        // 检查订单金额
        if ($orderData->totalAmount <= 0) {
            return false;
        }

        return true;
    }

    /**
     * 验证退款商品
     *
     * @param ProductDataDTO[] $products
     * @param array<array{productId: string, quantity: int}> $refundItems
     */
    /**
     * @param array<ProductDataDTO> $products
     * @param array<array{productId: string, quantity: int}> $refundItems
     * @return array<string>
     */
    public function validateRefundItems(array $products, array $refundItems): array
    {
        $errors = [];

        // 创建商品映射
        $productMap = [];
        foreach ($products as $product) {
            $productMap[$product->productId] = $product;
        }

        foreach ($refundItems as $item) {
            $productId = $item['productId'];
            $quantity = $item['quantity'];

            if (!isset($productMap[$productId])) {
                $errors[] = "商品不存在: {$productId}";
                continue;
            }

            $product = $productMap[$productId];

            if ($quantity <= 0) {
                $errors[] = "退款数量必须大于0: {$productId}";
            }

            if ($quantity > $product->orderQuantity) {
                $errors[] = "退款数量超过订单数量: {$productId}";
            }
        }

        return $errors;
    }

    /**
     * 计算最大允许退款金额
     *
     * @param ProductDataDTO[] $products
     */
    public function calculateMaxRefundAmount(array $products): float
    {
        $maxAmount = 0.0;

        foreach ($products as $product) {
            $maxAmount += $product->paidPrice * $product->orderQuantity;
        }

        return $maxAmount;
    }

    /**
     * 检查售后类型是否允许
     */
    public function isAftersalesTypeAllowed(string $aftersalesType, OrderDataDTO $orderData): bool
    {
        $allowedTypes = $this->getAllowedAftersalesTypes($orderData);

        return in_array($aftersalesType, $allowedTypes, true);
    }

    /**
     * 获取允许的售后类型
     */
    /**
     * @return array<string>
     */
    public function getAllowedAftersalesTypes(OrderDataDTO $orderData): array
    {
        $types = [];

        // 根据订单状态决定允许的售后类型
        switch ($orderData->orderStatus) {
            case 'paid':
                $types = ['refund_only']; // 已支付未发货，只能退款
                break;

            case 'shipped':
                $types = ['return_refund', 'exchange']; // 已发货，可退货退款或换货
                break;

            case 'received':
                $types = ['return_refund', 'exchange', 'refund_only']; // 已收货，所有类型都可以
                break;

            default:
                $types = [];
        }

        return $types;
    }

    /**
     * 检查售后原因是否合理
     */
    public function isReasonValid(string $reason, string $aftersalesType): bool
    {
        $validReasons = $this->getValidReasonsForType($aftersalesType);

        return in_array($reason, $validReasons, true);
    }

    /**
     * 获取指定售后类型的有效原因
     */
    /**
     * @return array<string>
     */
    public function getValidReasonsForType(string $aftersalesType): array
    {
        return match ($aftersalesType) {
            'refund_only' => [
                'seven_days', // 七天无理由
                'not_needed', // 不需要了
                'duplicate',  // 重复下单
            ],
            'return_refund' => [
                'quality',     // 质量问题
                'damaged',     // 商品损坏
                'wrong_item',  // 发错商品
                'size_issue',  // 尺寸问题
            ],
            'exchange' => [
                'size_issue',  // 尺寸问题
                'color_issue', // 颜色问题
                'style_issue', // 款式问题
            ],
            default => [],
        };
    }

    /**
     * 检查订单状态是否有效
     */
    private function isValidOrderStatus(string $orderStatus): bool
    {
        $validStatuses = [
            'paid',     // 已支付
            'shipped',  // 已发货
            'received', // 已收货
        ];

        return in_array($orderStatus, $validStatuses, true);
    }

    /**
     * 检查是否在时间限制内
     */
    private function isWithinTimeLimit(OrderDataDTO $orderData): bool
    {
        $maxDaysStr = $_ENV['AFTERSALES_MAX_DAYS'] ?? '30';
        $maxDays = is_string($maxDaysStr) ? (int) $maxDaysStr : 30;
        $orderDate = new \DateTime($orderData->orderCreateTime->format('Y-m-d H:i:s'));
        $deadline = $orderDate->modify("+{$maxDays} days");
        $now = new \DateTime();

        return $now <= $deadline;
    }

    /**
     * 应用自定义规则
     */
    /**
     * @param array<string, mixed> $context
     * @return array<string, array<string, mixed>>
     */
    public function applyCustomRules(OrderDataDTO $orderData, array $context = []): array
    {
        $results = [];

        // 用户等级规则
        if (isset($context['userLevel']) && is_string($context['userLevel'])) {
            $results['user_level_check'] = $this->checkUserLevelRules($context['userLevel']);
        }

        // 商品类别规则
        if (isset($context['productCategory']) && is_string($context['productCategory'])) {
            $results['category_check'] = $this->checkCategoryRules($context['productCategory']);
        }

        // 历史售后次数规则
        if (isset($context['previousAftersalesCount']) && is_int($context['previousAftersalesCount'])) {
            $results['frequency_check'] = $this->checkFrequencyRules($context['previousAftersalesCount']);
        }

        return $results;
    }

    /**
     * 检查用户等级规则
     */
    /**
     * @return array<string, mixed>
     */
    private function checkUserLevelRules(string $userLevel): array
    {
        return match ($userLevel) {
            'vip' => [
                'allowed' => true,
                'auto_approve' => true,
                'message' => 'VIP用户自动审核通过',
            ],
            'premium' => [
                'allowed' => true,
                'auto_approve' => false,
                'message' => '高级用户优先处理',
            ],
            default => [
                'allowed' => true,
                'auto_approve' => false,
                'message' => '普通用户正常流程',
            ],
        };
    }

    /**
     * 检查商品类别规则
     */
    /**
     * @return array<string, mixed>
     */
    private function checkCategoryRules(string $category): array
    {
        $restrictedCategories = ['digital', 'customized', 'perishable'];

        if (in_array($category, $restrictedCategories, true)) {
            return [
                'allowed' => false,
                'message' => '该类别商品不支持售后',
            ];
        }

        return [
            'allowed' => true,
            'message' => '商品类别允许售后',
        ];
    }

    /**
     * 检查频率规则
     */
    /**
     * @return array<string, mixed>
     */
    private function checkFrequencyRules(int $previousCount): array
    {
        $maxAllowedStr = $_ENV['AFTERSALES_MAX_FREQUENCY'] ?? '5';
        $maxAllowed = is_string($maxAllowedStr) ? (int) $maxAllowedStr : 5;

        if ($previousCount >= $maxAllowed) {
            return [
                'allowed' => false,
                'message' => '售后申请次数超过限制',
            ];
        }

        return [
            'allowed' => true,
            'message' => '售后频率正常',
        ];
    }
}
