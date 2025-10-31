<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Tourze\OrderContracts\Event\CheckOrderRefundableEvent;
use Tourze\OrderContracts\Event\GetOrderDetailEvent;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;

/**
 * 订单事件监听器
 */
class OrderEventListener
{
    public function __construct(
        private readonly AftersalesRepository $aftersalesRepository,
    ) {
    }

    #[AsEventListener]
    public function onGetOrderDetail(GetOrderDetailEvent $event): void
    {
        $orderId = $event->getOrderId();
        if ('' === $orderId) {
            return;
        }

        // 查询该订单的所有售后单状态，按产品ID分组
        $aftersalesStatusByProduct = $this->aftersalesRepository->findAftersalesStatusByReferenceNumber($orderId);

        // 将售后状态信息设置到事件中
        $event->setAftersalesStatus($aftersalesStatusByProduct);
    }

    #[AsEventListener]
    public function onCheckOrderRefundable(CheckOrderRefundableEvent $event): void
    {
        $orderId = $event->getOrderId();
        $orderProducts = $event->getOrderProducts();

        if ('' === $orderId || [] === $orderProducts) {
            $event->setCanRefund(false);

            return;
        }

        // 查询该订单的所有售后单状态，按产品ID分组
        $aftersalesStatusByProduct = $this->aftersalesRepository->findAftersalesStatusByReferenceNumber($orderId);

        // 检查每个商品是否都已经发起了售后
        foreach ($orderProducts as $productId => $productInfo) {
            // 如果这个产品没有售后记录，说明还可以发起售后
            if (!isset($aftersalesStatusByProduct[$productId]) || [] === $aftersalesStatusByProduct[$productId]) {
                $event->setCanRefund(true);

                return;
            }
        }

        // 如果所有产品都有售后记录，则不能再发起售后
        $event->setCanRefund(false);
    }
}
