<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;

/**
 * 超时处理服务
 */
readonly class TimeoutService
{
    /**
     * 设置售后申请的自动处理时间
     */
    public function setAutoProcessTime(Aftersales $aftersales): void
    {
        $state = $aftersales->getState();
        $type = $aftersales->getType();

        $timeout = $this->calculateTimeout($state, $type);

        if ($timeout > 0) {
            $autoProcessTime = (new \DateTimeImmutable())->modify(sprintf('+%d hours', $timeout));
            $aftersales->setAutoProcessTime($autoProcessTime);
        } else {
            $aftersales->setAutoProcessTime(null);
        }
    }

    /**
     * 计算超时时间（小时）
     */
    private function calculateTimeout(AftersalesState $state, ?AftersalesType $type): int
    {
        return match ($state) {
            // 待审核：72小时后自动通过
            AftersalesState::PENDING_APPROVAL => 72,

            // 待退货：根据类型设置不同超时
            AftersalesState::PENDING_RETURN => match ($type) {
                AftersalesType::RETURN_REFUND => 7 * 24, // 7天
                AftersalesType::EXCHANGE => 7 * 24,      // 7天
                default => 0,
            },

            // 待收货确认：72小时后自动确认
            AftersalesState::PENDING_RECEIVE => 72,

            // 其他状态不设置超时
            default => 0,
        };
    }

    /**
     * 检查是否需要更新超时时间
     */
    public function shouldUpdateTimeout(Aftersales $aftersales, AftersalesState $newState): bool
    {
        $currentState = $aftersales->getState();

        // 状态发生变化时需要重新设置超时
        if ($currentState !== $newState) {
            return true;
        }

        // 如果当前没有设置自动处理时间，但新状态需要超时处理
        if (null === $aftersales->getAutoProcessTime()) {
            return $this->calculateTimeout($newState, $aftersales->getType()) > 0;
        }

        return false;
    }

    /**
     * 清除自动处理时间
     */
    public function clearAutoProcessTime(Aftersales $aftersales): void
    {
        $aftersales->setAutoProcessTime(null);
    }

    /**
     * 获取超时处理规则说明
     *
     * @return array<string, string>
     */
    public function getTimeoutRules(): array
    {
        return [
            AftersalesState::PENDING_APPROVAL->value => '待审核状态72小时后自动通过',
            AftersalesState::PENDING_RETURN->value => '待退货状态7天后自动取消（仅限退货退款和换货）',
            AftersalesState::PENDING_RECEIVE->value => '待收货确认状态72小时后自动确认收货',
        ];
    }
}
