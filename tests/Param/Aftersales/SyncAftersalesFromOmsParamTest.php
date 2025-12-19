<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Aftersales\SyncAftersalesFromOmsParam;

#[CoversClass(SyncAftersalesFromOmsParam::class)]
final class SyncAftersalesFromOmsParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new SyncAftersalesFromOmsParam();
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testDefaultValues(): void
    {
        $param = new SyncAftersalesFromOmsParam();
        $this->assertSame('', $param->aftersalesNo);
        $this->assertSame('', $param->aftersalesType);
        $this->assertSame('', $param->orderNo);
        $this->assertSame('', $param->reason);
        $this->assertNull($param->description);
        $this->assertSame([], $param->proofImages);
        $this->assertSame('', $param->status);
        $this->assertSame(0, $param->refundAmount);
        $this->assertSame('', $param->applicantName);
        $this->assertSame('', $param->applicantPhone);
        $this->assertSame('', $param->applyTime);
        $this->assertNull($param->auditor);
        $this->assertNull($param->auditTime);
        $this->assertNull($param->auditRemark);
        $this->assertSame([], $param->products);
        $this->assertNull($param->returnLogistics);
        $this->assertNull($param->exchangeAddress);
    }

    public function testConstructorWithValues(): void
    {
        $products = [['productCode' => 'P001', 'productName' => 'Test', 'quantity' => 1, 'amount' => 1000]];
        $returnLogistics = ['company' => 'SF', 'trackingNo' => 'SF123'];
        $exchangeAddress = ['name' => 'Test', 'address' => 'Test Address'];

        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: 'AS001',
            aftersalesType: 'return',
            orderNo: 'ORDER001',
            reason: 'quality_issue',
            description: 'Damaged',
            proofImages: ['img.jpg'],
            status: 'approved',
            refundAmount: 1000,
            applicantName: 'Test User',
            applicantPhone: '13800138000',
            applyTime: '2024-01-01 00:00:00',
            auditor: 'Admin',
            auditTime: '2024-01-02 00:00:00',
            auditRemark: 'Approved',
            products: $products,
            returnLogistics: $returnLogistics,
            exchangeAddress: $exchangeAddress,
        );

        $this->assertSame('AS001', $param->aftersalesNo);
        $this->assertSame('return', $param->aftersalesType);
        $this->assertSame('ORDER001', $param->orderNo);
        $this->assertSame('quality_issue', $param->reason);
        $this->assertSame('Damaged', $param->description);
        $this->assertSame(['img.jpg'], $param->proofImages);
        $this->assertSame('approved', $param->status);
        $this->assertSame(1000, $param->refundAmount);
        $this->assertSame('Test User', $param->applicantName);
        $this->assertSame('13800138000', $param->applicantPhone);
        $this->assertSame('2024-01-01 00:00:00', $param->applyTime);
        $this->assertSame('Admin', $param->auditor);
        $this->assertSame('2024-01-02 00:00:00', $param->auditTime);
        $this->assertSame('Approved', $param->auditRemark);
        $this->assertSame($products, $param->products);
        $this->assertSame($returnLogistics, $param->returnLogistics);
        $this->assertSame($exchangeAddress, $param->exchangeAddress);
    }
}
