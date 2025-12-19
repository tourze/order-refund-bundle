<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\OrderRefundBundle\Param\Aftersales\UpdateAftersalesStatusFromOmsParam;
use Tourze\OrderRefundBundle\Procedure\Aftersales\UpdateAftersalesStatusFromOms;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(UpdateAftersalesStatusFromOms::class)]
#[RunTestsInSeparateProcesses]
final class UpdateAftersalesStatusFromOmsTest extends AbstractProcedureTestCase
{
    private UpdateAftersalesStatusFromOms $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(UpdateAftersalesStatusFromOms::class);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(UpdateAftersalesStatusFromOms::class, $this->procedure);
    }

    public function testExecuteWithoutRequiredParameters(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单号不能为空');
        $param = new UpdateAftersalesStatusFromOmsParam(
            aftersalesNo: '',
            status: '',
        );
        $this->procedure->execute($param);
    }
}
