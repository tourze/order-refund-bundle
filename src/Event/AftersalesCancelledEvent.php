<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Event;

use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * 售后取消事件
 */
final class AftersalesCancelledEvent extends AbstractAftersalesEvent
{
    public const NAME = 'aftersales.cancelled';

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        Aftersales $aftersales,
        private readonly string $cancelReason,
        private readonly ?string $operator = null,
        array $context = [],
    ) {
        parent::__construct($aftersales, $context);
    }

    public function getCancelReason(): string
    {
        return $this->cancelReason;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }
}
