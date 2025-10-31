<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Exception\GeneralAftersalesException;
use Tourze\OrderRefundBundle\Service\OmsStatusMapper;

/**
 * @internal
 */
#[CoversClass(OmsStatusMapper::class)]
final class OmsStatusMapperTest extends TestCase
{
    private OmsStatusMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new OmsStatusMapper();
    }

    #[DataProvider('validTypeDataProvider')]
    public function testMapOmsTypeToEnumWithValidTypes(string $type, AftersalesType $expected): void
    {
        $result = $this->mapper->mapOmsTypeToEnum($type);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{type: string, expected: AftersalesType}>
     */
    public static function validTypeDataProvider(): iterable
    {
        yield 'refund type' => [
            'type' => 'refund',
            'expected' => AftersalesType::REFUND_ONLY,
        ];

        yield 'return type' => [
            'type' => 'return',
            'expected' => AftersalesType::RETURN_REFUND,
        ];

        yield 'exchange type' => [
            'type' => 'exchange',
            'expected' => AftersalesType::EXCHANGE,
        ];
    }

    #[DataProvider('invalidTypeDataProvider')]
    public function testMapOmsTypeToEnumWithInvalidTypes(string $type): void
    {
        $this->expectException(GeneralAftersalesException::class);
        $this->expectExceptionMessage('无效的售后类型: ' . $type);

        $this->mapper->mapOmsTypeToEnum($type);
    }

    /**
     * @return iterable<string, array{type: string}>
     */
    public static function invalidTypeDataProvider(): iterable
    {
        yield 'unknown type' => ['type' => 'unknown'];
        yield 'cancel type' => ['type' => 'cancel'];
        yield 'resend type' => ['type' => 'resend'];
        yield 'empty string' => ['type' => ''];
        yield 'uppercase type' => ['type' => 'REFUND'];
        yield 'mixed case type' => ['type' => 'Return'];
    }

    public function testMapOmsReasonToEnum(): void
    {
        // Test with various reasons - all should return QUALITY_ISSUE
        $reasons = ['quality', 'price', 'delivery', 'other', ''];

        foreach ($reasons as $reason) {
            $result = $this->mapper->mapOmsReasonToEnum($reason);
            $this->assertSame(RefundReason::QUALITY_ISSUE, $result);
        }
    }

    #[DataProvider('statusToStateDataProvider')]
    public function testMapOmsStatusToState(string $status, AftersalesState $expected): void
    {
        $result = $this->mapper->mapOmsStatusToState($status);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{status: string, expected: AftersalesState}>
     */
    public static function statusToStateDataProvider(): iterable
    {
        yield 'pending status' => [
            'status' => 'pending',
            'expected' => AftersalesState::PENDING_APPROVAL,
        ];

        yield 'submitted status' => [
            'status' => 'submitted',
            'expected' => AftersalesState::PENDING_APPROVAL,
        ];

        yield 'approved status' => [
            'status' => 'approved',
            'expected' => AftersalesState::APPROVED,
        ];

        yield 'processing status' => [
            'status' => 'processing',
            'expected' => AftersalesState::APPROVED,
        ];

        yield 'rejected status' => [
            'status' => 'rejected',
            'expected' => AftersalesState::REJECTED,
        ];

        yield 'refused status' => [
            'status' => 'refused',
            'expected' => AftersalesState::REJECTED,
        ];

        yield 'completed status' => [
            'status' => 'completed',
            'expected' => AftersalesState::COMPLETED,
        ];

        yield 'finished status' => [
            'status' => 'finished',
            'expected' => AftersalesState::COMPLETED,
        ];

        yield 'cancelled status' => [
            'status' => 'cancelled',
            'expected' => AftersalesState::CANCELLED,
        ];

        yield 'closed status' => [
            'status' => 'closed',
            'expected' => AftersalesState::CANCELLED,
        ];
    }

    #[DataProvider('statusCaseInsensitiveDataProvider')]
    public function testMapOmsStatusToStateIsCaseInsensitive(string $status, AftersalesState $expected): void
    {
        $result = $this->mapper->mapOmsStatusToState($status);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{status: string, expected: AftersalesState}>
     */
    public static function statusCaseInsensitiveDataProvider(): iterable
    {
        yield 'UPPERCASE pending' => [
            'status' => 'PENDING',
            'expected' => AftersalesState::PENDING_APPROVAL,
        ];

        yield 'MixedCase Approved' => [
            'status' => 'Approved',
            'expected' => AftersalesState::APPROVED,
        ];

        yield 'UPPERCASE COMPLETED' => [
            'status' => 'COMPLETED',
            'expected' => AftersalesState::COMPLETED,
        ];

        yield 'camelCase Rejected' => [
            'status' => 'ReJeCTeD',
            'expected' => AftersalesState::REJECTED,
        ];
    }

    public function testMapOmsStatusToStateWithUnknownStatus(): void
    {
        $result = $this->mapper->mapOmsStatusToState('unknown-status');

        $this->assertSame(AftersalesState::PENDING_APPROVAL, $result);
    }

    #[DataProvider('statusToStageDataProvider')]
    public function testMapOmsStatusToStage(string $status, AftersalesStage $expected): void
    {
        $result = $this->mapper->mapOmsStatusToStage($status);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{status: string, expected: AftersalesStage}>
     */
    public static function statusToStageDataProvider(): iterable
    {
        yield 'pending stage' => [
            'status' => 'pending',
            'expected' => AftersalesStage::APPLY,
        ];

        yield 'submitted stage' => [
            'status' => 'submitted',
            'expected' => AftersalesStage::APPLY,
        ];

        yield 'approved stage' => [
            'status' => 'approved',
            'expected' => AftersalesStage::AUDIT,
        ];

        yield 'processing stage' => [
            'status' => 'processing',
            'expected' => AftersalesStage::RETURN,
        ];

        yield 'completed stage' => [
            'status' => 'completed',
            'expected' => AftersalesStage::COMPLETE,
        ];

        yield 'finished stage' => [
            'status' => 'finished',
            'expected' => AftersalesStage::COMPLETE,
        ];
    }

    #[DataProvider('stageCaseInsensitiveDataProvider')]
    public function testMapOmsStatusToStageIsCaseInsensitive(string $status, AftersalesStage $expected): void
    {
        $result = $this->mapper->mapOmsStatusToStage($status);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{status: string, expected: AftersalesStage}>
     */
    public static function stageCaseInsensitiveDataProvider(): iterable
    {
        yield 'UPPERCASE PENDING' => [
            'status' => 'PENDING',
            'expected' => AftersalesStage::APPLY,
        ];

        yield 'MixedCase Processing' => [
            'status' => 'Processing',
            'expected' => AftersalesStage::RETURN,
        ];

        yield 'UPPERCASE APPROVED' => [
            'status' => 'APPROVED',
            'expected' => AftersalesStage::AUDIT,
        ];
    }

    public function testMapOmsStatusToStageWithUnknownStatus(): void
    {
        $result = $this->mapper->mapOmsStatusToStage('unknown-status');

        $this->assertSame(AftersalesStage::APPLY, $result);
    }

    #[DataProvider('unmappedStatusToStageDataProvider')]
    public function testMapOmsStatusToStageReturnsDefaultForUnmappedStatuses(string $status): void
    {
        $result = $this->mapper->mapOmsStatusToStage($status);

        $this->assertSame(AftersalesStage::APPLY, $result);
    }

    /**
     * @return iterable<string, array{status: string}>
     */
    public static function unmappedStatusToStageDataProvider(): iterable
    {
        yield 'rejected status' => ['status' => 'rejected'];
        yield 'refused status' => ['status' => 'refused'];
        yield 'cancelled status' => ['status' => 'cancelled'];
        yield 'closed status' => ['status' => 'closed'];
        yield 'empty string' => ['status' => ''];
        yield 'random status' => ['status' => 'random'];
    }

    public function testMapOmsStatusToStateAndStageConsistency(): void
    {
        // Test that state and stage mappings are logically consistent
        $testCases = [
            'pending' => [
                'state' => AftersalesState::PENDING_APPROVAL,
                'stage' => AftersalesStage::APPLY,
            ],
            'approved' => [
                'state' => AftersalesState::APPROVED,
                'stage' => AftersalesStage::AUDIT,
            ],
            'processing' => [
                'state' => AftersalesState::APPROVED,
                'stage' => AftersalesStage::RETURN,
            ],
            'completed' => [
                'state' => AftersalesState::COMPLETED,
                'stage' => AftersalesStage::COMPLETE,
            ],
        ];

        foreach ($testCases as $status => $expected) {
            $state = $this->mapper->mapOmsStatusToState($status);
            $stage = $this->mapper->mapOmsStatusToStage($status);

            $this->assertSame(
                $expected['state'],
                $state,
                "State mapping for '{$status}' should be {$expected['state']->value}"
            );
            $this->assertSame(
                $expected['stage'],
                $stage,
                "Stage mapping for '{$status}' should be {$expected['stage']->value}"
            );
        }
    }

    public function testMapOmsReasonToEnumAlwaysReturnsQualityIssue(): void
    {
        // Test edge cases to ensure it always returns QUALITY_ISSUE
        $edgeCases = [
            '',
            ' ',
            'null',
            '0',
            'false',
            'true',
            'QUALITY_ISSUE',
            'quality_issue',
        ];

        foreach ($edgeCases as $reason) {
            $result = $this->mapper->mapOmsReasonToEnum($reason);
            $this->assertSame(
                RefundReason::QUALITY_ISSUE,
                $result,
                "Reason '{$reason}' should map to QUALITY_ISSUE"
            );
        }
    }
}
