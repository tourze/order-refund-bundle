<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesLog;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;
use Tourze\OrderRefundBundle\Entity\ExchangeOrder;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Entity\RefundOrder;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;

/**
 * 订单退款管理菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('订单退款管理')) {
            $item->addChild('订单退款管理');
        }

        $orderRefundMenu = $item->getChild('订单退款管理');
        if (null === $orderRefundMenu) {
            return;
        }

        // 核心售后管理
        $orderRefundMenu->addChild('售后申请管理')
            ->setUri($this->linkGenerator->getCurdListPage(Aftersales::class))
            ->setAttribute('icon', 'fas fa-undo-alt')
        ;

        // 退款相关
        $orderRefundMenu->addChild('退款订单管理')
            ->setUri($this->linkGenerator->getCurdListPage(RefundOrder::class))
            ->setAttribute('icon', 'fas fa-money-check-alt')
        ;

        // 退货相关
        $orderRefundMenu->addChild('退货订单管理')
            ->setUri($this->linkGenerator->getCurdListPage(ReturnOrder::class))
            ->setAttribute('icon', 'fas fa-shipping-fast')
        ;

        $orderRefundMenu->addChild('退货地址管理')
            ->setUri($this->linkGenerator->getCurdListPage(ReturnAddress::class))
            ->setAttribute('icon', 'fas fa-map-marker-alt')
        ;

        // 换货相关
        $orderRefundMenu->addChild('换货订单管理')
            ->setUri($this->linkGenerator->getCurdListPage(ExchangeOrder::class))
            ->setAttribute('icon', 'fas fa-exchange-alt')
        ;

        // 数据快照管理
        if (null === $orderRefundMenu->getChild('数据快照管理')) {
            $orderRefundMenu->addChild('数据快照管理');
        }

        $snapshotMenu = $orderRefundMenu->getChild('数据快照管理');
        if (null !== $snapshotMenu) {
            $snapshotMenu->addChild('订单快照')
                ->setUri($this->linkGenerator->getCurdListPage(AftersalesOrder::class))
                ->setAttribute('icon', 'fas fa-copy')
            ;
        }

        // 日志审计
        $orderRefundMenu->addChild('售后操作日志')
            ->setUri($this->linkGenerator->getCurdListPage(AftersalesLog::class))
            ->setAttribute('icon', 'fas fa-history')
        ;

        // 基础配置
        if (null === $orderRefundMenu->getChild('基础配置')) {
            $orderRefundMenu->addChild('基础配置');
        }

        $configMenu = $orderRefundMenu->getChild('基础配置');
        if (null !== $configMenu) {
            $configMenu->addChild('快递公司管理')
                ->setUri($this->linkGenerator->getCurdListPage(ExpressCompany::class))
                ->setAttribute('icon', 'fas fa-truck')
            ;
        }
    }
}
