<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Refund;

use OrderCoreBundle\Entity\Contract;
use Symfony\Component\Security\Core\User\UserInterface;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Repository\ContractRepository;
use OrderCoreBundle\Repository\OrderProductRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderRefundBundle\DTO\RefundCalculationItem;
use Tourze\OrderRefundBundle\DTO\RefundCalculationResult;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;

#[MethodTag(name: '退款管理')]
#[MethodDoc(description: '计算退款信息 - 批量计算同一订单下多个商品的退款信息')]
#[MethodExpose(method: 'refundCalculateRefundInfo')]
#[IsGranted(attribute: 'ROLE_USER')]
class CalculateRefundInfoProcedure extends BaseProcedure
{
    #[MethodParam(description: '订单ID')]
    #[Assert\NotBlank]
    public string $contractId = '';

    /**
     * @var array<array{orderProductId: string, quantity: int}>
     */
    #[MethodParam(description: '商品退款申请列表')]
    #[Assert\NotBlank]
    #[Assert\Type(type: 'array')]
    public array $items = [];

    public function __construct(
        private readonly Security $security,
        private readonly ContractRepository $contractRepository,
        private readonly OrderProductRepository $orderProductRepository,
        private readonly AftersalesRepository $aftersalesRepository,
    ) {
    }

    public function execute(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            throw new ApiException('用户未登录或类型错误');
        }

        // 1. 验证订单存在且属于当前用户
        $contract = $this->validateContract($user);

        // 2. 验证items参数格式
        $this->validateItems();

        // 3. 批量查询订单商品信息
        $orderProducts = $this->getOrderProductsByIds($contract);

        // 4. 批量查询所有商品的售后历史记录
        $refundHistory = $this->getRefundHistoryBatch(array_keys($orderProducts));

        // 5. 逐个计算每个商品的退款信息
        $calculationItems = [];
        $totalRefundableAmount = '0.00';
        $validationErrors = [];

        foreach ($this->items as $requestItem) {
            $orderProduct = $orderProducts[$requestItem['orderProductId']] ?? null;
            if (null === $orderProduct) {
                $validationErrors[] = "商品不存在: {$requestItem['orderProductId']}";
                continue;
            }

            $calculationItem = $this->calculateSingleItem(
                $orderProduct,
                $requestItem['quantity'],
                $refundHistory[$requestItem['orderProductId']] ?? []
            );

            $calculationItems[] = $calculationItem;

            if ($calculationItem->canRefund) {
                /** @var numeric-string $refundableAmount */
                $refundableAmount = $calculationItem->refundableAmount;
                $totalRefundableAmount = bcadd($totalRefundableAmount, $refundableAmount, 2);
            }
        }

        // 6. 整体业务规则验证
        $canRefund = [] === $validationErrors && $this->validateOverallRules($contract);
        if (!$canRefund && [] === $validationErrors) {
            $validationErrors[] = '订单状态不允许申请退款';
        }

        $result = new RefundCalculationResult(
            contractId: $this->contractId,
            orderNumber: $contract->getSn(),
            totalRefundableAmount: $totalRefundableAmount,
            canRefund: $canRefund,
            items: $calculationItems,
            validationErrors: $validationErrors,
            refundRules: $this->getRefundRules($contract)
        );

        return $result->toArray();
    }

    private function validateContract(UserInterface $user): Contract
    {
        if ('' === $this->contractId) {
            throw new ApiException('订单ID不能为空');
        }

        $contract = $this->contractRepository->find($this->contractId);
        if (null === $contract) {
            throw new ApiException('订单不存在');
        }

        if ($contract->getUser() !== $user) {
            throw new ApiException('无权操作此订单');
        }

        return $contract;
    }

    private function validateItems(): void
    {
        if ([] === $this->items) {
            throw new ApiException('商品退款申请列表不能为空');
        }

        foreach ($this->items as $index => $item) {
            // Runtime validation needed for test cases that use reflection to inject invalid data
            if (!array_key_exists('orderProductId', $item) || !array_key_exists('quantity', $item)) {
                throw new ApiException('第 ' . ($index + 1) . ' 个商品项目格式不正确，缺少必要字段');
            }

            if ('' === $item['orderProductId']) {
                throw new ApiException('第 ' . ($index + 1) . ' 参数异常');
            }

            if (!is_int($item['quantity']) || $item['quantity'] <= 0) {
                throw new ApiException('第 ' . ($index + 1) . ' 个商品的quantity必须是大于0的整数');
            }
        }
    }

    /**
     * @return array<string, OrderProduct>
     */
    private function getOrderProductsByIds(Contract $contract): array
    {
        $orderProductIds = array_column($this->items, 'orderProductId');
        $orderProducts = $this->orderProductRepository->findBy(['id' => $orderProductIds]);

        $result = [];
        foreach ($orderProducts as $orderProduct) {
            if ($orderProduct->getContract() === $contract) {
                $productIdStr = (string) $orderProduct->getId();
                $result[$productIdStr] = $orderProduct;
            }
        }

        /** @var array<string, OrderProduct> */
        return $result;
    }

    /**
     * @param array<string> $orderProductIds
     * @return array<string, array<array{quantity: int, refundAmount: string}>>
     */
    private function getRefundHistoryBatch(array $orderProductIds): array
    {
        if ([] === $orderProductIds) {
            return [];
        }

        return $this->aftersalesRepository->findRefundHistoryBatch($orderProductIds);
    }

    /**
     * @param array<array{quantity: int, refundAmount: string}> $refundHistory
     */
    private function calculateSingleItem(OrderProduct $orderProduct, int $requestQuantity, array $refundHistory): RefundCalculationItem
    {
        // 计算已退信息
        $refundedQuantity = array_sum(array_column($refundHistory, 'quantity'));
        $totalRefundedAmount = array_reduce(
            $refundHistory,
            function (string $sum, array $item): string {
                /** @var numeric-string $refundAmount */
                $refundAmount = $item['refundAmount'];

                return bcadd($sum, $refundAmount, 2);
            },
            '0.00'
        );

        // 计算可退信息
        $maxRefundableQuantity = $orderProduct->getQuantity() - $refundedQuantity;
        /** @var numeric-string $unitPrice */
        $unitPrice = $this->calculateUnitPrice($orderProduct);
        $requestQuantityStr = (string) $requestQuantity;
        $refundableAmount = bcmul($unitPrice, $requestQuantityStr, 2);

        // 单个商品验证
        $errors = [];
        $canRefund = true;

        if ($requestQuantity > $maxRefundableQuantity) {
            $errors[] = "申请数量({$requestQuantity})超过可退数量({$maxRefundableQuantity})";
            $canRefund = false;
        }

        if ($requestQuantity <= 0) {
            $errors[] = '申请数量必须大于0';
            $canRefund = false;
        }

        $isValid = $orderProduct->isValid();
        if (true !== $isValid) {
            $errors[] = '商品状态无效，不支持退款';
            $canRefund = false;
        }

        return new RefundCalculationItem(
            orderProductId: (string) $orderProduct->getId(),
            productName: $orderProduct->getSpu()?->getTitle() ?? '未知商品',
            skuName: $orderProduct->getSku()?->getGtin() ?? '',
            mainThumb: $orderProduct->getSku()?->getMainThumb() ?? '',
            orderQuantity: $orderProduct->getQuantity(),
            refundedQuantity: $refundedQuantity,
            maxRefundableQuantity: $maxRefundableQuantity,
            requestQuantity: $requestQuantity,
            unitPrice: $unitPrice,
            refundableAmount: $refundableAmount,
            totalRefundedAmount: $totalRefundedAmount,
            canRefund: $canRefund,
            errors: $errors
        );
    }

    private function calculateUnitPrice(OrderProduct $orderProduct): string
    {
        // 从商品的价格集合中获取实付单价
        foreach ($orderProduct->getPrices() as $price) {
            if ('CNY' === $price->getCurrency()) {
                $totalPrice = $price->getMoney() ?? '0.00';

                return bcdiv((string) $totalPrice, (string) $orderProduct->getQuantity(), 2);
            }
        }

        return '0.00';
    }

    private function validateOverallRules(Contract $contract): bool
    {
        // 检查订单状态是否允许退款
        $allowedStates = ['received', 'shipped', 'paid'];
        if (!in_array($contract->getState()->value, $allowedStates, true)) {
            return false;
        }

        // 检查退款时限（30天）
        $maxDaysValue = $_ENV['AFTERSALES_MAX_DAYS'] ?? '30';
        $maxDays = is_numeric($maxDaysValue) ? (int) $maxDaysValue : 30;
        $orderDate = $contract->getCreateTime();
        if (null !== $orderDate) {
            $deadline = $orderDate->modify("+{$maxDays} days");
            $now = new \DateTimeImmutable();
            if ($now > $deadline) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
    private function getRefundRules(Contract $contract): array
    {
        $rules = [];
        $maxDaysValue = $_ENV['AFTERSALES_MAX_DAYS'] ?? '30';
        $maxDays = is_numeric($maxDaysValue) ? (int) $maxDaysValue : 30;
        $rules[] = "订单完成后{$maxDays}天内可申请退款";
        $rules[] = '商品必须处于有效状态';
        $rules[] = '申请数量不能超过可退数量';

        return $rules;
    }
}
