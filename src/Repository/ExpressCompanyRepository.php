<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<ExpressCompany>
 */
#[AsRepository(entityClass: ExpressCompany::class)]
class ExpressCompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpressCompany::class);
    }

    public function save(ExpressCompany $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ExpressCompany $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 获取所有启用的快递公司，按排序序号排列
     * @return array<ExpressCompany>
     */
    public function findActiveCompanies(): array
    {
        /** @var ExpressCompany[] */
        return $this->createQueryBuilder('e')
            ->where('e.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据代码查找快递公司
     */
    public function findByCode(string $code): ?ExpressCompany
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * 获取所有快递公司，按排序序号排列
     * @return array<ExpressCompany>
     */
    public function findAllOrdered(): array
    {
        /** @var ExpressCompany[] */
        return $this->createQueryBuilder('e')
            ->orderBy('e.sortOrder', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 检查代码是否已存在
     */
    public function isCodeExists(string $code, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.code = :code')
            ->setParameter('code', $code)
        ;

        if (null !== $excludeId) {
            $qb->andWhere('e.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * 获取启用的快递公司数量
     */
    public function countActiveCompanies(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 批量更新快递公司状态
     * @param array<int|string> $ids
     */
    public function updateActiveStatus(array $ids, bool $isActive): int
    {
        if ([] === $ids) {
            return 0;
        }

        // 将所有 ID 转换为字符串以匹配 Snowflake ID 类型
        $stringIds = array_map('strval', $ids);

        /** @var int */
        return $this->createQueryBuilder('e')
            ->update()
            ->set('e.isActive', ':active')
            ->where('e.id IN (:ids)')
            ->setParameter('active', $isActive)
            ->setParameter('ids', $stringIds)
            ->getQuery()
            ->execute()
        ;
    }
}
