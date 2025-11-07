<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use BizUserBundle\Entity\BizUser;
use InvalidArgumentException;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Repository\OrderProductRepository;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;

/**
 * 售后验证服务
 */
final class AftersalesValidator
{
    public function __construct(
        private readonly OrderProductRepository $orderProductRepository,
    ) {
    }

    /**
     * 验证售后类型
     */
    public function validateAftersalesType(?string $type): AftersalesType
    {
        if (null === $type) {
            throw new \InvalidArgumentException('售后类型不能为空');
        }

        $aftersalesType = AftersalesType::tryFrom($type);
        if (null === $aftersalesType) {
            throw new \InvalidArgumentException('无效的售后类型');
        }

        return $aftersalesType;
    }

    /**
     * 验证退款原因
     */
    public function validateRefundReason(?string $reason): RefundReason
    {
        if (null === $reason) {
            throw new \InvalidArgumentException('退款原因不能为空');
        }

        $refundReason = RefundReason::tryFrom($reason);
        if (null === $refundReason) {
            throw new \InvalidArgumentException('无效的退款原因');
        }

        return $refundReason;
    }

    /**
     * 验证合同
     * @param array<mixed> $items
     */
    public function validateContract(string $contractId, array $items, Contract $contract, BizUser $user): void
    {
        if ('' === $contractId) {
            throw new \InvalidArgumentException('订单ID不能为空');
        }

        if ([] === $items) {
            throw new \InvalidArgumentException('售后商品列表不能为空');
        }

        if ($contract->getUser() !== $user) {
            throw new \InvalidArgumentException('无权操作此订单');
        }
    }

    /**
     * 验证售后商品项目
     * @param array<string, mixed> $item
     * @param array<string, array<string>> $activeAftersales
     */
    public function validateAftersalesItem(
        Contract $contract,
        array $item,
        int $index,
        array $activeAftersales,
    ): OrderProduct {
        $this->validateItemStructure($item, $index);

        $orderProductId = is_string($item['orderProductId']) || is_numeric($item['orderProductId'])
            ? (string) $item['orderProductId']
            : 'unknown';
        $this->validateNoActiveAftersales($orderProductId, $activeAftersales);

        $this->validateQuantity($item, $orderProductId);

        $orderProduct = $this->validateOrderProduct($contract, $orderProductId);
        $this->validateQuantityLimit($orderProduct, $item, $orderProductId);

        return $orderProduct;
    }

    /**
     * 验证项目结构
     * @param array<string, mixed> $item
     */
    private function validateItemStructure(array $item, int $index): void
    {
        if (!isset($item['orderProductId']) || !isset($item['quantity'])) {
            throw new \InvalidArgumentException('第 ' . ($index + 1) . ' 个商品项目格式不正确，缺少必要字段');
        }
    }

    /**
     * 验证无活跃售后
     * @param array<string, array<string>> $activeAftersales
     */
    private function validateNoActiveAftersales(string $orderProductId, array $activeAftersales): void
    {
        if (isset($activeAftersales[$orderProductId]) && [] !== $activeAftersales[$orderProductId]) {
            $states = implode(', ', $activeAftersales[$orderProductId]);
            throw new \InvalidArgumentException('商品 ' . $orderProductId . " 已存在售后申请（状态：{$states}），无法重复申请");
        }
    }

    /**
     * 验证数量
     * @param array<string, mixed> $item
     */
    private function validateQuantity(array $item, string $orderProductId): void
    {
        if ($item['quantity'] <= 0) {
            throw new \InvalidArgumentException('商品 ' . $orderProductId . ' 的数量必须大于0');
        }
    }

    /**
     * 验证订单商品
     */
    private function validateOrderProduct(Contract $contract, string $orderProductId): OrderProduct
    {
        $orderProduct = $this->orderProductRepository->find($orderProductId);
        if (null === $orderProduct || $orderProduct->getContract() !== $contract) {
            throw new \InvalidArgumentException('商品不属于此订单: ' . $orderProductId);
        }

        // 检查是否为赠品
        if ($orderProduct->isGift()) {
            throw new \InvalidArgumentException('赠品不允许售后，如有疑问请联系客服');
        }

        return $orderProduct;
    }

    /**
     * 验证数量限制
     * @param array<string, mixed> $item
     */
    private function validateQuantityLimit(OrderProduct $orderProduct, array $item, string $orderProductId): void
    {
        if (!is_int($item['quantity']) && !is_numeric($item['quantity'])) {
            throw new \InvalidArgumentException('商品数量格式错误');
        }
        $quantity = (int) $item['quantity'];
        if ($quantity > $orderProduct->getQuantity()) {
            throw new \InvalidArgumentException('商品 ' . $orderProductId . " 申请数量({$quantity})超过订单数量(" . $orderProduct->getQuantity() . ')');
        }
    }
}
