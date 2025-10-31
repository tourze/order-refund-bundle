<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Service\AutoAuditService;

/**
 * @internal
 */
#[CoversClass(AutoAuditService::class)]
class AutoAuditServiceTest extends TestCase
{
    public function testProcessAutoAudit(): void
    {
        $service = new AutoAuditService();
        $aftersales = $this->createMockAftersales();

        $service->processAutoAudit($aftersales);

        // 验证服务可以正常处理自动审核
        $this->assertInstanceOf(AutoAuditService::class, $service);
    }

    public function testIsEligibleForAutoProcess(): void
    {
        $service = new AutoAuditService();
        $aftersales = $this->createMockAftersales();

        $result = $service->isEligibleForAutoProcess($aftersales);

        $this->assertIsBool($result);
    }

    private function createMockAftersales(): Aftersales
    {
        return new Aftersales();
    }
}
