<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Exception\InvalidEventTypeException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidEventTypeException::class)]
class InvalidEventTypeExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $eventType = 'invalid_event';
        $exception = new InvalidEventTypeException($eventType);

        $this->assertStringContainsString($eventType, $exception->getMessage());
        $this->assertInstanceOf(InvalidEventTypeException::class, $exception);
    }

    public function testExceptionWithCustomMessage(): void
    {
        $customMessage = '自定义错误消息';
        $exception = new InvalidEventTypeException($customMessage, 1001);

        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame(1001, $exception->getCode());
    }
}
