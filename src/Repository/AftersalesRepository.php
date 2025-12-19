<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 售后申请仓储类
 *
 * @extends ServiceEntityRepository<Aftersales>
 */
#[AsRepository(entityClass: Aftersales::class)]
final class AftersalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Aftersales::class);
    }

    public function save(Aftersales $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Aftersales $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 批量查询订单商品的售后历史记录
     *
     * @param array<string> $orderProductIds 订单商品ID数组
     * @return array<string, array<array{quantity: int, refundAmount: string}>> 键为orderProductId，值为售后记录数组
     */
    public function findRefundHistoryBatch(array $orderProductIds): array
    {
        if ([] === $orderProductIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('a')
            ->select('a.orderProductId', 'a.quantity', 'a.refundAmount', 'a.state')
            ->where('a.orderProductId IN (:orderProductIds)')
            ->andWhere('a.state NOT IN (:excludedStates)')
            ->setParameter('orderProductIds', $orderProductIds)
            ->setParameter('excludedStates', ['CANCELLED', 'REJECTED'])
        ;

        /** @var array<array{orderProductId: string, quantity: int, refundAmount: string, state: mixed}> $results */
        $results = $qb->getQuery()->getResult();

        $groupedResults = $this->groupRefundHistoryResults($results);

        return $this->ensureAllOrderProductIdsPresent($groupedResults, $orderProductIds);
    }

    /**
     * 将售后历史结果按orderProductId分组
     *
     * @param array<array{orderProductId: string, quantity: int, refundAmount: string, state: mixed}> $results
     * @return array<string, array<array{quantity: int, refundAmount: string}>>
     */
    private function groupRefundHistoryResults(array $results): array
    {
        $grouped = [];
        foreach ($results as $result) {
            // PHPDoc ensures orderProductId key exists with correct type
            $orderProductId = (string) $result['orderProductId'];
            if (!array_key_exists($orderProductId, $grouped)) {
                $grouped[$orderProductId] = [];
            }

            $grouped[$orderProductId][] = [
                'quantity' => (int) $result['quantity'], // PHPDoc ensures type
                'refundAmount' => (string) $result['refundAmount'], // PHPDoc ensures type
            ];
        }

        return $grouped;
    }

    /**
     * 确保所有orderProductId都在结果中，即使是空数组
     *
     * @param array<string, array<array{quantity: int, refundAmount: string}>> $groupedResults
     * @param array<string> $orderProductIds
     * @return array<string, array<array{quantity: int, refundAmount: string}>>
     */
    private function ensureAllOrderProductIdsPresent(array $groupedResults, array $orderProductIds): array
    {
        foreach ($orderProductIds as $orderProductId) {
            if (!array_key_exists($orderProductId, $groupedResults)) {
                $groupedResults[$orderProductId] = [];
            }
        }

        return $groupedResults;
    }

    /**
     * 检查订单是否所有商品都已完成售后
     *
     * @param string $referenceNumber 订单编号
     * @return array{allCompleted: bool, completedCount: int, totalAftersalesCount: int, details: array<string, array<string, mixed>>}
     */
    public function checkOrderAftersalesStatus(string $referenceNumber): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.referenceNumber = :referenceNumber')
            ->andWhere('a.state NOT IN (:excludedStates)')
            ->setParameter('referenceNumber', $referenceNumber)
            ->setParameter('excludedStates', [AftersalesState::CANCELLED->value, AftersalesState::REJECTED->value])
        ;

        /** @var Aftersales[] $aftersalesRecords */
        $aftersalesRecords = $qb->getQuery()->getResult();

        $details = [];
        $completedCount = 0;
        $totalCount = count($aftersalesRecords);

        foreach ($aftersalesRecords as $record) {
            $orderProductId = $record->getOrderProductId();
            if (null === $orderProductId) {
                continue;
            }

            $state = $record->getState()->value;

            if (!array_key_exists($orderProductId, $details)) {
                $details[$orderProductId] = [
                    'states' => [],
                    'hasCompleted' => false,
                    'totalQuantity' => 0,
                ];
            }

            $details[$orderProductId]['states'][] = $state;
            $details[$orderProductId]['totalQuantity'] += $record->getQuantity() ?? 0;

            if ($state === AftersalesState::COMPLETED->value) {
                $details[$orderProductId]['hasCompleted'] = true;
                ++$completedCount;
            }
        }

        // 计算是否所有商品都已完成售后
        $allCompleted = $totalCount > 0 && $completedCount === $totalCount;

        return [
            'allCompleted' => $allCompleted,
            'completedCount' => $completedCount,
            'totalAftersalesCount' => $totalCount,
            'details' => $details,
        ];
    }

    /**
     * 根据关联单号查询售后单状态，按产品ID分组
     * 排除已取消的售后单
     *
     * @param string $referenceNumber 关联单号（订单ID）
     * @return array<string, array<string>> 键为产品ID，值为该产品的有效售后单状态数组
     */
    public function findAftersalesStatusByReferenceNumber(string $referenceNumber): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.productId', 'a.state')
            ->where('a.referenceNumber = :referenceNumber')
            ->andWhere('a.state != :cancelledState')
            ->setParameter('referenceNumber', $referenceNumber)
            ->setParameter('cancelledState', AftersalesState::CANCELLED)
        ;

        /** @var array<array{productId: string, state: mixed}> $results */
        $results = $qb->getQuery()->getArrayResult();

        /** @var array<string, array<string>> $groupedResults */
        $groupedResults = [];
        foreach ($results as $result) {
            // PHPDoc ensures productId key exists with correct type
            $productId = (string) $result['productId'];
            $stateValue = $result['state'] ?? '';
            $state = $stateValue instanceof \BackedEnum
                ? $stateValue->value
                : (is_string($stateValue) ? $stateValue : '');

            if (!array_key_exists($productId, $groupedResults)) {
                $groupedResults[$productId] = [];
            }

            // 确保state是string类型
            $groupedResults[$productId][] = (string) $state;
        }

        return $groupedResults;
    }

    /**
     * 查询指定商品的活跃售后记录（排除已取消和已拒绝的售后）
     *
     * @param array<string> $orderProductIds 订单商品ID数组
     * @return array<string, array<string>> 键为orderProductId，值为该商品的活跃售后单状态数组
     */
    public function findActiveAftersalesByOrderProductIds(array $orderProductIds): array
    {
        if ([] === $orderProductIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('a')
            ->select('a.orderProductId', 'a.state')
            ->where('a.orderProductId IN (:orderProductIds)')
            ->andWhere('a.state NOT IN (:excludedStates)')
            ->setParameter('orderProductIds', $orderProductIds)
            ->setParameter('excludedStates', [
                AftersalesState::CANCELLED,
                AftersalesState::REJECTED,
            ])
        ;

        /** @var array<array{orderProductId: string, state: mixed}> $results */
        $results = $qb->getQuery()->getArrayResult();

        /** @var array<string, array<string>> $groupedResults */
        $groupedResults = [];
        foreach ($results as $result) {
            // PHPDoc ensures orderProductId key exists with correct type
            $orderProductId = (string) $result['orderProductId'];
            $stateValue = $result['state'] ?? '';
            $state = $stateValue instanceof \BackedEnum
                ? $stateValue->value
                : (is_string($stateValue) ? $stateValue : '');

            if (!array_key_exists($orderProductId, $groupedResults)) {
                $groupedResults[$orderProductId] = [];
            }

            // 确保state是string类型
            $groupedResults[$orderProductId][] = (string) $state;
        }

        return $groupedResults;
    }
}
