<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Aftersales\ApplyAftersalesParam;

#[CoversClass(ApplyAftersalesParam::class)]
final class ApplyAftersalesParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new ApplyAftersalesParam();
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testDefaultValues(): void
    {
        $param = new ApplyAftersalesParam();
        $this->assertSame('', $param->contractId);
        $this->assertNull($param->type);
        $this->assertNull($param->reason);
        $this->assertNull($param->description);
        $this->assertSame([], $param->proofImages);
        $this->assertSame([], $param->items);
    }

    public function testConstructorWithValues(): void
    {
        $items = [['orderProductId' => '123', 'quantity' => 2]];
        $proofImages = ['image1.jpg', 'image2.jpg'];

        $param = new ApplyAftersalesParam(
            contractId: 'contract-123',
            type: 'refund',
            reason: 'quality_issue',
            description: 'Product damaged',
            proofImages: $proofImages,
            items: $items,
        );

        $this->assertSame('contract-123', $param->contractId);
        $this->assertSame('refund', $param->type);
        $this->assertSame('quality_issue', $param->reason);
        $this->assertSame('Product damaged', $param->description);
        $this->assertSame($proofImages, $param->proofImages);
        $this->assertSame($items, $param->items);
    }
}
