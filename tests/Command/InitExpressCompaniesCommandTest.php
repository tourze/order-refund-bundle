<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
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
    /** @phpstan-ignore property.onlyRead */
    private ExpressCompanyRepository $repository;

    protected function onSetUp(): void
    {
        // @phpstan-ignore method.notFound
        $this->repository = new class extends ExpressCompanyRepository {
            /** @var array<string, list<mixed>> */
            private array $methodCalls = [];

            /** @var array<int> */
            private array $countReturnValues = [];

            /** @var array<?ExpressCompany> */
            private array $findByCodeReturnValues = [];

            /** @var array<int> */
            private array $countActiveCompaniesReturnValues = [];

            private int $countCallIndex = 0;

            private int $findByCodeCallIndex = 0;

            // @phpstan-ignore-next-line constructor.missingParentCall
            public function __construct()
            {
                // Skip parent constructor to avoid dependencies
            }

            public function setExpectation(string $method, mixed $returnValue): void
            {
                switch ($method) {
                    case 'count':
                        if (!is_int($returnValue)) {
                            throw new \InvalidArgumentException('count() expects int, got ' . get_debug_type($returnValue));
                        }
                        $this->countReturnValues[] = $returnValue;
                        break;

                    case 'findByCode':
                        if (!($returnValue instanceof ExpressCompany || null === $returnValue)) {
                            throw new \InvalidArgumentException('findByCode() expects ExpressCompany|null, got ' . get_debug_type($returnValue));
                        }
                        $this->findByCodeReturnValues[] = $returnValue;
                        break;

                    case 'countActiveCompanies':
                        if (!is_int($returnValue)) {
                            throw new \InvalidArgumentException('countActiveCompanies() expects int, got ' . get_debug_type($returnValue));
                        }
                        $this->countActiveCompaniesReturnValues[] = $returnValue;
                        break;

                    default:
                        throw new \InvalidArgumentException('Unknown method expectation: ' . $method);
                }
            }

            /**
             * @param array<mixed> $criteria
             * @return int<0, max>
             */
            public function count(array $criteria = []): int
            {
                $this->methodCalls['count'][] = $criteria;
                $index = $this->countCallIndex++;
                $value = $this->countReturnValues[$index] ?? 0;

                return max(0, $value);
            }

            public function findByCode(string $code): ?ExpressCompany
            {
                $this->methodCalls['findByCode'][] = $code;
                $index = $this->findByCodeCallIndex++;

                return $this->findByCodeReturnValues[$index] ?? null;
            }

            public function save(ExpressCompany $entity, bool $flush = false): void
            {
                $this->methodCalls['save'][] = [$entity, $flush];
            }

            public function countActiveCompanies(): int
            {
                return $this->countActiveCompaniesReturnValues[0] ?? 0;
            }

            /** @return array<string, list<mixed>> */
            public function getMethodCalls(): array
            {
                return $this->methodCalls;
            }
        };
        self::getContainer()->set(ExpressCompanyRepository::class, $this->repository);
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(InitExpressCompaniesCommand::class);
        $this->assertInstanceOf(InitExpressCompaniesCommand::class, $command);

        return new CommandTester($command);
    }

    public function testExecuteWithEmptyDatabase(): void
    {
        // 模拟空数据库，count方法会被调用两次：检查现有记录 + 输出统计
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 0);  // 第一次返回0（空数据库）
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 15); // 第二次返回15（插入后）

        // 15个默认公司，findByCode都返回null
        for ($i = 0; $i < 15; ++$i) {
            // @phpstan-ignore method.notFound
            $this->repository->setExpectation('findByCode', null);
        }

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('countActiveCompanies', 15);

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('已创建: 顺丰 (SF)', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 15，更新: 0，跳过: 0', $commandTester->getDisplay());
    }

    public function testExecuteWithExistingDataAndNoForce(): void
    {
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 5);

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['no']); // 用户选择不继续

        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('系统中已存在 5 个快递公司记录', $commandTester->getDisplay());
    }

    public function testExecuteWithExistingDataAndContinue(): void
    {
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 5);  // 第一次返回5
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 20); // 第二次返回20

        for ($i = 0; $i < 15; ++$i) {
            // @phpstan-ignore method.notFound
            $this->repository->setExpectation('findByCode', null);
        }

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('countActiveCompanies', 20);

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['yes']); // 用户选择继续

        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('初始化完成！创建: 15，更新: 0，跳过: 0', $commandTester->getDisplay());
    }

    public function testExecuteWithForceOption(): void
    {
        // 创建可以被更新的 ExpressCompany 实体
        $existingCompany = new ExpressCompany();

        // 使用--force选项时count会被调用两次：检查现有记录 + 输出统计
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 1);  // 第一次返回1
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 15); // 第二次返回15

        // 第一个findByCode返回现有公司，其余返回null
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('findByCode', $existingCompany);
        for ($i = 0; $i < 14; ++$i) {
            // @phpstan-ignore method.notFound
            $this->repository->setExpectation('findByCode', null);
        }

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('countActiveCompanies', 15);

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('已更新: 顺丰 (SF)', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 14，更新: 1，跳过: 0', $commandTester->getDisplay());

        // 验证实体被正确更新
        $this->assertSame('顺丰', $existingCompany->getName());
        $this->assertSame('https://www.sf-express.com/chn/sc/dynamic_function/waybill/#search/bill-number/%s', $existingCompany->getTrackingUrlTemplate());
        $this->assertSame(1, $existingCompany->getSortOrder());
        $this->assertTrue($existingCompany->isActive());
    }

    public function testExecuteWithInactiveOption(): void
    {
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 0);  // 第一次返回0
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 15); // 第二次返回15

        for ($i = 0; $i < 15; ++$i) {
            // @phpstan-ignore method.notFound
            $this->repository->setExpectation('findByCode', null);
        }

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('countActiveCompanies', 0);

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--inactive' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('初始化完成！创建: 15，更新: 0，跳过: 0', $commandTester->getDisplay());
    }

    public function testExecuteWithSkippedExisting(): void
    {
        $existingCompany = new ExpressCompany();

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 1);  // 第一次返回1
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 15); // 第二次返回15

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('findByCode', $existingCompany);
        for ($i = 0; $i < 14; ++$i) {
            // @phpstan-ignore method.notFound
            $this->repository->setExpectation('findByCode', null);
        }

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('countActiveCompanies', 14);

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['yes']); // 用户选择继续

        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('已跳过: 顺丰 (SF) - 已存在', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 14，更新: 0，跳过: 1', $commandTester->getDisplay());
    }

    public function testOptionForce(): void
    {
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 1);
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 15);

        for ($i = 0; $i < 15; ++$i) {
            // @phpstan-ignore method.notFound
            $this->repository->setExpectation('findByCode', null);
        }

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('countActiveCompanies', 15);

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testOptionInactive(): void
    {
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 0);
        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('count', 15);

        for ($i = 0; $i < 15; ++$i) {
            // @phpstan-ignore method.notFound
            $this->repository->setExpectation('findByCode', null);
        }

        // @phpstan-ignore method.notFound
        $this->repository->setExpectation('countActiveCompanies', 0);

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--inactive' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
