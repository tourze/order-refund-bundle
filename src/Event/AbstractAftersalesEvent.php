<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * 售后事件基类
 */
abstract class AbstractAftersalesEvent extends Event
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private readonly Aftersales $aftersales,
        private readonly array $context = [],
    ) {
    }

    public function getAftersales(): Aftersales
    {
        return $this->aftersales;
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context[$key] ?? $default;
    }
}
