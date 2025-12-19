<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Aftersales\GetAftersalesDetailParam;

#[CoversClass(GetAftersalesDetailParam::class)]
final class GetAftersalesDetailParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new GetAftersalesDetailParam(id: 1);
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithIntId(): void
    {
        $param = new GetAftersalesDetailParam(id: 123);
        $this->assertSame(123, $param->id);
    }

    public function testConstructorWithStringId(): void
    {
        $param = new GetAftersalesDetailParam(id: 'abc-123');
        $this->assertSame('abc-123', $param->id);
    }
}
