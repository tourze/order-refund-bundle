<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Aftersales\UpdateAftersalesInfoFromOmsParam;

#[CoversClass(UpdateAftersalesInfoFromOmsParam::class)]
final class UpdateAftersalesInfoFromOmsParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new UpdateAftersalesInfoFromOmsParam(aftersalesNo: 'AS001');
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithRequiredOnly(): void
    {
        $param = new UpdateAftersalesInfoFromOmsParam(aftersalesNo: 'AS001');

        $this->assertSame('AS001', $param->aftersalesNo);
        $this->assertNull($param->description);
        $this->assertNull($param->proofImages);
        $this->assertNull($param->refundAmount);
        $this->assertNull($param->applicantName);
        $this->assertNull($param->applicantPhone);
        $this->assertNull($param->products);
        $this->assertNull($param->returnLogistics);
        $this->assertNull($param->exchangeAddress);
        $this->assertNull($param->serviceNote);
        $this->assertSame('', $param->modifyReason);
    }

    public function testConstructorWithAllValues(): void
    {
        $products = [['productCode' => 'P001', 'productName' => 'Test', 'quantity' => 1, 'amount' => 1000]];
        $returnLogistics = ['company' => 'SF', 'trackingNo' => 'SF123'];
        $exchangeAddress = ['name' => 'Test', 'address' => 'Test Address'];

        $param = new UpdateAftersalesInfoFromOmsParam(
            aftersalesNo: 'AS001',
            description: 'Updated description',
            proofImages: ['img.jpg'],
            refundAmount: 2000,
            applicantName: 'New User',
            applicantPhone: '13900139000',
            products: $products,
            returnLogistics: $returnLogistics,
            exchangeAddress: $exchangeAddress,
            serviceNote: 'Service note',
            modifyReason: 'Customer request',
        );

        $this->assertSame('AS001', $param->aftersalesNo);
        $this->assertSame('Updated description', $param->description);
        $this->assertSame(['img.jpg'], $param->proofImages);
        $this->assertSame(2000, $param->refundAmount);
        $this->assertSame('New User', $param->applicantName);
        $this->assertSame('13900139000', $param->applicantPhone);
        $this->assertSame($products, $param->products);
        $this->assertSame($returnLogistics, $param->returnLogistics);
        $this->assertSame($exchangeAddress, $param->exchangeAddress);
        $this->assertSame('Service note', $param->serviceNote);
        $this->assertSame('Customer request', $param->modifyReason);
    }
}
