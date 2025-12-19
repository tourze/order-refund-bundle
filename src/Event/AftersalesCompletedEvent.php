<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Event;

use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * 售后完成事件
 */
final class AftersalesCompletedEvent extends AbstractAftersalesEvent
{
    public const NAME = 'aftersales.completed';

    /**
     * @param array<string, mixed> $completionData
     * @param array<string, mixed> $context
     */
    public function __construct(
        Aftersales $aftersales,
        private readonly array $completionData = [],
        array $context = [],
    ) {
        parent::__construct($aftersales, $context);
    }

    /** @return array<string, mixed> */
    public function getCompletionData(): array
    {
        return $this->completionData;
    }

    public function getTotalRefundAmount(): float
    {
        $amount = $this->completionData['total_refund_amount'] ?? 0.0;

        return is_float($amount) || is_int($amount) ? (float) $amount : 0.0;
    }

    /** @return array<mixed> */
    public function getCompletedItems(): array
    {
        $items = $this->completionData['completed_items'] ?? [];

        return is_array($items) ? $items : [];
    }
}
