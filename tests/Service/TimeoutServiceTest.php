<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Service\TimeoutService;

/**
 * @internal
 */
#[CoversClass(TimeoutService::class)]
class TimeoutServiceTest extends TestCase
{
    public function testSetAutoProcessTime(): void
    {
        $service = new TimeoutService();
        $aftersales = $this->createMockAftersales();

        // Set the required state
        $aftersales->setState(AftersalesState::PENDING_APPROVAL);
        // Don't set type as it requires a specific enum value

        $service->setAutoProcessTime($aftersales);

        // Verify that autoProcessTime was set and is in the future
        $autoProcessTime = $aftersales->getAutoProcessTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $autoProcessTime);
        $this->assertGreaterThan(new \DateTimeImmutable(), $autoProcessTime);
    }

    public function testShouldUpdateTimeout(): void
    {
        $service = new TimeoutService();
        $aftersales = $this->createMockAftersales();

        $currentState = AftersalesState::PENDING_APPROVAL;
        $newState = AftersalesState::APPROVED;

        $aftersales->setState($currentState);

        $result = $service->shouldUpdateTimeout($aftersales, $newState);
        $this->assertTrue($result);
    }

    public function testClearAutoProcessTime(): void
    {
        $service = new TimeoutService();
        $aftersales = $this->createMockAftersales();

        // Set a time first
        $aftersales->setAutoProcessTime(new \DateTimeImmutable());

        $service->clearAutoProcessTime($aftersales);

        // Verify that autoProcessTime was cleared
        $this->assertNull($aftersales->getAutoProcessTime());
    }

    public function testGetTimeoutRules(): void
    {
        $service = new TimeoutService();
        $rules = $service->getTimeoutRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('pending_approval', $rules);
        $this->assertArrayHasKey('pending_return', $rules);
        $this->assertArrayHasKey('pending_receive', $rules);
    }

    private function createMockAftersales(): Aftersales
    {
        return new Aftersales();
    }
}
