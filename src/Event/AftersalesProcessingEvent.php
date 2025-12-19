<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Event;

use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * 售后处理中事件
 */
final class AftersalesProcessingEvent extends AbstractAftersalesEvent
{
    public const NAME = 'aftersales.processing';

    /**
     * @param array<string, mixed> $processingData
     * @param array<string, mixed> $context
     */
    public function __construct(
        Aftersales $aftersales,
        private readonly string $processingType,
        private readonly array $processingData = [],
        array $context = [],
    ) {
        parent::__construct($aftersales, $context);
    }

    public function getProcessingType(): string
    {
        return $this->processingType;
    }

    /** @return array<string, mixed> */
    public function getProcessingData(): array
    {
        return $this->processingData;
    }
}
