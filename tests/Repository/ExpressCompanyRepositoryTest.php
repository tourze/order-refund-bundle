<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ExpressCompanyRepository::class)]
#[RunTestsInSeparateProcesses]
final class ExpressCompanyRepositoryTest extends AbstractRepositoryTestCase
{
    private ExpressCompanyRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ExpressCompanyRepository::class);
    }

    protected function createNewEntity(): ExpressCompany
    {
        $entity = new ExpressCompany();
        $entity->setCode('TEST' . uniqid());
        $entity->setName('测试快递公司');

        return $entity;
    }

    protected function getRepository(): ExpressCompanyRepository
    {
        return $this->repository;
    }

    private function uniqueCode(string $prefix = 'CODE'): string
    {
        return $prefix . uniqid();
    }

    public function testSave(): void
    {
        $code = 'SF' . uniqid();
        $entity = new ExpressCompany();
        $entity->setCode($code);
        $entity->setName('顺丰速运');
        $entity->setTrackingUrlTemplate('https://www.sf-express.com/track/{code}');
        $entity->setIsActive(true);
        $entity->setSortOrder(100);
        $entity->setDescription('全国性快递公司');

        $this->repository->save($entity, true);

        $this->assertNotNull($entity->getId());

        // 验证数据已保存到数据库
        $savedEntity = $this->repository->find($entity->getId());
        $this->assertInstanceOf(ExpressCompany::class, $savedEntity);
        $this->assertSame($code, $savedEntity->getCode());
        $this->assertSame('顺丰速运', $savedEntity->getName());
        $this->assertTrue($savedEntity->isActive());
    }

    public function testRemove(): void
    {
        $entity = new ExpressCompany();
        $entity->setCode($this->uniqueCode('YTO'));
        $entity->setName('圆通速递');

        $this->repository->save($entity, true);
        $entityId = $entity->getId();

        $this->assertNotNull($entityId);

        // 删除实体
        $this->repository->remove($entity, true);

        // 验证实体已被删除
        $deletedEntity = $this->repository->find($entityId);
        $this->assertNull($deletedEntity);
    }

    public function testFindActiveCompanies(): void
    {
        // 清空现有数据，确保测试独立
        $this->clearTable();

        // 创建测试数据
        $code1 = $this->uniqueCode('SF');
        $code2 = $this->uniqueCode('YTO');

        $activeCompany1 = new ExpressCompany();
        $activeCompany1->setCode($code1);
        $activeCompany1->setName('顺丰速运');
        $activeCompany1->setIsActive(true);
        $activeCompany1->setSortOrder(1);

        $activeCompany2 = new ExpressCompany();
        $activeCompany2->setCode($code2);
        $activeCompany2->setName('圆通速递');
        $activeCompany2->setIsActive(true);
        $activeCompany2->setSortOrder(2);

        $inactiveCompany = new ExpressCompany();
        $inactiveCompany->setCode($this->uniqueCode('INACTIVE'));
        $inactiveCompany->setName('已停用快递');
        $inactiveCompany->setIsActive(false);
        $inactiveCompany->setSortOrder(0);

        $this->repository->save($activeCompany1, false);
        $this->repository->save($activeCompany2, false);
        $this->repository->save($inactiveCompany, true);

        // 测试查询结果
        $result = $this->repository->findActiveCompanies();

        $this->assertCount(2, $result);
        $this->assertSame($code1, $result[0]->getCode());
        $this->assertSame($code2, $result[1]->getCode());
    }

    public function testFindActiveCompaniesOrderedCorrectly(): void
    {
        // 清空现有数据，确保测试独立
        $this->clearTable();

        // 创建测试数据（颠倒创建顺序来测试排序）
        $company1 = new ExpressCompany();
        $company1->setCode('C');
        $company1->setName('C快递');
        $company1->setIsActive(true);
        $company1->setSortOrder(3);

        $company2 = new ExpressCompany();
        $company2->setCode('A');
        $company2->setName('A快递');
        $company2->setIsActive(true);
        $company2->setSortOrder(1);

        $company3 = new ExpressCompany();
        $company3->setCode('B');
        $company3->setName('B快递');
        $company3->setIsActive(true);
        $company3->setSortOrder(2);

        $this->repository->save($company1, false);
        $this->repository->save($company2, false);
        $this->repository->save($company3, true);

        $result = $this->repository->findActiveCompanies();

        $this->assertCount(3, $result);
        // 应该按sortOrder ASC, name ASC排序
        $this->assertSame('A', $result[0]->getCode());
        $this->assertSame('B', $result[1]->getCode());
        $this->assertSame('C', $result[2]->getCode());
    }

    public function testFindByCode(): void
    {
        $code = $this->uniqueCode('TESTCODE');

        $entity = new ExpressCompany();
        $entity->setCode($code);
        $entity->setName('测试快递');

        $this->repository->save($entity, true);

        // 测试查找存在的代码
        $found = $this->repository->findByCode($code);
        $this->assertInstanceOf(ExpressCompany::class, $found);
        $this->assertSame('测试快递', $found->getName());

        // 测试查找不存在的代码
        $notFound = $this->repository->findByCode('NONEXISTENT');
        $this->assertNull($notFound);
    }

    public function testFindAllOrdered(): void
    {
        // 清空现有数据，确保测试独立
        $this->clearTable();

        // 创建测试数据
        $company1 = new ExpressCompany();
        $company1->setCode('C');
        $company1->setName('C快递');
        $company1->setIsActive(false);
        $company1->setSortOrder(3);

        $company2 = new ExpressCompany();
        $company2->setCode('A');
        $company2->setName('A快递');
        $company2->setIsActive(true);
        $company2->setSortOrder(1);

        $company3 = new ExpressCompany();
        $company3->setCode('B');
        $company3->setName('B快递');
        $company3->setIsActive(true);
        $company3->setSortOrder(2);

        $this->repository->save($company1, false);
        $this->repository->save($company2, false);
        $this->repository->save($company3, true);

        $result = $this->repository->findAllOrdered();

        $this->assertCount(3, $result);
        // 应该按sortOrder ASC, name ASC排序，包含未激活的
        $this->assertSame('A', $result[0]->getCode());
        $this->assertSame('B', $result[1]->getCode());
        $this->assertSame('C', $result[2]->getCode());
    }

    public function testIsCodeExists(): void
    {
        $code = $this->uniqueCode('TESTEXIST');

        $entity = new ExpressCompany();
        $entity->setCode($code);
        $entity->setName('测试快递');

        $this->repository->save($entity, true);
        $entityId = $entity->getId();

        // 测试代码存在
        $this->assertTrue($this->repository->isCodeExists($code));

        // 测试代码不存在
        $this->assertFalse($this->repository->isCodeExists('NONEXISTENT'));

        // 测试排除自身ID
        $this->assertFalse($this->repository->isCodeExists($code, (int) $entityId));

        // 测试排除其他ID
        $this->assertTrue($this->repository->isCodeExists($code, 999));
    }

    public function testCountActiveCompanies(): void
    {
        // 清空现有数据
        $this->clearTable();

        $activeCompany1 = new ExpressCompany();
        $activeCompany1->setCode('SF');
        $activeCompany1->setName('顺丰速运');
        $activeCompany1->setIsActive(true);

        $activeCompany2 = new ExpressCompany();
        $activeCompany2->setCode('YTO');
        $activeCompany2->setName('圆通速递');
        $activeCompany2->setIsActive(true);

        $inactiveCompany = new ExpressCompany();
        $inactiveCompany->setCode('INACTIVE');
        $inactiveCompany->setName('已停用快递');
        $inactiveCompany->setIsActive(false);

        $this->repository->save($activeCompany1, false);
        $this->repository->save($activeCompany2, false);
        $this->repository->save($inactiveCompany, true);

        $count = $this->repository->countActiveCompanies();
        $this->assertSame(2, $count);
    }

    /**
     * @param array<int> $ids
     */
    #[DataProvider('updateActiveStatusProvider')]
    public function testUpdateActiveStatus(array $ids, bool $isActive, int $expectedUpdated): void
    {
        // 创建测试数据
        $companies = [];
        for ($i = 1; $i <= 3; ++$i) {
            $company = new ExpressCompany();
            $company->setCode("CODE{$i}");
            $company->setName("快递{$i}");
            $company->setIsActive(!$isActive); // 初始状态相反
            $this->repository->save($company, false);
            $companies[] = $company;
        }
        $lastCompany = end($companies);
        if (false !== $lastCompany) {
            $this->repository->save($lastCompany, true);
        }

        // 获取实际的ID
        /** @var array<int> $actualIds */
        $actualIds = array_slice(array_map(fn ($c) => (int) $c->getId(), $companies), 0, count($ids));

        // 执行批量更新
        $updated = $this->repository->updateActiveStatus($actualIds, $isActive);
        $this->assertSame($expectedUpdated, $updated);

        // 清空实体管理器缓存，确保从数据库重新加载实体
        // 因为 DQL UPDATE 不会更新已加载的实体对象
        self::getEntityManager()->clear();

        // 验证更新结果
        for ($i = 0; $i < count($actualIds); ++$i) {
            $company = $this->repository->find($actualIds[$i]);
            $this->assertNotNull($company);
            $this->assertSame($isActive, $company->isActive());
        }

        // 验证未更新的记录
        for ($i = count($actualIds); $i < count($companies); ++$i) {
            $company = $this->repository->find($companies[$i]->getId());
            $this->assertNotNull($company);
            $this->assertSame(!$isActive, $company->isActive());
        }
    }

    /**
     * @return array<string, array{array<int>, bool, int}>
     */
    public static function updateActiveStatusProvider(): array
    {
        return [
            'empty_array' => [[], true, 0],
            'single_id_activate' => [[0], true, 1],
            'single_id_deactivate' => [[0], false, 1],
            'multiple_ids_activate' => [[0, 1], true, 2],
            'multiple_ids_deactivate' => [[0, 1, 2], false, 3],
        ];
    }

    public function testFindActiveCompaniesSortsByNameWhenSortOrderEqual(): void
    {
        // 清空现有数据，确保测试独立
        $this->clearTable();

        $company1 = new ExpressCompany();
        $company1->setCode('ZTO');
        $company1->setName('ZTO Express');
        $company1->setIsActive(true);
        $company1->setSortOrder(1);

        $company2 = new ExpressCompany();
        $company2->setCode('SF');
        $company2->setName('SF Express');
        $company2->setIsActive(true);
        $company2->setSortOrder(1);

        $this->repository->save($company1, false);
        $this->repository->save($company2, true);

        $result = $this->repository->findActiveCompanies();

        $this->assertCount(2, $result);
        // 当sortOrder相同时，应按name ASC排序
        $this->assertSame('SF Express', $result[0]->getName());
        $this->assertSame('ZTO Express', $result[1]->getName());
    }

    private function clearTable(): void
    {
        $entityManager = self::getEntityManager();
        $connection = $entityManager->getConnection();
        $connection->executeStatement('DELETE FROM order_express_companies');
    }
}
