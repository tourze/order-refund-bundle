<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Aftersales\UpdateAftersalesStatusFromOmsParam;

#[CoversClass(UpdateAftersalesStatusFromOmsParam::class)]
final class UpdateAftersalesStatusFromOmsParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new UpdateAftersalesStatusFromOmsParam(
            aftersalesNo: 'AS001',
            status: 'approved',
        );
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testConstructorWithRequiredOnly(): void
    {
        $param = new UpdateAftersalesStatusFromOmsParam(
            aftersalesNo: 'AS001',
            status: 'approved',
        );

        $this->assertSame('AS001', $param->aftersalesNo);
        $this->assertSame('approved', $param->status);
        $this->assertNull($param->auditor);
        $this->assertNull($param->auditTime);
        $this->assertNull($param->auditRemark);
        $this->assertNull($param->approvedAmount);
        $this->assertNull($param->returnLogistics);
        $this->assertNull($param->processTime);
        $this->assertNull($param->completedTime);
    }

    public function testConstructorWithAllValues(): void
    {
        $returnLogistics = ['company' => 'SF', 'trackingNo' => 'SF123'];

        $param = new UpdateAftersalesStatusFromOmsParam(
            aftersalesNo: 'AS001',
            status: 'completed',
            auditor: 'Admin',
            auditTime: '2024-01-02 00:00:00',
            auditRemark: 'Approved',
            approvedAmount: 900,
            returnLogistics: $returnLogistics,
            processTime: '2024-01-03 00:00:00',
            completedTime: '2024-01-04 00:00:00',
        );

        $this->assertSame('AS001', $param->aftersalesNo);
        $this->assertSame('completed', $param->status);
        $this->assertSame('Admin', $param->auditor);
        $this->assertSame('2024-01-02 00:00:00', $param->auditTime);
        $this->assertSame('Approved', $param->auditRemark);
        $this->assertSame(900, $param->approvedAmount);
        $this->assertSame($returnLogistics, $param->returnLogistics);
        $this->assertSame('2024-01-03 00:00:00', $param->processTime);
        $this->assertSame('2024-01-04 00:00:00', $param->completedTime);
    }
}
