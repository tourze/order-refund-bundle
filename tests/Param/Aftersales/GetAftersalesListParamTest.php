<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Param\Aftersales\GetAftersalesListParam;

#[CoversClass(GetAftersalesListParam::class)]
final class GetAftersalesListParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new GetAftersalesListParam();
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testDefaultValues(): void
    {
        $param = new GetAftersalesListParam();
        $this->assertSame(1, $param->page);
        $this->assertSame(10, $param->limit);
        $this->assertNull($param->state);
        $this->assertNull($param->type);
    }

    public function testConstructorWithValues(): void
    {
        $param = new GetAftersalesListParam(
            page: 2,
            limit: 20,
            state: AftersalesState::PENDING_APPROVAL,
            type: AftersalesType::REFUND_ONLY,
        );

        $this->assertSame(2, $param->page);
        $this->assertSame(20, $param->limit);
        $this->assertSame(AftersalesState::PENDING_APPROVAL, $param->state);
        $this->assertSame(AftersalesType::REFUND_ONLY, $param->type);
    }
}
