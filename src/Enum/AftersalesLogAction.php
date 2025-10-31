<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 售后日志操作枚举
 */
enum AftersalesLogAction: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case CREATE = 'CREATE';
    case SUBMIT = 'SUBMIT';
    case MODIFY = 'MODIFY';
    case CANCEL = 'CANCEL';
    case APPROVE = 'APPROVE';
    case REJECT = 'REJECT';
    case AUTO_APPROVE = 'AUTO_APPROVE';
    case AUTO_REJECT = 'AUTO_REJECT';
    case SHIP_RETURN = 'SHIP_RETURN';
    case RECEIVE_RETURN = 'RECEIVE_RETURN';
    case INSPECT_RETURN = 'INSPECT_RETURN';
    case SHIP_EXCHANGE = 'SHIP_EXCHANGE';
    case RECEIVE_EXCHANGE = 'RECEIVE_EXCHANGE';
    case REQUEST_REFUND = 'REQUEST_REFUND';
    case PROCESS_REFUND = 'PROCESS_REFUND';
    case COMPLETE_REFUND = 'COMPLETE_REFUND';
    case FAIL_REFUND = 'FAIL_REFUND';
    case TIMEOUT_PROCESS = 'TIMEOUT_PROCESS';
    case STATE_CHANGE = 'STATE_CHANGE';
    case SYSTEM_UPDATE = 'SYSTEM_UPDATE';
    case SYSTEM_SYNC = 'SYSTEM_SYNC';
    case STATUS_CHANGE = 'STATUS_CHANGE';
    case ADD_REMARK = 'ADD_REMARK';
    case COMPLETE = 'COMPLETE';
    case MODIFY_INFO = 'MODIFY_INFO';
    case MODIFY_REFUND_AMOUNT = 'MODIFY_REFUND_AMOUNT';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATE => 'CREATE',
            self::SUBMIT => 'SUBMIT',
            self::MODIFY => 'MODIFY',
            self::CANCEL => 'CANCEL',
            self::APPROVE => 'APPROVE',
            self::REJECT => 'REJECT',
            self::AUTO_APPROVE => 'AUTO_APPROVE',
            self::AUTO_REJECT => 'AUTO_REJECT',
            self::SHIP_RETURN => 'SHIP_RETURN',
            self::RECEIVE_RETURN => 'RECEIVE_RETURN',
            self::INSPECT_RETURN => 'INSPECT_RETURN',
            self::SHIP_EXCHANGE => 'SHIP_EXCHANGE',
            self::RECEIVE_EXCHANGE => 'RECEIVE_EXCHANGE',
            self::REQUEST_REFUND => 'REQUEST_REFUND',
            self::PROCESS_REFUND => 'PROCESS_REFUND',
            self::COMPLETE_REFUND => 'COMPLETE_REFUND',
            self::FAIL_REFUND => 'FAIL_REFUND',
            self::TIMEOUT_PROCESS => 'TIMEOUT_PROCESS',
            self::STATE_CHANGE => 'STATE_CHANGE',
            self::SYSTEM_UPDATE => 'SYSTEM_UPDATE',
            self::SYSTEM_SYNC => 'SYSTEM_SYNC',
            self::STATUS_CHANGE => 'STATUS_CHANGE',
            self::ADD_REMARK => 'ADD_REMARK',
            self::COMPLETE => 'COMPLETE',
            self::MODIFY_INFO => 'MODIFY_INFO',
            self::MODIFY_REFUND_AMOUNT => 'MODIFY_REFUND_AMOUNT',
        };
    }
}
