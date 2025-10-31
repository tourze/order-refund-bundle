<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Event;

use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * 售后状态变更事件
 */
class AftersalesStatusChangedEvent extends AbstractAftersalesEvent
{
    public const NAME = 'aftersales.status_changed';

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        Aftersales $aftersales,
        private readonly string $oldStatus,
        private readonly string $newStatus,
        private readonly string $action,
        array $context = [],
    ) {
        parent::__construct($aftersales, $context);
    }

    public function getOldStatus(): string
    {
        return $this->oldStatus;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
