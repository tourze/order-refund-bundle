<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Exception\AftersalesException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesException::class)]
class AftersalesExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return AftersalesException::class;
    }

    protected function getParentExceptionClass(): string
    {
        return \Exception::class;
    }
}
