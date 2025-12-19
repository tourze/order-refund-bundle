<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\OrderRefundBundle\Param\Aftersales\UpdateAftersalesInfoFromOmsParam;
use Tourze\OrderRefundBundle\Procedure\Aftersales\UpdateAftersalesInfoFromOms;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(UpdateAftersalesInfoFromOms::class)]
#[RunTestsInSeparateProcesses]
final class UpdateAftersalesInfoFromOmsTest extends AbstractProcedureTestCase
{
    private UpdateAftersalesInfoFromOms $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(UpdateAftersalesInfoFromOms::class);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(UpdateAftersalesInfoFromOms::class, $this->procedure);
    }

    public function testExecuteWithoutRequiredParameters(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单号不能为空');
        $param = new UpdateAftersalesInfoFromOmsParam(
            aftersalesNo: '',
        );
        $this->procedure->execute($param);
    }
}
