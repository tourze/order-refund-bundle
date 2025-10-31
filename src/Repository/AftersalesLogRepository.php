<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OrderRefundBundle\Entity\AftersalesLog;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AftersalesLog>
 */
#[AsRepository(entityClass: AftersalesLog::class)]
class AftersalesLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AftersalesLog::class);
    }

    public function save(AftersalesLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AftersalesLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找售后申请的所有日志
     *
     * @return AftersalesLog[]
     */
    public function findByAftersalesId(string $aftersalesId): array
    {
        /** @var AftersalesLog[] */
        return $this->createQueryBuilder('l')
            ->where('l.aftersales = :aftersalesId')
            ->setParameter('aftersalesId', $aftersalesId)
            ->orderBy('l.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定操作类型的日志
     *
     * @return AftersalesLog[]
     */
    public function findByOperatorType(string $operatorType, int $limit = 100): array
    {
        /** @var AftersalesLog[] */
        return $this->createQueryBuilder('l')
            ->where('l.operatorType = :operatorType')
            ->setParameter('operatorType', $operatorType)
            ->orderBy('l.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定操作动作的日志
     *
     * @return AftersalesLog[]
     */
    public function findByAction(AftersalesLogAction $action, int $limit = 100): array
    {
        /** @var AftersalesLog[] */
        return $this->createQueryBuilder('l')
            ->where('l.action = :action')
            ->setParameter('action', $action)
            ->orderBy('l.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定用户的操作日志
     *
     * @return AftersalesLog[]
     */
    public function findByUserId(string $userId, int $limit = 50): array
    {
        /** @var AftersalesLog[] */
        return $this->createQueryBuilder('l')
            ->where('l.operatorType = :operatorType')
            ->andWhere('l.operatorId = :operatorId')
            ->setParameter('operatorType', 'USER')
            ->setParameter('operatorId', $userId)
            ->orderBy('l.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按操作类型统计日志数量
     *
     * @return array<string, int>
     */
    public function getLogStatsByOperatorType(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('l.operatorType, COUNT(l.id) as count')
            ->where('l.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('l.operatorType')
        ;

        $results = $qb->getQuery()->getResult();

        return $this->normalizeStatsResults($results, 'operatorType');
    }

    /**
     * @param mixed $results
     * @return array<string, int>
     */
    private function normalizeStatsResults(mixed $results, string $keyField): array
    {
        $stats = [];
        if (!is_array($results)) {
            return $stats;
        }

        foreach ($results as $result) {
            if (!is_array($result) || !isset($result[$keyField], $result['count'])) {
                continue;
            }

            $count = $result['count'];
            $keyValue = $result[$keyField];
            assert(is_string($keyValue) || is_int($keyValue));
            $stats[(string) $keyValue] = is_numeric($count) ? (int) $count : 0;
        }

        return $stats;
    }

    /**
     * 按操作动作统计日志数量
     *
     * @return array<string, int>
     */
    public function getLogStatsByAction(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('l.action, COUNT(l.id) as count')
            ->where('l.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('l.action')
        ;

        $results = $qb->getQuery()->getResult();

        return $this->normalizeStatsResults($results, 'action');
    }

    /**
     * 查找系统自动操作日志
     *
     * @return AftersalesLog[]
     */
    public function findSystemOperations(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        /** @var AftersalesLog[] */
        return $this->createQueryBuilder('l')
            ->where('l.operatorType = :operatorType')
            ->andWhere('l.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('operatorType', 'SYSTEM')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('l.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找状态变更日志
     *
     * @return AftersalesLog[]
     */
    public function findStateChangeLogs(string $aftersalesId): array
    {
        /** @var AftersalesLog[] */
        return $this->createQueryBuilder('l')
            ->where('l.aftersales = :aftersalesId')
            ->andWhere('l.previousState IS NOT NULL')
            ->andWhere('l.currentState IS NOT NULL')
            ->setParameter('aftersalesId', $aftersalesId)
            ->orderBy('l.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找最近的操作日志
     *
     * @return AftersalesLog[]
     */
    public function findRecentLogs(int $hours = 24, int $limit = 100): array
    {
        $startTime = new \DateTimeImmutable(sprintf('-%d hours', $hours));

        /** @var AftersalesLog[] */
        return $this->createQueryBuilder('l')
            ->where('l.createTime >= :startTime')
            ->setParameter('startTime', $startTime)
            ->orderBy('l.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 清理过期日志
     */
    public function cleanupExpiredLogs(int $keepDays = 90): int
    {
        $cutoffDate = new \DateTimeImmutable(sprintf('-%d days', $keepDays));

        $result = $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createTime < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute()
        ;

        return is_int($result) ? $result : 0;
    }
}
