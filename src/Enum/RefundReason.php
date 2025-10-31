<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 退款原因枚举
 */
enum RefundReason: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case UNUSED_DISCOUNT = 'unused_discount';
    case QUALITY_ISSUE = 'quality_issue';
    case PRICE_ISSUE = 'price_issue';
    case DONT_WANT = 'dont_want';
    case OUT_OF_STOCK = 'out_of_stock';
    case MISSING_ITEM = 'missing_item';
    case DELIVERY_TIMEOUT = 'delivery_timeout';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNUSED_DISCOUNT => '没用/少用优惠',
            self::QUALITY_ISSUE => '商品质量问题',
            self::PRICE_ISSUE => '商品价格问题',
            self::DONT_WANT => '不想要了',
            self::OUT_OF_STOCK => '商品缺货',
            self::MISSING_ITEM => '少件/漏发',
            self::DELIVERY_TIMEOUT => '订单配送超时',
            self::OTHER => '其他',
        };
    }

    /**
     * 判断是否支持自动审批
     */
    public function supportsAutoApproval(): bool
    {
        return match ($this) {
            self::DONT_WANT => true,
            self::UNUSED_DISCOUNT => true,
            self::QUALITY_ISSUE, self::MISSING_ITEM, self::OUT_OF_STOCK, self::DELIVERY_TIMEOUT => false,
            default => false,
        };
    }

    /**
     * 判断是否属于商家责任
     */
    public function isMerchantResponsibility(): bool
    {
        return match ($this) {
            self::QUALITY_ISSUE, self::MISSING_ITEM, self::OUT_OF_STOCK, self::DELIVERY_TIMEOUT => true,
            self::DONT_WANT, self::UNUSED_DISCOUNT, self::PRICE_ISSUE => false,
            default => false,
        };
    }
}
