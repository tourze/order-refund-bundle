<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Exception\AftersalesException;
use Tourze\OrderRefundBundle\Exception\GeneralAftersalesException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(GeneralAftersalesException::class)]
class GeneralAftersalesExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return GeneralAftersalesException::class;
    }

    protected function getParentExceptionClass(): string
    {
        return AftersalesException::class;
    }

    public function testGeneralAftersalesExceptionCreation(): void
    {
        $exception = new GeneralAftersalesException();

        self::assertSame('', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
    }

    public function testGeneralAftersalesExceptionWithMessage(): void
    {
        $message = '通用售后业务异常';
        $exception = new GeneralAftersalesException($message);

        self::assertSame($message, $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
    }

    public function testGeneralAftersalesExceptionWithMessageAndCode(): void
    {
        $message = '售后订单状态异常';
        $code = 4001;
        $exception = new GeneralAftersalesException($message, $code);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($code, $exception->getCode());
        self::assertNull($exception->getPrevious());
    }

    public function testGeneralAftersalesExceptionWithPreviousException(): void
    {
        $previousMessage = '底层异常';
        $previous = new \RuntimeException($previousMessage);

        $message = '售后业务处理失败';
        $code = 5000;
        $exception = new GeneralAftersalesException($message, $code, $previous);

        self::assertSame($message, $exception->getMessage());
        self::assertSame($code, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame($previousMessage, $exception->getPrevious()->getMessage());
    }

    public function testInheritanceFromAftersalesException(): void
    {
        $exception = new GeneralAftersalesException('测试异常');

        // 验证异常的基本属性
        self::assertSame('测试异常', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
    }
}
