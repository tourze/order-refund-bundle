<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Return;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Return\SubmitReturnExpressParam;

#[CoversClass(SubmitReturnExpressParam::class)]
final class SubmitReturnExpressParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new SubmitReturnExpressParam(
            aftersalesId: 1,
            expressCompany: 'SF',
            trackingNo: 'SF123456',
        );
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithIntId(): void
    {
        $param = new SubmitReturnExpressParam(
            aftersalesId: 123,
            expressCompany: 'SF',
            trackingNo: 'SF123456',
        );

        $this->assertSame(123, $param->aftersalesId);
        $this->assertSame('SF', $param->expressCompany);
        $this->assertSame('SF123456', $param->trackingNo);
        $this->assertNull($param->remark);
    }

    public function testConstructorWithStringId(): void
    {
        $param = new SubmitReturnExpressParam(
            aftersalesId: 'abc-123',
            expressCompany: 'YTO',
            trackingNo: 'YT123456',
        );

        $this->assertSame('abc-123', $param->aftersalesId);
        $this->assertSame('YTO', $param->expressCompany);
        $this->assertSame('YT123456', $param->trackingNo);
    }

    public function testConstructorWithRemark(): void
    {
        $param = new SubmitReturnExpressParam(
            aftersalesId: 1,
            expressCompany: 'SF',
            trackingNo: 'SF123456',
            remark: 'Please handle with care',
        );

        $this->assertSame('Please handle with care', $param->remark);
    }
}
