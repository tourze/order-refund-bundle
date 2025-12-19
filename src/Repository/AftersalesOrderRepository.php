<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<AftersalesOrder>
 */
#[AsRepository(entityClass: AftersalesOrder::class)]
final class AftersalesOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AftersalesOrder::class);
    }

    /**
     * @return array<AftersalesOrder>
     */
    public function findByOrderNumber(string $orderNumber): array
    {
        return $this->findBy(['orderNumber' => $orderNumber]);
    }

    public function save(AftersalesOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AftersalesOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
