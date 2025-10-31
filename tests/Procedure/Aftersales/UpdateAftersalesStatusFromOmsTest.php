<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Procedure\Aftersales\UpdateAftersalesStatusFromOms;

/**
 * @internal
 */
#[CoversClass(UpdateAftersalesStatusFromOms::class)]
#[RunTestsInSeparateProcesses]
class UpdateAftersalesStatusFromOmsTest extends AbstractProcedureTestCase
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
        $this->procedure->execute();
    }

    public function testGetMockResult(): void
    {
        $mockResult = UpdateAftersalesStatusFromOms::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertTrue($mockResult['success']);
        $this->assertEquals('售后单状态更新成功', $mockResult['message']);
        $this->assertArrayHasKey('aftersalesId', $mockResult);
        $this->assertArrayHasKey('oldStatus', $mockResult);
        $this->assertArrayHasKey('newStatus', $mockResult);
    }
}
