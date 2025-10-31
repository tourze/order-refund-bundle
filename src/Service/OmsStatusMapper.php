<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Exception\GeneralAftersalesException;

/**
 * OMS状态映射服务
 * 负责将OMS系统的状态映射到本地枚举
 */
readonly class OmsStatusMapper
{
    /**
     * 映射OMS类型到售后类型枚举
     * @throws GeneralAftersalesException
     */
    public function mapOmsTypeToEnum(string $type): AftersalesType
    {
        return match ($type) {
            'refund' => AftersalesType::REFUND_ONLY,
            'return' => AftersalesType::RETURN_REFUND,
            'exchange' => AftersalesType::EXCHANGE,
            default => throw new GeneralAftersalesException('无效的售后类型: ' . $type),
        };
    }

    /**
     * 映射OMS原因到退款原因枚举
     */
    public function mapOmsReasonToEnum(string $reason): RefundReason
    {
        // 简化处理，默认返回质量问题
        return RefundReason::QUALITY_ISSUE;
    }

    /**
     * 映射OMS状态到售后状态枚举
     */
    public function mapOmsStatusToState(string $status): AftersalesState
    {
        $normalizedStatus = strtolower($status);
        $stateMapping = [
            'pending' => AftersalesState::PENDING_APPROVAL,
            'submitted' => AftersalesState::PENDING_APPROVAL,
            'approved' => AftersalesState::APPROVED,
            'processing' => AftersalesState::APPROVED,
            'rejected' => AftersalesState::REJECTED,
            'refused' => AftersalesState::REJECTED,
            'completed' => AftersalesState::COMPLETED,
            'finished' => AftersalesState::COMPLETED,
            'cancelled' => AftersalesState::CANCELLED,
            'closed' => AftersalesState::CANCELLED,
        ];

        return $stateMapping[$normalizedStatus] ?? AftersalesState::PENDING_APPROVAL;
    }

    /**
     * 映射OMS状态到售后阶段枚举
     */
    public function mapOmsStatusToStage(string $status): AftersalesStage
    {
        $normalizedStatus = strtolower($status);
        $stageMapping = [
            'pending' => AftersalesStage::APPLY,
            'submitted' => AftersalesStage::APPLY,
            'approved' => AftersalesStage::AUDIT,
            'processing' => AftersalesStage::RETURN,
            'completed' => AftersalesStage::COMPLETE,
            'finished' => AftersalesStage::COMPLETE,
        ];

        return $stageMapping[$normalizedStatus] ?? AftersalesStage::APPLY;
    }
}
