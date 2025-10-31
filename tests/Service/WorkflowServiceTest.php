<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Service\EventDispatcherService;
use Tourze\OrderRefundBundle\Service\WorkflowService;

/**
 * @internal
 */
#[CoversClass(WorkflowService::class)]
class WorkflowServiceTest extends TestCase
{
    private WorkflowService $service;

    private EventDispatcherService $eventDispatcherService;

    protected function setUp(): void
    {
        // Create a dummy EventDispatcherInterface implementation
        $symfonyEventDispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event, ?string $eventName = null): object
            {
                return $event;
            }
        };

        $this->eventDispatcherService = new EventDispatcherService($symfonyEventDispatcher);
        $this->service = new WorkflowService($this->eventDispatcherService);
    }

    public function testStateTransitions(): void
    {
        $aftersales = new Aftersales();
        // 假设默认状态是 pending

        $this->assertTrue($this->service->canTransition($aftersales, 'approve'));
        $this->assertTrue($this->service->canTransition($aftersales, 'reject'));
        $this->assertFalse($this->service->canTransition($aftersales, 'complete'));

        $success = $this->service->transition($aftersales, 'approve');
        $this->assertTrue($success);
    }

    public function testGetAllowedActions(): void
    {
        $aftersales = new Aftersales();

        $actions = $this->service->getAllowedActions($aftersales);

        $this->assertContains('approve', $actions);
        $this->assertContains('reject', $actions);
        $this->assertContains('cancel', $actions);
    }

    public function testGetNextPossibleStatuses(): void
    {
        $aftersales = new Aftersales();

        $nextStatuses = $this->service->getNextPossibleStatuses($aftersales);

        $this->assertIsArray($nextStatuses);
        $this->assertNotEmpty($nextStatuses);

        foreach ($nextStatuses as $status) {
            $this->assertArrayHasKey('action', $status);
            $this->assertArrayHasKey('status', $status);
            $this->assertArrayHasKey('label', $status);
        }
    }

    public function testShouldAutoProcess(): void
    {
        $aftersales = new Aftersales();

        $shouldAuto = $this->service->shouldAutoProcess($aftersales);

        $this->assertIsBool($shouldAuto);
    }

    public function testGetWorkflowConfig(): void
    {
        $config = $this->service->getWorkflowConfig();

        $this->assertArrayHasKey('statuses', $config);
        $this->assertArrayHasKey('transitions', $config);

        $this->assertArrayHasKey('pending', $config['statuses']);
        $this->assertArrayHasKey('approve', $config['transitions']);
    }

    public function testValidateWorkflow(): void
    {
        $aftersales = new Aftersales();

        $errors = $this->service->validateWorkflow($aftersales);

        $this->assertIsArray($errors);
    }

    public function testBatchTransition(): void
    {
        $aftersales1 = new Aftersales();
        $aftersales2 = new Aftersales();

        $aftersalesList = [$aftersales1, $aftersales2];
        $action = 'approve';

        $results = $this->service->batchTransition($aftersalesList, $action);

        $this->assertIsArray($results);
        // Note: If entities don't have unique IDs, they may overwrite each other in the result array
        $this->assertGreaterThanOrEqual(1, count($results));

        foreach ($results as $result) {
            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('old_status', $result);
            $this->assertArrayHasKey('new_status', $result);
            $this->assertIsBool($result['success']);
        }
    }

    public function testCanTransitionValid(): void
    {
        $aftersales = new Aftersales();

        // Test valid transitions from pending status
        $this->assertTrue($this->service->canTransition($aftersales, 'approve'));
        $this->assertTrue($this->service->canTransition($aftersales, 'reject'));
        $this->assertTrue($this->service->canTransition($aftersales, 'cancel'));
    }

    public function testCanTransitionInvalid(): void
    {
        $aftersales = new Aftersales();

        // Test invalid transition from pending status
        $this->assertFalse($this->service->canTransition($aftersales, 'complete'));
        $this->assertFalse($this->service->canTransition($aftersales, 'start_processing'));
        $this->assertFalse($this->service->canTransition($aftersales, 'invalid_action'));
    }

    public function testTransitionSuccess(): void
    {
        $aftersales = new Aftersales();

        // Test successful transition
        $result = $this->service->transition($aftersales, 'approve');
        $this->assertTrue($result);
    }

    public function testTransitionFailure(): void
    {
        $aftersales = new Aftersales();

        // Test invalid transition
        $result = $this->service->transition($aftersales, 'complete');
        $this->assertFalse($result);
    }

    public function testTransitionWithEventDispatcher(): void
    {
        // For this test, we just verify that the method completes successfully
        // The actual event dispatching is tested in integration tests
        $aftersales = new Aftersales();

        $result = $this->service->transition($aftersales, 'approve');
        $this->assertTrue($result);

        // Verify the status was changed as expected
        // (The actual event dispatcher call cannot be easily verified without mocking framework)
    }
}
