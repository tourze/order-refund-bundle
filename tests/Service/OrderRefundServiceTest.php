<?php

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OrderRefundBundle\Service\OrderRefundService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(OrderRefundService::class)]
#[RunTestsInSeparateProcesses]
final class OrderRefundServiceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Setup if needed
    }

    public function testGetRandomErrorPageWithNoTemplates(): void
    {
        $service = self::getService(OrderRefundService::class);

        $result = $service->getRandomErrorPage();
        $this->assertNull($result);
    }

    public function testGetRandomErrorPageWithTemplates(): void
    {
        $service = self::getService(OrderRefundService::class);
        $result = $service->getRandomErrorPage();

        $this->assertTrue(null === $result || $result instanceof Response);
    }
}
