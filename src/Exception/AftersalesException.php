<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Exception;

/**
 * 售后业务异常
 */
abstract class AftersalesException extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
