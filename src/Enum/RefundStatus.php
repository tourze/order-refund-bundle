<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 退款状态枚举
 */
enum RefundStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::PROCESSING => '处理中',
            self::SUCCESS => '退款成功',
            self::FAILED => '退款失败',
            self::CANCELLED => '已取消',
        };
    }
}
