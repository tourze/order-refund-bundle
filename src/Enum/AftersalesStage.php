<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 售后阶段枚举
 */
enum AftersalesStage: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case APPLY = 'apply';
    case AUDIT = 'audit';
    case RETURN = 'return';
    case RECEIVE = 'receive';
    case REFUND = 'refund';
    case EXCHANGE = 'exchange';
    case COMPLETE = 'complete';

    public function getLabel(): string
    {
        return match ($this) {
            self::APPLY => 'APPLY',
            self::AUDIT => 'AUDIT',
            self::RETURN => 'RETURN',
            self::RECEIVE => 'RECEIVE',
            self::REFUND => '退款',
            self::EXCHANGE => 'EXCHANGE',
            self::COMPLETE => 'COMPLETE',
        };
    }
}
