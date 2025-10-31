<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OrderRefundBundle\Entity\RefundOrder;
use Tourze\OrderRefundBundle\Enum\RefundStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<RefundOrder>
 */
#[AsRepository(entityClass: RefundOrder::class)]
class RefundOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefundOrder::class);
    }

    public function save(RefundOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RefundOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找待处理的退款订单
     *
     * @return RefundOrder[]
     */
    public function findPendingRefunds(int $limit = 100): array
    {
        /** @var RefundOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', RefundStatus::PENDING)
            ->orderBy('r.createTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找失败可重试的退款订单
     *
     * @return RefundOrder[]
     */
    public function findRetryableRefunds(int $limit = 50): array
    {
        /** @var RefundOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.retryCount < :maxRetry')
            ->setParameter('status', RefundStatus::FAILED)
            ->setParameter('maxRetry', 3)
            ->orderBy('r.updateTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按支付方式统计退款金额
     *
     * @return array<string, array{count: int, total_amount: float}>
     */
    public function getRefundStatsByPaymentMethod(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.paymentMethod, COUNT(r.id) as count, SUM(r.refundAmount) as total_amount')
            ->where('r.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('r.paymentMethod')
        ;

        /** @var list<array{paymentMethod: string, count: string, total_amount: string}> */
        $results = $qb->getQuery()->getResult();

        $stats = [];
        foreach ($results as $result) {
            $stats[$result['paymentMethod']] = [
                'count' => (int) $result['count'],
                'total_amount' => (float) $result['total_amount'],
            ];
        }

        return $stats;
    }

    /**
     * 查找超时未处理的退款
     *
     * @return RefundOrder[]
     */
    public function findTimeoutRefunds(int $timeoutHours = 24): array
    {
        $cutoffTime = new \DateTimeImmutable(sprintf('-%d hours', $timeoutHours));

        /** @var RefundOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.createTime <= :cutoffTime')
            ->setParameter('status', RefundStatus::PENDING)
            ->setParameter('cutoffTime', $cutoffTime)
            ->orderBy('r.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按退款单号查找
     */
    public function findByRefundNo(string $refundNo): ?RefundOrder
    {
        return $this->findOneBy(['refundNo' => $refundNo]);
    }

    /**
     * 查找售后申请的所有退款订单
     *
     * @return RefundOrder[]
     */
    public function findByAftersalesId(string $aftersalesId): array
    {
        /** @var RefundOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.aftersales = :aftersalesId')
            ->setParameter('aftersalesId', $aftersalesId)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
