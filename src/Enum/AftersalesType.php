<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 售后类型枚举
 */
enum AftersalesType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case CANCEL = 'cancel';
    case REFUND_ONLY = 'refund_only';
    case RETURN_REFUND = 'return_refund';
    case EXCHANGE = 'exchange';
    case RESEND = 'resend';

    public function getLabel(): string
    {
        return match ($this) {
            self::CANCEL => '订单待支付状态下取消订单',
            self::REFUND_ONLY => '仅退款',
            self::RETURN_REFUND => '退货退款',
            self::EXCHANGE => '退回原商品，发出新商品',
            self::RESEND => '商品破损缺件，直接补发',
        };
    }
}
