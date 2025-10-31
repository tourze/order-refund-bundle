<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesLog;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Repository\AftersalesLogRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesLogRepository::class)]
#[RunTestsInSeparateProcesses]
class AftersalesLogRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createNewEntity(): AftersalesLog
    {
        // 创建依赖的Aftersales实体
        $aftersales = $this->createValidAftersales();

        // 持久化Aftersales
        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        // 创建AftersalesLog
        $log = new AftersalesLog();
        $log->setAftersales($aftersales);
        $log->setAction(AftersalesLogAction::CREATE);
        $log->setOperatorType('USER');
        $log->setOperatorId('user-' . uniqid());
        $log->setOperatorName('Test User');
        $log->setContent('Test log content');
        $log->setContextData(['key' => 'value']);

        return $log;
    }

    protected function getRepository(): AftersalesLogRepository
    {
        $repository = self::getContainer()->get(AftersalesLogRepository::class);
        $this->assertInstanceOf(AftersalesLogRepository::class, $repository);

        return $repository;
    }

    protected function onSetUp(): void
    {
        // 在这里可以设置测试前的准备工作
    }

    public function testFindByOperatorType(): void
    {
        $repository = $this->getRepository();
        $operatorType = 'USER';
        $limit = 50;

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $repository->save($entity);

        $result = $repository->findByOperatorType($operatorType, $limit);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByOperatorTypeWithDefaultLimit(): void
    {
        $repository = $this->getRepository();
        $operatorType = 'SYSTEM';

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $entity->setSystemOperator();
        $repository->save($entity);

        $result = $repository->findByOperatorType($operatorType);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByAction(): void
    {
        $repository = $this->getRepository();
        $action = AftersalesLogAction::APPROVE;
        $limit = 20;

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $entity->setAction($action);
        $repository->save($entity);

        $result = $repository->findByAction($action, $limit);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByActionWithDefaultLimit(): void
    {
        $repository = $this->getRepository();
        $action = AftersalesLogAction::REJECT;

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $entity->setAction($action);
        $repository->save($entity);

        $result = $repository->findByAction($action);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testFindByUserId(): void
    {
        $repository = $this->getRepository();
        $userId = 'user-123';
        $limit = 30;

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $entity->setOperatorId($userId);
        $repository->save($entity);

        $result = $repository->findByUserId($userId, $limit);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testGetLogStatsByOperatorType(): void
    {
        $repository = $this->getRepository();

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $result = $repository->getLogStatsByOperatorType($startDate, $endDate);

        $this->assertIsArray($result);
    }

    public function testFindSystemOperations(): void
    {
        $repository = $this->getRepository();

        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $entity->setSystemOperator();
        $repository->save($entity);

        $result = $repository->findSystemOperations($startDate, $endDate);

        $this->assertIsArray($result);
    }

    public function testFindRecentLogs(): void
    {
        $repository = $this->getRepository();
        $hours = 48;
        $limit = 200;

        // 先保存一些测试数据
        $entity = $this->createNewEntity();
        $repository->save($entity);

        $result = $repository->findRecentLogs($hours, $limit);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));
    }

    public function testCleanupExpiredLogs(): void
    {
        $repository = $this->getRepository();
        $keepDays = 180;

        $result = $repository->cleanupExpiredLogs($keepDays);

        $this->assertIsInt($result);
    }

    public function testEmptyResultsHandling(): void
    {
        $repository = $this->getRepository();

        // 测试统计方法返回空结果的情况
        $startDate = new \DateTimeImmutable('2020-01-01');
        $endDate = new \DateTimeImmutable('2020-01-02');

        $stats = $repository->getLogStatsByOperatorType($startDate, $endDate);
        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    public function testFindByAftersalesIdReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findByAftersalesId('non-existent-id');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindStateChangeLogsReturnsArray(): void
    {
        $repository = $this->getRepository();

        // 测试方法存在并返回数组
        $result = $repository->findStateChangeLogs('non-existent-id');
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testRemoveMethod(): void
    {
        $repository = $this->getRepository();

        // 先保存一个实体
        $entity = $this->createNewEntity();
        $repository->save($entity, false);

        // 然后删除它
        $repository->remove($entity, true);

        // 验证实体已被删除
        $this->assertNull($repository->find($entity->getId()));
    }

    private function createValidAftersales(): Aftersales
    {
        $aftersales = new Aftersales();
        $aftersales->setType(AftersalesType::REFUND_ONLY);
        $aftersales->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales->setReferenceNumber('REF-' . uniqid());
        $aftersales->setOrderProductId('order_product_' . uniqid());
        $aftersales->setProductId('product_' . uniqid());
        $aftersales->setSkuId('sku_' . uniqid());
        $aftersales->setProductName('Test Product Name');
        $aftersales->setSkuName('Test SKU Name');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('90.00');
        $aftersales->setRefundAmount('90.00');
        $aftersales->setOriginalRefundAmount('90.00');
        $aftersales->setActualRefundAmount('90.00');

        return $aftersales;
    }
}
