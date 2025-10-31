<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Event\AftersalesCancelledEvent;
use Tourze\OrderRefundBundle\Event\AftersalesCompletedEvent;
use Tourze\OrderRefundBundle\Event\AftersalesCreatedEvent;
use Tourze\OrderRefundBundle\Event\AftersalesProcessingEvent;
use Tourze\OrderRefundBundle\Event\AftersalesStatusChangedEvent;
use Tourze\OrderRefundBundle\Exception\InvalidEventTypeException;

/**
 * 事件调度服务 - 封装事件分发逻辑
 */
readonly class EventDispatcherService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * 分发售后创建事件
     */
    /**
     * @param array<string, mixed> $orderData
     * @param array<mixed> $productData
     * @param array<string, mixed> $context
     */
    public function dispatchAftersalesCreated(
        Aftersales $aftersales,
        array $orderData,
        array $productData,
        array $context = [],
    ): void {
        /** @var array<int, array<string, mixed>> $typedProductData */
        $typedProductData = $productData;

        $event = new AftersalesCreatedEvent(
            $aftersales,
            $orderData,
            $typedProductData,
            $context
        );

        $this->eventDispatcher->dispatch($event, AftersalesCreatedEvent::NAME);
    }

    /**
     * 分发售后状态变更事件
     */
    /**
     * @param array<string, mixed> $context
     */
    public function dispatchStatusChanged(
        Aftersales $aftersales,
        string $oldStatus,
        string $newStatus,
        string $action,
        array $context = [],
    ): void {
        $event = new AftersalesStatusChangedEvent(
            $aftersales,
            $oldStatus,
            $newStatus,
            $action,
            $context
        );

        $this->eventDispatcher->dispatch($event, AftersalesStatusChangedEvent::NAME);
    }

    /**
     * 分发售后处理中事件
     */
    /**
     * @param array<string, mixed> $processingData
     * @param array<string, mixed> $context
     */
    public function dispatchProcessing(
        Aftersales $aftersales,
        string $processingType,
        array $processingData = [],
        array $context = [],
    ): void {
        $event = new AftersalesProcessingEvent(
            $aftersales,
            $processingType,
            $processingData,
            $context
        );

        $this->eventDispatcher->dispatch($event, AftersalesProcessingEvent::NAME);
    }

    /**
     * 分发售后完成事件
     */
    /**
     * @param array<string, mixed> $completionData
     * @param array<string, mixed> $context
     */
    public function dispatchCompleted(
        Aftersales $aftersales,
        array $completionData = [],
        array $context = [],
    ): void {
        $event = new AftersalesCompletedEvent(
            $aftersales,
            $completionData,
            $context
        );

        $this->eventDispatcher->dispatch($event, AftersalesCompletedEvent::NAME);
    }

    /**
     * 分发售后取消事件
     */
    /**
     * @param array<string, mixed> $context
     */
    public function dispatchCancelled(
        Aftersales $aftersales,
        string $cancelReason,
        ?string $operator = null,
        array $context = [],
    ): void {
        $event = new AftersalesCancelledEvent(
            $aftersales,
            $cancelReason,
            $operator,
            $context
        );

        $this->eventDispatcher->dispatch($event, AftersalesCancelledEvent::NAME);
    }

    /**
     * 批量分发状态变更事件
     */
    /**
     * @param array<Aftersales> $aftersalesList
     * @param array<string, mixed> $context
     */
    public function dispatchBatchStatusChanged(
        array $aftersalesList,
        string $oldStatus,
        string $newStatus,
        string $action,
        array $context = [],
    ): void {
        foreach ($aftersalesList as $aftersales) {
            $this->dispatchStatusChanged(
                $aftersales,
                $oldStatus,
                $newStatus,
                $action,
                array_merge($context, ['batch_operation' => true])
            );
        }
    }

    /**
     * 条件性事件分发
     */
    /**
     * @param array<string, mixed> $eventData
     * @param array<string, mixed> $conditions
     */
    public function dispatchConditional(
        string $eventType,
        Aftersales $aftersales,
        array $eventData = [],
        array $conditions = [],
    ): bool {
        // 检查分发条件
        if (!$this->shouldDispatchEvent($eventType, $aftersales, $conditions)) {
            return false;
        }

        // 根据事件类型分发
        $this->doDispatchEvent($eventType, $aftersales, $eventData);

        return true;
    }

    /**
     * @param array<string, mixed> $eventData
     */
    private function doDispatchEvent(string $eventType, Aftersales $aftersales, array $eventData): void
    {
        match ($eventType) {
            'created' => $this->dispatchAftersalesCreated(
                $aftersales,
                $this->getArrayValue($eventData, 'orderData'),
                $this->getArrayValue($eventData, 'productData'),
                $this->getArrayValue($eventData, 'context')
            ),
            'status_changed' => $this->dispatchStatusChanged(
                $aftersales,
                $this->getStringValue($eventData, 'oldStatus'),
                $this->getStringValue($eventData, 'newStatus'),
                $this->getStringValue($eventData, 'action'),
                $this->getArrayValue($eventData, 'context')
            ),
            'processing' => $this->dispatchProcessing(
                $aftersales,
                $this->getStringValue($eventData, 'processingType'),
                $this->getArrayValue($eventData, 'processingData'),
                $this->getArrayValue($eventData, 'context')
            ),
            'completed' => $this->dispatchCompleted(
                $aftersales,
                $this->getArrayValue($eventData, 'completionData'),
                $this->getArrayValue($eventData, 'context')
            ),
            'cancelled' => $this->dispatchCancelled(
                $aftersales,
                $this->getStringValue($eventData, 'cancelReason'),
                $this->getNullableStringValue($eventData, 'operator'),
                $this->getArrayValue($eventData, 'context')
            ),
            default => throw new InvalidEventTypeException("不支持的事件类型: {$eventType}"),
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function getArrayValue(array $data, string $key): array
    {
        $value = $data[$key] ?? [];

        /** @var array<string, mixed> */
        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getStringValue(array $data, string $key): string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : '';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getNullableStringValue(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
    }

    /**
     * 检查是否应该分发事件
     */
    /**
     * @param array<string, mixed> $conditions
     */
    private function shouldDispatchEvent(
        string $eventType,
        Aftersales $aftersales,
        array $conditions,
    ): bool {
        return $this->isEventEnabled($conditions)
            && $this->isAftersalesTypeAllowed($aftersales, $conditions)
            && $this->isStatusAllowed($aftersales, $conditions)
            && $this->isWithinTimeWindow($aftersales, $conditions);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    private function isEventEnabled(array $conditions): bool
    {
        return !isset($conditions['enabled']) || false !== $conditions['enabled'];
    }

    /**
     * @param array<string, mixed> $conditions
     */
    private function isAftersalesTypeAllowed(Aftersales $aftersales, array $conditions): bool
    {
        if (!isset($conditions['allowed_types'])) {
            return true;
        }

        $aftersalesType = $aftersales->getType();
        $allowedTypes = $conditions['allowed_types'];

        if (!is_array($allowedTypes)) {
            return false;
        }

        return in_array($aftersalesType, $allowedTypes, true);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    private function isStatusAllowed(Aftersales $aftersales, array $conditions): bool
    {
        if (!isset($conditions['allowed_statuses'])) {
            return true;
        }

        $currentStatus = $this->getCurrentStatus($aftersales);
        $allowedStatuses = $conditions['allowed_statuses'];

        if (!is_array($allowedStatuses)) {
            return false;
        }

        return in_array($currentStatus, $allowedStatuses, true);
    }

    /**
     * @param array<string, mixed> $conditions
     */
    private function isWithinTimeWindow(Aftersales $aftersales, array $conditions): bool
    {
        if (!isset($conditions['time_window'])) {
            return true;
        }

        $timeWindow = $conditions['time_window'];
        if (!is_array($timeWindow)) {
            return true;
        }

        $createdAt = $aftersales->getCreateTime();
        $startValue = $timeWindow['start'] ?? '-1 hour';
        $endValue = $timeWindow['end'] ?? '+1 hour';
        $windowStart = new \DateTime(is_string($startValue) ? $startValue : '-1 hour');
        $windowEnd = new \DateTime(is_string($endValue) ? $endValue : '+1 hour');

        return $createdAt >= $windowStart && $createdAt <= $windowEnd;
    }

    /**
     * 获取当前状态（兼容性方法）
     */
    private function getCurrentStatus(Aftersales $aftersales): string
    {
        if (method_exists($aftersales, 'getStatus')) {
            $status = $aftersales->getStatus();

            return is_string($status) ? $status : 'pending';
        }

        return 'pending';
    }
}
