<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Aftersales\CreateAftersalesFromOmsParam;

#[CoversClass(CreateAftersalesFromOmsParam::class)]
final class CreateAftersalesFromOmsParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new CreateAftersalesFromOmsParam(
            aftersalesNo: 'AS001',
            aftersalesType: 'refund',
            orderNo: 'ORDER001',
            reason: 'quality_issue',
            refundAmount: 1000,
            applicantName: 'Test User',
            applicantPhone: '13800138000',
            applyTime: '2024-01-01 00:00:00',
            products: [],
        );
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithRequiredValues(): void
    {
        $products = [
            ['productCode' => 'P001', 'productName' => 'Test Product', 'quantity' => 1, 'amount' => 1000],
        ];

        $param = new CreateAftersalesFromOmsParam(
            aftersalesNo: 'AS001',
            aftersalesType: 'refund',
            orderNo: 'ORDER001',
            reason: 'quality_issue',
            refundAmount: 1000,
            applicantName: 'Test User',
            applicantPhone: '13800138000',
            applyTime: '2024-01-01 00:00:00',
            products: $products,
        );

        $this->assertSame('AS001', $param->aftersalesNo);
        $this->assertSame('refund', $param->aftersalesType);
        $this->assertSame('ORDER001', $param->orderNo);
        $this->assertSame('quality_issue', $param->reason);
        $this->assertSame(1000, $param->refundAmount);
        $this->assertSame('Test User', $param->applicantName);
        $this->assertSame('13800138000', $param->applicantPhone);
        $this->assertSame('2024-01-01 00:00:00', $param->applyTime);
        $this->assertSame($products, $param->products);
        $this->assertNull($param->description);
        $this->assertSame([], $param->proofImages);
        $this->assertNull($param->exchangeAddress);
    }

    public function testConstructorWithAllValues(): void
    {
        $products = [
            ['productCode' => 'P001', 'productName' => 'Test Product', 'quantity' => 1, 'amount' => 1000],
        ];
        $proofImages = ['image1.jpg'];
        $exchangeAddress = ['name' => 'Test', 'phone' => '13800138000', 'address' => 'Test Address'];

        $param = new CreateAftersalesFromOmsParam(
            aftersalesNo: 'AS001',
            aftersalesType: 'exchange',
            orderNo: 'ORDER001',
            reason: 'quality_issue',
            refundAmount: 1000,
            applicantName: 'Test User',
            applicantPhone: '13800138000',
            applyTime: '2024-01-01 00:00:00',
            products: $products,
            description: 'Test description',
            proofImages: $proofImages,
            exchangeAddress: $exchangeAddress,
        );

        $this->assertSame('Test description', $param->description);
        $this->assertSame($proofImages, $param->proofImages);
        $this->assertSame($exchangeAddress, $param->exchangeAddress);
    }
}
