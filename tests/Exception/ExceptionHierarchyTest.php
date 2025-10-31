<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Exception\AftersalesException;
use Tourze\OrderRefundBundle\Exception\InvalidOrderDataException;
use Tourze\OrderRefundBundle\Exception\InvalidProductDataException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesException::class)]
class ExceptionHierarchyTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return AftersalesException::class;
    }

    protected function getParentExceptionClass(): string
    {
        return \Exception::class;
    }

    public function testExceptionHierarchy(): void
    {
        $this->assertInstanceOf(
            AftersalesException::class,
            new InvalidOrderDataException()
        );

        $this->assertInstanceOf(
            AftersalesException::class,
            new InvalidProductDataException()
        );
    }
}
