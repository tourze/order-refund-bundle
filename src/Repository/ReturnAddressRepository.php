<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<ReturnAddress>
 */
#[AsRepository(entityClass: ReturnAddress::class)]
class ReturnAddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReturnAddress::class);
    }

    public function save(ReturnAddress $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ReturnAddress $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 获取默认寄回地址
     */
    public function findDefaultAddress(): ?ReturnAddress
    {
        return $this->findOneBy([
            'isDefault' => true,
            'isActive' => true,
        ]);
    }

    /**
     * 获取所有启用的寄回地址，按排序序号排列
     * @return array<ReturnAddress>
     */
    public function findActiveAddresses(): array
    {
        /** @var ReturnAddress[] */
        return $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.sortOrder', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 设置默认地址（确保只有一个默认地址）
     */
    public function setDefaultAddress(ReturnAddress $address): void
    {
        // 先将所有地址的默认状态设为 false
        $this->clearAllDefaultAddresses();

        // 设置指定地址为默认，并确保启用
        $address->setIsDefault(true);
        $address->setIsActive(true);

        $this->save($address, true);
    }

    /**
     * 清除所有默认地址标记
     */
    public function clearAllDefaultAddresses(): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.isDefault', ':default')
            ->setParameter('default', false)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 按地区查找地址
     * @return array<ReturnAddress>
     */
    public function findByRegion(string $province, ?string $city = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->andWhere('r.province = :province')
            ->setParameter('active', true)
            ->setParameter('province', $province)
        ;

        if (null !== $city) {
            $qb->andWhere('r.city = :city')
                ->setParameter('city', $city)
            ;
        }

        /** @var ReturnAddress[] */
        return $qb->orderBy('r.sortOrder', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取启用的地址数量
     */
    public function countActiveAddresses(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 检查是否已有默认地址
     */
    public function hasDefaultAddress(): bool
    {
        return null !== $this->findDefaultAddress();
    }

    /**
     * 获取第一个启用的地址（作为默认地址的备选）
     */
    public function findFirstActiveAddress(): ?ReturnAddress
    {
        /** @var ReturnAddress|null */
        return $this->createQueryBuilder('r')
            ->where('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.sortOrder', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 批量更新地址状态
     * @param array<int> $ids
     */
    public function updateActiveStatus(array $ids, bool $isActive): int
    {
        if ([] === $ids) {
            return 0;
        }

        /** @var int */
        return $this->createQueryBuilder('r')
            ->update()
            ->set('r.isActive', ':active')
            ->where('r.id IN (:ids)')
            ->setParameter('active', $isActive)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 按名称查找地址
     */
    public function findByName(string $name): ?ReturnAddress
    {
        return $this->findOneBy([
            'name' => $name,
            'isActive' => true,
        ]);
    }

    /**
     * 验证地址名称是否已存在
     */
    public function isNameExists(string $name, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.name = :name')
            ->setParameter('name', $name)
        ;

        if (null !== $excludeId) {
            $qb->andWhere('r.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
