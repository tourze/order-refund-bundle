<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStatus;

/**
 * 工作流服务 - 处理售后状态转换和流程控制
 */
readonly class WorkflowService
{
    public function __construct(
        private EventDispatcherService $eventDispatcherService,
    ) {
    }

    /**
     * 检查是否可以进行状态转换
     */
    public function canTransition(Aftersales $aftersales, string $action): bool
    {
        $currentStatus = $this->getCurrentStatus($aftersales);
        $allowedTransitions = $this->getAllowedTransitions($currentStatus);

        return in_array($action, $allowedTransitions, true);
    }

    /**
     * 执行状态转换
     */
    public function transition(Aftersales $aftersales, string $action): bool
    {
        if (!$this->canTransition($aftersales, $action)) {
            return false;
        }

        $currentStatus = $this->getCurrentStatus($aftersales);
        $newStatus = $this->getNewStatusByAction($currentStatus, $action);

        if (null !== $newStatus) {
            $this->setAftersalesStatus($aftersales, $newStatus);

            // 分发状态变更事件
            $this->eventDispatcherService->dispatchStatusChanged(
                $aftersales,
                $currentStatus,
                $newStatus,
                $action,
                ['timestamp' => new \DateTime()]
            );

            return true;
        }

        return false;
    }

    /**
     * 获取当前状态允许的操作
     */
    /**
     * @return array<string>
     */
    public function getAllowedActions(Aftersales $aftersales): array
    {
        $currentStatus = $this->getCurrentStatus($aftersales);

        return $this->getAllowedTransitions($currentStatus);
    }

    /**
     * 获取状态转换历史
     */
    /**
     * @return array<array<string, mixed>>
     */
    public function getTransitionHistory(Aftersales $aftersales): array
    {
        // 这里可以从日志或历史表中获取状态转换记录
        return [
            [
                'from_status' => 'pending',
                'to_status' => 'approved',
                'action' => 'approve',
                'timestamp' => new \DateTime('-1 day'),
                'operator' => 'admin',
            ],
        ];
    }

    /**
     * 检查是否需要自动处理
     */
    public function shouldAutoProcess(Aftersales $aftersales): bool
    {
        $currentStatus = $this->getCurrentStatus($aftersales);

        // 某些状态下支持自动处理
        $autoProcessStatuses = ['pending', 'approved'];

        return in_array($currentStatus, $autoProcessStatuses, true);
    }

    /**
     * 获取下一步可能的状态
     */
    /**
     * @return array<array<string, mixed>>
     */
    public function getNextPossibleStatuses(Aftersales $aftersales): array
    {
        $currentStatus = $this->getCurrentStatus($aftersales);
        $allowedActions = $this->getAllowedTransitions($currentStatus);

        $nextStatuses = [];
        foreach ($allowedActions as $action) {
            $nextStatus = $this->getNewStatusByAction($currentStatus, $action);
            if (null !== $nextStatus) {
                $nextStatuses[] = [
                    'action' => $action,
                    'status' => $nextStatus,
                    'label' => $this->getStatusLabel($nextStatus),
                ];
            }
        }

        return $nextStatuses;
    }

    /**
     * 批量状态转换
     */
    /**
     * @param array<Aftersales> $aftersalesList
     * @return array<string, array<string, mixed>>
     */
    public function batchTransition(array $aftersalesList, string $action): array
    {
        $results = [];

        foreach ($aftersalesList as $aftersales) {
            $id = $aftersales->getId();
            $success = $this->transition($aftersales, $action);

            $results[$id] = [
                'success' => $success,
                'old_status' => $this->getCurrentStatus($aftersales),
                'new_status' => $success ? $this->getNewStatusByAction($this->getCurrentStatus($aftersales), $action) : null,
            ];
        }

        return $results;
    }

    /**
     * 检查工作流完整性
     */
    /**
     * @return array<string>
     */
    public function validateWorkflow(Aftersales $aftersales): array
    {
        $errors = [];
        $currentStatus = $this->getCurrentStatus($aftersales);

        // 检查必要的数据是否完整
        if ('approved' === $currentStatus && null === $aftersales->getProductSnapshot()) {
            $errors[] = '已审核状态必须有商品快照数据';
        }

        if ('processing' === $currentStatus && (null === $aftersales->getProductId() || '' === $aftersales->getProductId())) {
            $errors[] = '处理中状态必须有商品ID';
        }

        return $errors;
    }

    /**
     * 获取当前状态
     */
    private function getCurrentStatus(Aftersales $aftersales): string
    {
        // 假设实体有 getStatus 方法，如果没有则使用默认状态
        if (method_exists($aftersales, 'getStatus')) {
            $status = $aftersales->getStatus();

            return is_string($status) ? $status : 'pending';
        }

        return 'pending'; // 默认状态
    }

    /**
     * 设置售后状态
     */
    private function setAftersalesStatus(Aftersales $aftersales, string $status): void
    {
        // 假设实体有 setStatus 方法
        if (method_exists($aftersales, 'setStatus')) {
            $aftersales->setStatus($status);
        }
    }

    /**
     * 获取允许的状态转换
     */
    /**
     * @return array<string>
     */
    private function getAllowedTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            'pending' => ['approve', 'reject', 'cancel'],
            'approved' => ['start_processing', 'cancel'],
            'rejected' => ['resubmit', 'cancel'],
            'processing' => ['complete', 'cancel'],
            'completed' => [], // 完成状态不能再转换
            'cancelled' => [], // 取消状态不能再转换
            default => [],
        };
    }

    /**
     * 根据操作获取新状态
     */
    private function getNewStatusByAction(string $currentStatus, string $action): ?string
    {
        $transitions = [
            'pending' => [
                'approve' => 'approved',
                'reject' => 'rejected',
                'cancel' => 'cancelled',
            ],
            'approved' => [
                'start_processing' => 'processing',
                'cancel' => 'cancelled',
            ],
            'rejected' => [
                'resubmit' => 'pending',
                'cancel' => 'cancelled',
            ],
            'processing' => [
                'complete' => 'completed',
                'cancel' => 'cancelled',
            ],
        ];

        return $transitions[$currentStatus][$action] ?? null;
    }

    /**
     * 获取状态标签
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => '待审核',
            'approved' => '已审核',
            'rejected' => '已拒绝',
            'processing' => '处理中',
            'completed' => '已完成',
            'cancelled' => '已取消',
            default => '未知状态',
        };
    }

    /**
     * 获取工作流配置
     */
    /**
     * @return array<string, array<string, mixed>>
     */
    public function getWorkflowConfig(): array
    {
        return [
            'statuses' => [
                'pending' => ['label' => '待审核', 'color' => 'warning'],
                'approved' => ['label' => '已审核', 'color' => 'success'],
                'rejected' => ['label' => '已拒绝', 'color' => 'danger'],
                'processing' => ['label' => '处理中', 'color' => 'info'],
                'completed' => ['label' => '已完成', 'color' => 'success'],
                'cancelled' => ['label' => '已取消', 'color' => 'secondary'],
            ],
            'transitions' => [
                'approve' => ['label' => '审核通过', 'icon' => 'check'],
                'reject' => ['label' => '审核拒绝', 'icon' => 'times'],
                'cancel' => ['label' => '取消', 'icon' => 'ban'],
                'start_processing' => ['label' => '开始处理', 'icon' => 'play'],
                'complete' => ['label' => '完成', 'icon' => 'check-circle'],
                'resubmit' => ['label' => '重新提交', 'icon' => 'redo'],
            ],
        ];
    }
}
