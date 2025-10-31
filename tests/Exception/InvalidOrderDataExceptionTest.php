<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Exception\InvalidOrderDataException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidOrderDataException::class)]
class InvalidOrderDataExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $field = 'order_number';
        $exception = new InvalidOrderDataException($field);

        $this->assertStringContainsString($field, $exception->getMessage());
        $this->assertInstanceOf(InvalidOrderDataException::class, $exception);
    }

    public function testExceptionWithCustomMessage(): void
    {
        $customMessage = '订单数据格式错误';
        $exception = new InvalidOrderDataException($customMessage, 2001);

        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame(2001, $exception->getCode());
    }
}
