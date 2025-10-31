<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 换货状态枚举
 */
enum ExchangeStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case RETURN_SHIPPED = 'RETURN_SHIPPED';
    case RETURN_RECEIVED = 'RETURN_RECEIVED';
    case EXCHANGE_SHIPPED = 'EXCHANGE_SHIPPED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::APPROVED => 'APPROVED',
            self::REJECTED => 'REJECTED',
            self::RETURN_SHIPPED => 'RETURN_SHIPPED',
            self::RETURN_RECEIVED => 'RETURN_RECEIVED',
            self::EXCHANGE_SHIPPED => 'EXCHANGE_SHIPPED',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已取消',
        };
    }
}
