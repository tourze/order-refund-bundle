<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Enum\ReturnStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<ReturnOrder>
 */
#[AsRepository(entityClass: ReturnOrder::class)]
final class ReturnOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReturnOrder::class);
    }

    public function save(ReturnOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReturnOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找需要更新物流信息的退货订单
     *
     * @return ReturnOrder[]
     */
    public function findNeedTrackingUpdate(): array
    {
        /** @var ReturnOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->andWhere('r.trackingNo IS NOT NULL')
            ->setParameter('statuses', [
                ReturnStatus::SHIPPED,
                ReturnStatus::IN_TRANSIT,
            ])
            ->orderBy('r.updateTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找超时未发货的退货订单
     *
     * @return ReturnOrder[]
     */
    public function findTimeoutPendingReturns(int $timeoutHours = 168): array // 7天
    {
        $cutoffTime = new \DateTimeImmutable(sprintf('-%d hours', $timeoutHours));

        /** @var ReturnOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.createTime <= :cutoffTime')
            ->setParameter('status', ReturnStatus::PENDING)
            ->setParameter('cutoffTime', $cutoffTime)
            ->orderBy('r.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找需要商家处理的退货订单
     *
     * @return ReturnOrder[]
     */
    public function findPendingMerchantAction(): array
    {
        /** @var ReturnOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', ReturnStatus::RECEIVED)
            ->orderBy('r.receiveTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按退货单号查找
     */
    public function findByReturnNo(string $returnNo): ?ReturnOrder
    {
        return $this->findOneBy(['returnNo' => $returnNo]);
    }

    /**
     * 查找售后申请的所有退货订单
     *
     * @return ReturnOrder[]
     */
    public function findByAftersalesId(string $aftersalesId): array
    {
        /** @var ReturnOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.aftersales = :aftersalesId')
            ->setParameter('aftersalesId', $aftersalesId)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按状态统计退货数量
     *
     * @return array<string, int>
     */
    public function getReturnStatsByStatus(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->where('r.createTime BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('r.status')
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
     * 查找指定快递公司的退货订单
     *
     * @return ReturnOrder[]
     */
    public function findByExpressCompany(string $expressCompany): array
    {
        /** @var ReturnOrder[] */
        return $this->createQueryBuilder('r')
            ->where('r.expressCompany = :company')
            ->setParameter('company', $expressCompany)
            ->orderBy('r.shipTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
