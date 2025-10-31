<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 支付方式枚举
 */
enum PaymentMethod: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case ALIPAY = 'ALIPAY';
    case WECHAT_PAY = 'WECHAT_PAY';
    case UNION_PAY = 'UNION_PAY';
    case CREDIT_CARD = 'CREDIT_CARD';
    case BANK_TRANSFER = 'BANK_TRANSFER';
    case BALANCE = 'BALANCE';
    case POINTS = 'POINTS';
    case COUPON = 'COUPON';

    public function getLabel(): string
    {
        return match ($this) {
            self::ALIPAY => 'ALIPAY',
            self::WECHAT_PAY => 'WECHAT_PAY',
            self::UNION_PAY => 'UNION_PAY',
            self::CREDIT_CARD => 'CREDIT_CARD',
            self::BANK_TRANSFER => 'BANK_TRANSFER',
            self::BALANCE => 'BALANCE',
            self::POINTS => 'POINTS',
            self::COUPON => '优惠券',
        };
    }
}
