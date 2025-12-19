<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OrderRefundBundle\Entity\ExchangeOrder;
use Tourze\OrderRefundBundle\Enum\ExchangeStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<ExchangeOrder>
 */
#[AsRepository(entityClass: ExchangeOrder::class)]
final class ExchangeOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeOrder::class);
    }

    public function save(ExchangeOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ExchangeOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找需要用户操作的换货订单
     *
     * @return ExchangeOrder[]
     */
    public function findPendingUserAction(): array
    {
        /** @var ExchangeOrder[] */
        return $this->createQueryBuilder('e')
            ->where('e.status IN (:statuses)')
            ->setParameter('statuses', [
                ExchangeStatus::PENDING,
                ExchangeStatus::APPROVED,
            ])
            ->orderBy('e.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找需要商家处理的换货订单
     *
     * @return ExchangeOrder[]
     */
    public function findPendingMerchantAction(): array
    {
        /** @var ExchangeOrder[] */
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', ExchangeStatus::RETURN_RECEIVED)
            ->orderBy('e.returnReceiveTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找超时未处理的换货订单
     *
     * @return ExchangeOrder[]
     */
    public function findTimeoutPending(int $timeoutHours = 168): array // 7天
    {
        $cutoffTime = new \DateTimeImmutable(sprintf('-%d hours', $timeoutHours));

        /** @var ExchangeOrder[] */
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.createTime <= :cutoffTime')
            ->setParameter('status', ExchangeStatus::PENDING)
            ->setParameter('cutoffTime', $cutoffTime)
            ->orderBy('e.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按换货单号查找
     */
    public function findByExchangeNo(string $exchangeNo): ?ExchangeOrder
    {
        return $this->findOneBy(['exchangeNo' => $exchangeNo]);
    }

    /**
     * 查找售后申请的所有换货订单
     *
     * @return ExchangeOrder[]
     */
    public function findByAftersalesId(string $aftersalesId): array
    {
        /** @var ExchangeOrder[] */
        return $this->createQueryBuilder('e')
            ->where('e.aftersales = :aftersalesId')
            ->setParameter('aftersalesId', $aftersalesId)
            ->orderBy('e.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按状态统计换货数量
     *
     * @return array<string, int>
     */
    public function getExchangeStatsByStatus(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e.status, COUNT(e.id) as count')
            ->where('e.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('e.status')
        ;

        /** @var list<array{status: string, count: string}> */
        $results = $qb->getQuery()->getResult();

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['status']] = (int) $result['count'];
        }

        return $stats;
    }

    /**
     * 查找有价格差额的换货订单
     *
     * @return ExchangeOrder[]
     */
    public function findWithPriceDifference(bool $needsPayment = true): array
    {
        $operator = $needsPayment ? '>' : '<';

        /** @var ExchangeOrder[] */
        return $this->createQueryBuilder('e')
            ->where("e.priceDifference {$operator} :zero")
            ->setParameter('zero', '0.00')
            ->orderBy('e.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找正在处理中的换货订单
     *
     * @return ExchangeOrder[]
     */
    public function findProcessingExchanges(): array
    {
        /** @var ExchangeOrder[] */
        return $this->createQueryBuilder('e')
            ->where('e.status IN (:statuses)')
            ->setParameter('statuses', [
                ExchangeStatus::RETURN_SHIPPED,
                ExchangeStatus::RETURN_RECEIVED,
                ExchangeStatus::EXCHANGE_SHIPPED,
            ])
            ->orderBy('e.updateTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定用户的换货订单
     *
     * @return ExchangeOrder[]
     */
    public function findByUserId(string $userId, int $limit = 20): array
    {
        /** @var ExchangeOrder[] */
        return $this->createQueryBuilder('e')
            ->join('e.aftersales', 'a')
            ->where('a.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
}
