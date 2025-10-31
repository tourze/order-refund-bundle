<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Procedure\Aftersales\UpdateAftersalesInfoFromOms;

/**
 * @internal
 */
#[CoversClass(UpdateAftersalesInfoFromOms::class)]
#[RunTestsInSeparateProcesses]
class UpdateAftersalesInfoFromOmsTest extends AbstractProcedureTestCase
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
        $this->procedure->execute();
    }

    public function testGetMockResult(): void
    {
        $mockResult = UpdateAftersalesInfoFromOms::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertTrue($mockResult['success']);
        $this->assertEquals('售后单信息修改成功', $mockResult['message']);
        $this->assertArrayHasKey('aftersalesId', $mockResult);
        $this->assertArrayHasKey('modifiedFields', $mockResult);
    }
}
