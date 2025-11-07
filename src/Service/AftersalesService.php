<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesLog;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesStatus;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Exception\GeneralAftersalesException;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Yiisoft\Json\Json;

/**
 * 售后服务 - 核心业务逻辑处理
 */
readonly class AftersalesService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AftersalesRepository $aftersalesRepository,
        private DataValidationService $validationService,
        private EventDispatcherService $eventDispatcherService,
        private Security $security,
    ) {
    }

    /**
     * 使用DTO创建售后申请 - 适应新的单商品售后模型
     */
    /**
     * @param ProductDataDTO $productData 单个商品数据
     * @param array<string> $proofImages
     */
    public function create(
        OrderDataDTO $orderData,
        ProductDataDTO $productData,
        string $orderProductId,
        int $quantity,
        AftersalesType $aftersalesType,
        RefundReason $reason,
        ?string $description = null,
        array $proofImages = [],
        ?UserInterface $user = null,
    ): Aftersales {
        // 验证数据
        $errors = $this->validationService->validateAftersalesData(
            $orderData,
            $productData,
            $orderProductId,
            $quantity,
            $aftersalesType,
            $reason
        );

        if ([] !== $errors) {
            throw new GeneralAftersalesException('数据验证失败: ' . implode(', ', $errors));
        }

        try {
            $this->entityManager->beginTransaction();

            // 创建售后申请
            $aftersales = new Aftersales();
            $aftersales->setReferenceNumber($orderData->orderNumber);
            $aftersales->setType($aftersalesType);
            $aftersales->setReason($reason);
            $aftersales->setDescription($description);
            $aftersales->setProofImages($proofImages);

            // 设置用户信息
            if (null !== $user) {
                $aftersales->setUser($user);
            }

            // 设置状态为待审核
            $aftersales->setState(AftersalesState::PENDING_APPROVAL);

            // 直接设置商品信息到实体中
            $aftersales->setOrderProductId($orderProductId);
            $aftersales->setProductId($productData->productId);
            $aftersales->setSkuId($productData->skuId);
            $aftersales->setProductName($productData->productName);
            $aftersales->setSkuName($productData->skuName);
            $aftersales->setQuantity($quantity);
            $aftersales->setOriginalPrice((string) $productData->originalPrice);
            $aftersales->setPaidPrice((string) $productData->paidPrice);

            // 计算退款金额：paidPrice已经是总价，无需再乘以数量
            $refundAmount = $productData->paidPrice;
            $aftersales->setRefundAmount((string) $refundAmount);

            // 设置原始和实际退款金额
            $aftersales->setOriginalRefundAmount((string) $refundAmount);
            $aftersales->setActualRefundAmount((string) $refundAmount);

            // 设置商品快照数据
            $productSnapshot = [
                'orderQuantity' => $productData->orderQuantity,
                'attributes' => $productData->attributes,
                'originalPrice' => $productData->originalPrice,
                'paidPrice' => $productData->paidPrice,
                'discountAmount' => $productData->discountAmount,
                'productImages' => $productData->productImages,
                'productMainImage' => $productData->productMainImage,
                'skuImages' => $productData->skuImages,
                'skuMainImage' => $productData->skuMainImage,
                'productSubtitle' => $productData->productSubtitle,
                'skuSpecs' => $productData->skuSpecs,
            ];
            $aftersales->setProductSnapshot($productSnapshot);

            // 保存到数据库
            $this->entityManager->persist($aftersales);
            $this->entityManager->flush();

            $this->entityManager->commit();

            // 分发创建事件
            $this->eventDispatcherService->dispatchAftersalesCreated(
                $aftersales,
                $orderData->toArray(),
                $productData->toArray(),
                [
                    'order_product_id' => $orderProductId,
                    'quantity' => $quantity,
                    'aftersales_type' => $aftersalesType,
                    'reason' => $reason->value,
                ]
            );

            return $aftersales;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw new GeneralAftersalesException('创建售后申请失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 使用数组创建售后申请 - 单商品模式
     *
     * @param array<string, mixed> $orderData
     * @param array<string, mixed> $productData 单个商品数据
     * @param array<string> $proofImages
     */
    public function createFromArray(
        array $orderData,
        array $productData,
        string $orderProductId,
        int $quantity,
        AftersalesType $aftersalesType,
        RefundReason $reason,
        ?string $description = null,
        array $proofImages = [],
        ?UserInterface $user = null,
    ): Aftersales {
        $orderDTO = OrderDataDTO::fromArray($orderData);
        $productDTO = ProductDataDTO::fromArray($productData);

        return $this->create(
            $orderDTO,
            $productDTO,
            $orderProductId,
            $quantity,
            $aftersalesType,
            $reason,
            $description,
            $proofImages,
            $user
        );
    }

    /**
     * 根据引用号查找售后申请
     */
    /**
     * @return array<Aftersales>
     */
    public function findByReferenceNumber(string $referenceNumber): array
    {
        return $this->aftersalesRepository->findBy(['referenceNumber' => $referenceNumber]);
    }

    /**
     * 获取售后详情（包含快照数据）
     */
    public function getAftersalesWithSnapshots(string $aftersalesId): ?Aftersales
    {
        $aftersales = $this->aftersalesRepository->find($aftersalesId);

        if (null === $aftersales) {
            return null;
        }

        // 新的实体结构无需加载额外的关联数据，快照数据已内嵌
        return $aftersales;
    }

    /**
     * 计算售后申请的总退款金额
     */
    public function calculateTotalRefundAmount(Aftersales $aftersales): float
    {
        return $aftersales->getTotalRefundAmount();
    }

    /**
     * 检查是否可以创建售后申请
     */
    public function canCreateAftersales(OrderDataDTO $orderData): bool
    {
        // 检查是否在售后时限内
        $maxDaysValue = $_ENV['AFTERSALES_MAX_DAYS'] ?? '30';
        $maxDays = is_numeric($maxDaysValue) ? (int) $maxDaysValue : 30;
        $orderDate = new \DateTime($orderData->orderCreateTime->format('Y-m-d H:i:s'));
        $deadline = $orderDate->modify("+{$maxDays} days");
        $now = new \DateTime();

        return $now <= $deadline;
    }

    /**
     * 批量处理售后申请
     */
    /**
     * @param array<string> $aftersalesIds
     * @return array<string, array<string, mixed>>
     */
    public function batchProcess(array $aftersalesIds, string $action, ?string $reason = null): array
    {
        $results = [];

        foreach ($aftersalesIds as $id) {
            try {
                $aftersales = $this->aftersalesRepository->find($id);
                if (null === $aftersales) {
                    $results[$id] = ['success' => false, 'error' => '售后申请不存在'];
                    continue;
                }

                // 根据动作执行对应操作
                switch ($action) {
                    case 'approve':
                        // 审批通过逻辑
                        $results[$id] = ['success' => true, 'message' => '审批通过'];
                        break;

                    case 'reject':
                        // 审批拒绝逻辑
                        $results[$id] = ['success' => true, 'message' => '审批拒绝'];
                        break;

                    default:
                        $results[$id] = ['success' => false, 'error' => '不支持的操作'];
                }
            } catch (\Throwable $e) {
                $results[$id] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * 修改退款金额
     */
    public function modifyRefundAmount(string $aftersalesId, string $newAmount, ?string $reason = null): Aftersales
    {
        $aftersales = $this->aftersalesRepository->find($aftersalesId);
        if (null === $aftersales) {
            throw new GeneralAftersalesException('售后申请不存在');
        }

        // 检查是否可以修改退款金额
        if (!$aftersales->canModifyRefundAmount()) {
            throw new GeneralAftersalesException('当前状态不允许修改退款金额');
        }

        try {
            $this->entityManager->beginTransaction();

            // 记录原始退款金额用于日志
            $originalAmount = $aftersales->getActualRefundAmount();

            // 修改退款金额
            $aftersales->modifyRefundAmount($newAmount, $reason);

            // 保存更改
            $this->entityManager->persist($aftersales);
            $this->entityManager->flush();

            // 记录修改日志
            $this->logRefundAmountModification($aftersales, $originalAmount, $newAmount, $reason);

            $this->entityManager->commit();

            return $aftersales;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw new GeneralAftersalesException('修改退款金额失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 记录退款金额修改日志
     */
    private function logRefundAmountModification(Aftersales $aftersales, ?string $originalAmount, string $newAmount, ?string $reason): void
    {
        $user = $this->security->getUser();
        $operatorType = 'system';
        $operatorId = null;

        if ($user instanceof UserInterface) {
            $operatorType = 'admin';
            $operatorId = $user->getUserIdentifier();
        }

        $log = new AftersalesLog();
        $log->setAftersales($aftersales);
        $log->setAction(AftersalesLogAction::MODIFY_REFUND_AMOUNT);
        $log->setOperatorType($operatorType);
        $log->setOperatorId($operatorId);

        // 构建日志内容
        $logContent = [
            'original_amount' => $originalAmount,
            'new_amount' => $newAmount,
            'reason' => $reason,
            'operator' => $user instanceof UserInterface ? $user->getUserIdentifier() : 'system',
        ];

        $log->setContent(Json::encode($logContent));
        $log->setRemark(null !== $reason ? "修改退款金额: {$reason}" : '修改退款金额');

        $this->entityManager->persist($log);
    }
}
