<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 退货状态枚举
 */
enum ReturnStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = 'PENDING';
    case SHIPPED = 'SHIPPED';
    case IN_TRANSIT = 'IN_TRANSIT';
    case RECEIVED = 'RECEIVED';
    case INSPECTED = 'INSPECTED';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::SHIPPED => '已发货',
            self::IN_TRANSIT => '运送中',
            self::RECEIVED => '已收到',
            self::INSPECTED => '已检验',
            self::REJECTED => '已拒绝',
            self::CANCELLED => '已取消',
        };
    }
}
