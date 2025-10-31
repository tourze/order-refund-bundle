<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 售后状态枚举
 */
enum AftersalesState: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PENDING_MODIFICATION = 'pending_modification';
    case PENDING_RETURN = 'pending_return';
    case PENDING_RECEIVE = 'pending_receive';
    case PENDING_REFUND = 'pending_refund';
    case PENDING_EXCHANGE = 'pending_exchange';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case TIMEOUT = 'timeout';
    case CS_INTERVENTION = 'cs_intervention';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING_APPROVAL => '审核中',
            self::APPROVED => '已通过',
            self::REJECTED => '已拒绝',
            self::PENDING_MODIFICATION => '待修改',
            self::PENDING_RETURN => '待买家发货',
            self::PENDING_RECEIVE => '待商家收货',
            self::PENDING_REFUND => '待退款',
            self::PENDING_EXCHANGE => '待换货',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已关闭',
            self::TIMEOUT => '已超时',
            self::CS_INTERVENTION => '客服介入',
        };
    }

    public function getUserLabel(): string
    {
        return match ($this) {
            self::PENDING_APPROVAL => '审核中',
            self::APPROVED => '已通过',
            self::REJECTED => '已拒绝',
            self::PENDING_MODIFICATION => '待修改',
            self::PENDING_RETURN => '待您发货',
            self::PENDING_RECEIVE => '待商家收货',
            self::PENDING_REFUND => '待退款',
            self::PENDING_EXCHANGE => '待换货',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已关闭',
            self::TIMEOUT => '已超时',
            self::CS_INTERVENTION => '客服介入',
        };
    }
}
