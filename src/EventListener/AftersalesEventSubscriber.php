<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\EventListener;

use Monolog\Attribute\WithMonologChannel;
use OrderCoreBundle\Enum\AftersaleStatus;
use OrderCoreBundle\Repository\ContractRepository;
use OrderCoreBundle\Repository\OrderProductRepository;
use OrderCoreBundle\Service\OrderService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Event\AftersalesCancelledEvent;
use Tourze\OrderRefundBundle\Event\AftersalesCompletedEvent;
use Tourze\OrderRefundBundle\Event\AftersalesCreatedEvent;
use Tourze\OrderRefundBundle\Event\AftersalesStatusChangedEvent;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\ProductCoreBundle\Service\SkuServiceInterface;
use Tourze\StockManageBundle\Entity\StockLog;
use Tourze\StockManageBundle\Enum\StockChange;
use Tourze\StockManageBundle\Service\StockServiceInterface;

/**
 * 售后事件订阅器
 */
#[WithMonologChannel(channel: 'order_refund')]
final class AftersalesEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AftersalesRepository $aftersalesRepository,
        private readonly ContractRepository $contractRepository,
        private readonly OrderProductRepository $orderProductRepository,
        private readonly ?StockServiceInterface $stockService = null,
        private readonly ?SkuServiceInterface $skuService = null,
        private readonly ?OrderService $orderService = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AftersalesCreatedEvent::NAME => 'onAftersalesCreated',
            AftersalesStatusChangedEvent::NAME => 'onAftersalesStatusChanged',
            AftersalesCompletedEvent::NAME => 'onAftersalesCompleted',
            AftersalesCancelledEvent::NAME => 'onAftersalesCancelled',
        ];
    }

    public function onAftersalesCreated(AftersalesCreatedEvent $event): void
    {
        $aftersales = $event->getAftersales();

        $this->logger->info('售后申请已创建', [
            'aftersales_id' => $aftersales->getId(),
            'reference_number' => $aftersales->getReferenceNumber(),
            'aftersales_type' => $aftersales->getType(),
            'reason' => $aftersales->getReason(),
            'context' => $event->getContext(),
        ]);

        // 这里可以添加其他逻辑，如发送通知、创建工单等
        $orderProduct = $this->orderProductRepository->findOneBy(['id' => $event->getAftersales()->getOrderProductId()]);
        if (null !== $orderProduct) {
            $orderProduct->setAftersaleStatus(AftersaleStatus::UNDER_REVIEW);
            $this->orderProductRepository->save($orderProduct);
        }
    }

    public function onAftersalesStatusChanged(AftersalesStatusChangedEvent $event): void
    {
        $aftersales = $event->getAftersales();

        $this->logger->info('售后状态已变更', [
            'aftersales_id' => $aftersales->getId(),
            'reference_number' => $aftersales->getReferenceNumber(),
            'old_status' => $event->getOldStatus(),
            'new_status' => $event->getNewStatus(),
            'action' => $event->getAction(),
            'context' => $event->getContext(),
        ]);

        // 根据状态变更执行相应的业务逻辑
        $this->handleStatusChangeLogic($event);
    }

    public function onAftersalesCompleted(AftersalesCompletedEvent $event): void
    {
        $aftersales = $event->getAftersales();
        $completionData = $event->getCompletionData();

        $this->logger->info('售后申请已完成', [
            'aftersales_id' => $aftersales->getId(),
            'reference_number' => $aftersales->getReferenceNumber(),
            'total_refund_amount' => $event->getTotalRefundAmount(),
            'completed_items' => count($event->getCompletedItems()),
            'context' => $event->getContext(),
        ]);

        // 完成后的业务逻辑，如库存恢复、积分处理等
        $this->handleCompletionLogic($event);

        // 检查订单是否所有商品都已完成售后
        $this->checkAndUpdateOrderStatus($event);
    }

    public function onAftersalesCancelled(AftersalesCancelledEvent $event): void
    {
        $aftersales = $event->getAftersales();

        $this->logger->info('售后申请已取消', [
            'aftersales_id' => $aftersales->getId(),
            'reference_number' => $aftersales->getReferenceNumber(),
            'cancel_reason' => $event->getCancelReason(),
            'operator' => $event->getOperator(),
            'context' => $event->getContext(),
        ]);

        // 取消后的清理工作
        $this->handleCancellationLogic($event);
    }

    private function handleStatusChangeLogic(AftersalesStatusChangedEvent $event): void
    {
        $newStatus = $event->getNewStatus();

        match ($newStatus) {
            'approved' => $this->handleApproval($event),
            'rejected' => $this->handleRejection($event),
            'processing' => $this->handleProcessingStart($event),
            'completed' => $this->handleCompletion($event),
            default => null,
        };
    }

    private function handleApproval(AftersalesStatusChangedEvent $event): void
    {
        // 审核通过后的逻辑
        $this->logger->debug('处理售后审核通过逻辑');
        $orderProduct = $this->orderProductRepository->findOneBy(['id' => $event->getAftersales()->getOrderProductId()]);
        if (null !== $orderProduct) {
            $orderProduct->setAftersaleStatus(AftersaleStatus::APPROVED);
            $this->orderProductRepository->save($orderProduct);
        }
    }

    private function handleRejection(AftersalesStatusChangedEvent $event): void
    {
        // 审核拒绝后的逻辑
        $this->logger->debug('处理售后审核拒绝逻辑');
        $orderProduct = $this->orderProductRepository->findOneBy(['id' => $event->getAftersales()->getOrderProductId()]);
        if (null !== $orderProduct) {
            $orderProduct->setAftersaleStatus(AftersaleStatus::NORMAL);
            $this->orderProductRepository->save($orderProduct);
        }
    }

    private function handleProcessingStart(AftersalesStatusChangedEvent $event): void
    {
        // 开始处理后的逻辑
        $this->logger->debug('处理售后开始处理逻辑');
    }

    private function handleCompletion(AftersalesStatusChangedEvent $event): void
    {
        // 完成后的逻辑
        $this->logger->debug('处理售后完成逻辑');

        $orderProduct = $this->orderProductRepository->findOneBy(['id' => $event->getAftersales()->getOrderProductId()]);
        if (null !== $orderProduct) {
            $orderProduct->setAftersaleStatus(AftersaleStatus::COMPLETED);
            $this->orderProductRepository->save($orderProduct);
        }
    }

    private function handleCompletionLogic(AftersalesCompletedEvent $event): void
    {
        // 售后完成的业务逻辑
        $this->logger->debug('执行售后完成的业务逻辑', [
            'total_refund' => $event->getTotalRefundAmount(),
        ]);

        // 处理库存返还
        $this->handleStockReturn($event);
    }

    /**
     * 检查订单状态并更新
     */
    private function checkAndUpdateOrderStatus(AftersalesCompletedEvent $event): void
    {
        $aftersales = $event->getAftersales();
        $orderNumber = $aftersales->getReferenceNumber();

        if (null === $orderNumber) {
            $this->logger->warning('售后单没有关联订单号，无法检查订单状态', [
                'aftersales_id' => $aftersales->getId(),
            ]);

            return;
        }

        $contract = $this->contractRepository->findOneBy(['sn' => $orderNumber]);
        if (null === $contract) {
            $this->logger->warning('未找到对应订单，无法检查售后状态', [
                'order_number' => $orderNumber,
            ]);

            return;
        }
        try {
            // 首先获取订单中的所有商品ID
            $orderProductIds = [];
            $products = $contract->getProducts();
            foreach ($products as $product) {
                $orderProductIds[] = (string) $product->getId();
            }

            if ([] === $orderProductIds) {
                $this->logger->warning('订单没有商品', [
                    'order_number' => $orderNumber,
                ]);

                return;
            }

            // 检查订单下所有商品的售后状态
            $statusCheck = $this->aftersalesRepository->checkOrderAftersalesStatus($orderNumber);

            // 验证所有商品都有对应的已完成售后记录
            $allProductsHaveCompletedAftersales = $this->validateAllProductsAftersalesCompleted(
                $orderProductIds,
                $statusCheck['details']
            );

            $this->logger->info('检查订单售后状态', [
                'order_number' => $orderNumber,
                'total_order_products' => count($orderProductIds),
                'aftersales_completed_count' => $statusCheck['completedCount'],
                'total_aftersales_count' => $statusCheck['totalAftersalesCount'],
                'all_products_aftersales_completed' => $allProductsHaveCompletedAftersales,
                'statusCheck' => $statusCheck,
            ]);

            // 只有当订单所有商品都申请售后并且都完成时，才更新订单状态
            if ($allProductsHaveCompletedAftersales) {
                $this->updateOrderStatusToAftersalesSuccess($orderNumber, $statusCheck);
            }
        } catch (\Exception $e) {
            $this->logger->error('检查订单售后状态时出错', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 更新订单状态为售后成功
     * @param array<string, mixed> $statusCheckResult
     */
    private function updateOrderStatusToAftersalesSuccess(string $orderNumber, array $statusCheckResult): void
    {
        if (null === $this->orderService) {
            $this->logger->warning('OrderService不可用，无法更新订单状态', [
                'order_number' => $orderNumber,
            ]);

            return;
        }

        try {
            $this->orderService->updateOrderStatusToAftersalesSuccess($orderNumber);
        } catch (\Exception $e) {
            $this->logger->error('更新订单状态时发生异常', [
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function handleCancellationLogic(AftersalesCancelledEvent $event): void
    {
        // 售后取消的清理逻辑
        $this->logger->debug('执行售后取消的清理逻辑', [
            'reason' => $event->getCancelReason(),
        ]);
    }

    /**
     * 验证所有商品都有对应的已完成售后记录
     *
     * @param array<string> $orderProductIds 订单中所有商品ID
     * @param array<string, array<string, mixed>> $aftersalesDetails 售后详情
     */
    private function validateAllProductsAftersalesCompleted(array $orderProductIds, array $aftersalesDetails): bool
    {
        foreach ($orderProductIds as $productId) {
            // 检查商品是否有售后记录
            if (!isset($aftersalesDetails[$productId])) {
                $this->logger->debug('商品没有售后记录', [
                    'product_id' => $productId,
                ]);

                return false;
            }

            $productAftersales = $aftersalesDetails[$productId];

            // 检查商品售后是否已完成
            if (true !== ($productAftersales['hasCompleted'] ?? false)) {
                $this->logger->debug('商品售后未完成', [
                    'product_id' => $productId,
                    'states' => $productAftersales['states'],
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * 处理库存返还
     */
    private function handleStockReturn(AftersalesCompletedEvent $event): void
    {
        // 检查依赖服务是否可用
        if (null === $this->stockService || null === $this->skuService) {
            $this->logger->warning('库存服务或SKU服务不可用，跳过库存返还', [
                'aftersales_id' => $event->getAftersales()->getId(),
                'stock_service_available' => null !== $this->stockService,
                'sku_service_available' => null !== $this->skuService,
            ]);

            return;
        }

        $aftersales = $event->getAftersales();
        $aftersalesType = $aftersales->getType();

        // 只有需要退回商品的售后类型才需要返还库存
        if (!$this->shouldReturnStock($aftersalesType)) {
            $this->logger->info('售后类型无需返还库存', [
                'aftersales_id' => $aftersales->getId(),
                'aftersales_type' => $aftersalesType?->value,
            ]);

            return;
        }

        try {
            // 获取SKU信息
            $skuId = $aftersales->getSkuId();
            if (null === $skuId) {
                $this->logger->error('售后记录缺少SKU信息，无法返还库存', [
                    'aftersales_id' => $aftersales->getId(),
                ]);

                return;
            }

            $sku = $this->skuService->findById($skuId);
            if (null === $sku) {
                $this->logger->error('未找到对应的SKU，无法返还库存', [
                    'aftersales_id' => $aftersales->getId(),
                    'sku_id' => $skuId,
                ]);

                return;
            }

            $quantity = $aftersales->getQuantity();
            if (null === $quantity || $quantity <= 0) {
                $this->logger->error('售后数量无效，无法返还库存', [
                    'aftersales_id' => $aftersales->getId(),
                    'quantity' => $quantity,
                ]);

                return;
            }

            // 创建库存返还日志
            $stockLog = new StockLog();
            $stockLog->setType(StockChange::RETURN);
            $stockLog->setQuantity($quantity);
            $stockLog->setSku($sku);
            $stockLog->setRemark(sprintf(
                '售后完成库存返还 - 售后ID: %s, 类型: %s',
                $aftersales->getId(),
                $aftersalesType?->getLabel() ?? '未知'
            ));

            // 执行库存返还
            $this->stockService->process($stockLog);

            $this->logger->info('售后完成，库存返还成功', [
                'aftersales_id' => $aftersales->getId(),
                'sku_id' => $skuId,
                'quantity' => $quantity,
                'aftersales_type' => $aftersalesType?->value,
            ]);
        } catch (\Throwable $e) {
            // 库存返还失败不应影响售后完成流程，只记录错误
            $this->logger->error('库存返还处理失败', [
                'aftersales_id' => $aftersales->getId(),
                'sku_id' => $aftersales->getSkuId(),
                'quantity' => $aftersales->getQuantity(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 判断售后类型是否需要返还库存
     */
    private function shouldReturnStock(?AftersalesType $type): bool
    {
        if (null === $type) {
            return false;
        }

        // 只有退回商品的售后类型才需要返还库存
        return match ($type) {
            AftersalesType::RETURN_REFUND => true,  // 退货退款
            AftersalesType::EXCHANGE => true,       // 换货（退回原商品）
            AftersalesType::REFUND_ONLY => false,   // 仅退款，不退回商品
            AftersalesType::CANCEL => false,        // 取消订单
            AftersalesType::RESEND => false,        // 补发，不涉及退回
        };
    }
}
