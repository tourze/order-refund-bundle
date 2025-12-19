<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Event;

use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * 售后创建事件
 */
final class AftersalesCreatedEvent extends AbstractAftersalesEvent
{
    public const NAME = 'aftersales.created';

    /**
     * @param array<string, mixed> $orderData
     * @param array<int, array<string, mixed>> $productData
     * @param array<string, mixed> $context
     */
    public function __construct(
        Aftersales $aftersales,
        private readonly array $orderData,
        private readonly array $productData,
        array $context = [],
    ) {
        parent::__construct($aftersales, $context);
    }

    /** @return array<string, mixed> */
    public function getOrderData(): array
    {
        return $this->orderData;
    }

    /** @return array<int, array<string, mixed>> */
    public function getProductData(): array
    {
        return $this->productData;
    }
}
