<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AftersalesStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待审核',
            self::APPROVED => '已审核',
            self::REJECTED => '已拒绝',
            self::PROCESSING => '处理中',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已取消',
        };
    }
}
