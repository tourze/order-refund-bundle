<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\OrderRefundBundle\Command\InitExpressCompaniesCommand;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(InitExpressCompaniesCommand::class)]
#[RunTestsInSeparateProcesses]
class InitExpressCompaniesCommandTest extends AbstractCommandTestCase
{
    private ExpressCompanyRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ExpressCompanyRepository::class);

        // 清空快递公司表
        $this->clearExpressCompanies();
    }

    protected function onTearDown(): void
    {
        // 清理测试数据
        $this->clearExpressCompanies();
    }

    private function clearExpressCompanies(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM ' . ExpressCompany::class)->execute();
        self::getEntityManager()->clear();
    }

    private function createExpressCompany(
        string $code,
        string $name,
        bool $isActive = true,
        int $sortOrder = 0
    ): ExpressCompany {
        $company = new ExpressCompany();
        $company->setCode($code);
        $company->setName($name);
        $company->setIsActive($isActive);
        $company->setSortOrder($sortOrder);

        $this->repository->save($company, true);

        return $company;
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(InitExpressCompaniesCommand::class);
        $this->assertInstanceOf(InitExpressCompaniesCommand::class, $command);

        return new CommandTester($command);
    }

    public function testExecuteWithEmptyDatabase(): void
    {
        // 确认数据库为空
        $this->assertSame(0, $this->repository->count([]));

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        // 验证输出包含创建信息
        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('已创建: 顺丰 (SF)', $display);
        $this->assertStringContainsString('初始化完成！创建: 15，更新: 0，跳过: 0', $display);

        // 验证数据库中确实创建了15个记录
        $this->assertSame(15, $this->repository->count([]));

        // 验证启用的公司数量
        $this->assertSame(15, $this->repository->countActiveCompanies());

        // 验证顺丰快递的数据
        $sfExpress = $this->repository->findByCode('SF');
        $this->assertNotNull($sfExpress);
        $this->assertSame('顺丰', $sfExpress->getName());
        $this->assertTrue($sfExpress->isActive());
    }

    public function testExecuteWithExistingDataAndNoForce(): void
    {
        // 创建5个快递公司记录
        for ($i = 1; $i <= 5; ++$i) {
            $this->createExpressCompany("TEST{$i}", "测试快递{$i}");
        }

        $this->assertSame(5, $this->repository->count([]));

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['no']); // 用户选择不继续

        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('系统中已存在 5 个快递公司记录', $display);

        // 确认没有创建新记录
        $this->assertSame(5, $this->repository->count([]));
    }

    public function testExecuteWithExistingDataAndContinue(): void
    {
        // 创建5个快递公司记录
        for ($i = 1; $i <= 5; ++$i) {
            $this->createExpressCompany("TEST{$i}", "测试快递{$i}");
        }

        $this->assertSame(5, $this->repository->count([]));

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['yes']); // 用户选择继续

        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('初始化完成！创建: 15，更新: 0，跳过: 0', $display);

        // 验证数据库中现在有20个记录（5个旧的 + 15个新的）
        $this->assertSame(20, $this->repository->count([]));
    }

    public function testExecuteWithForceOption(): void
    {
        // 创建一个已存在的顺丰快递记录（使用不同的数据）
        $existingCompany = $this->createExpressCompany('SF', '旧的顺丰名称', false, 999);
        $existingId = $existingCompany->getId();

        $this->assertSame(1, $this->repository->count([]));

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('已更新: 顺丰 (SF)', $display);
        $this->assertStringContainsString('初始化完成！创建: 14，更新: 1，跳过: 0', $display);

        // 验证数据库中有15个记录
        $this->assertSame(15, $this->repository->count([]));

        // 重新获取顺丰快递并验证已更新
        self::getEntityManager()->clear();
        $updatedCompany = $this->repository->findByCode('SF');

        $this->assertNotNull($updatedCompany);
        $this->assertSame($existingId, $updatedCompany->getId()); // ID未变
        $this->assertSame('顺丰', $updatedCompany->getName()); // 名称已更新
        $this->assertSame('https://www.sf-express.com/chn/sc/dynamic_function/waybill/#search/bill-number/%s', $updatedCompany->getTrackingUrlTemplate());
        $this->assertSame(1, $updatedCompany->getSortOrder()); // 排序已更新
        $this->assertTrue($updatedCompany->isActive()); // 状态已更新为启用
    }

    public function testExecuteWithInactiveOption(): void
    {
        // 确认数据库为空
        $this->assertSame(0, $this->repository->count([]));

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--inactive' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('初始化完成！创建: 15，更新: 0，跳过: 0', $display);

        // 验证数据库中有15个记录
        $this->assertSame(15, $this->repository->count([]));

        // 验证所有公司都是未启用状态
        $this->assertSame(0, $this->repository->countActiveCompanies());

        // 验证顺丰快递是未启用状态
        $sfExpress = $this->repository->findByCode('SF');
        $this->assertNotNull($sfExpress);
        $this->assertFalse($sfExpress->isActive());
    }

    public function testExecuteWithSkippedExisting(): void
    {
        // 创建一个已存在的顺丰快递记录
        $existingCompany = $this->createExpressCompany('SF', '顺丰速运');
        $existingId = $existingCompany->getId();

        $this->assertSame(1, $this->repository->count([]));

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['yes']); // 用户选择继续

        // 不使用 --force 选项，应该跳过已存在的记录
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $display = $commandTester->getDisplay();
        $this->assertStringContainsString('已跳过: 顺丰 (SF) - 已存在', $display);
        $this->assertStringContainsString('初始化完成！创建: 14，更新: 0，跳过: 1', $display);

        // 验证数据库中有15个记录
        $this->assertSame(15, $this->repository->count([]));

        // 验证顺丰快递未被修改
        self::getEntityManager()->clear();
        $unchangedCompany = $this->repository->findByCode('SF');

        $this->assertNotNull($unchangedCompany);
        $this->assertSame($existingId, $unchangedCompany->getId());
        $this->assertSame('顺丰速运', $unchangedCompany->getName()); // 名称未改变
    }

    public function testOptionForce(): void
    {
        // 创建一个快递公司记录（非预定义代码）
        $this->createExpressCompany('TEST1', '测试快递1');

        $this->assertSame(1, $this->repository->count([]));

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        // 验证命令成功执行
        // 期望有16个记录：15个预定义 + 1个TEST1（不会被删除）
        $this->assertSame(16, $this->repository->count([]));
        // 启用的只有15个（TEST1默认启用）+ 预定义的15个 = 16个
        $this->assertSame(16, $this->repository->countActiveCompanies());
    }

    public function testOptionInactive(): void
    {
        // 确认数据库为空
        $this->assertSame(0, $this->repository->count([]));

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--inactive' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        // 验证命令成功执行
        $this->assertSame(15, $this->repository->count([]));
        $this->assertSame(0, $this->repository->countActiveCompanies());
    }
}
