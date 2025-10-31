<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Exception\InvalidProductDataException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidProductDataException::class)]
class InvalidProductDataExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $field = 'product_id';
        $exception = new InvalidProductDataException($field);

        $this->assertStringContainsString($field, $exception->getMessage());
        $this->assertInstanceOf(InvalidProductDataException::class, $exception);
    }

    public function testExceptionWithCustomMessage(): void
    {
        $customMessage = '商品数据格式错误';
        $exception = new InvalidProductDataException($customMessage, 3001);

        $this->assertSame($customMessage, $exception->getMessage());
        $this->assertSame(3001, $exception->getCode());
    }
}
