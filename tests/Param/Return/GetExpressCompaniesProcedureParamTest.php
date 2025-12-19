<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Param\Return;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Param\Return\GetExpressCompaniesProcedureParam;

#[CoversClass(GetExpressCompaniesProcedureParam::class)]
final class GetExpressCompaniesProcedureParamTest extends TestCase
{
    public function testImplementsRpcParamInterface(): void
    {
        $param = new GetExpressCompaniesProcedureParam();
        $this->assertInstanceOf(RpcParamInterface::class, $param);
    }

    public function testEmptyConstructor(): void
    {
        $param = new GetExpressCompaniesProcedureParam();
        $this->assertInstanceOf(GetExpressCompaniesProcedureParam::class, $param);
    }
}
