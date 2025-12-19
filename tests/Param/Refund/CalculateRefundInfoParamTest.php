<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Refund;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Refund\CalculateRefundInfoParam;

#[CoversClass(CalculateRefundInfoParam::class)]
final class CalculateRefundInfoParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new CalculateRefundInfoParam();
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testDefaultValues(): void
    {
        $param = new CalculateRefundInfoParam();
        $this->assertSame('', $param->contractId);
        $this->assertSame([], $param->items);
    }

    public function testConstructorWithValues(): void
    {
        $items = [
            ['orderProductId' => '123', 'quantity' => 1],
            ['orderProductId' => '456', 'quantity' => 2],
        ];

        $param = new CalculateRefundInfoParam(
            contractId: 'contract-123',
            items: $items,
        );

        $this->assertSame('contract-123', $param->contractId);
        $this->assertSame($items, $param->items);
    }
}
