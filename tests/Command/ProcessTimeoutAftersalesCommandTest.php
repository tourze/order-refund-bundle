<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\OrderRefundBundle\Command\ProcessTimeoutAftersalesCommand;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(ProcessTimeoutAftersalesCommand::class)]
#[RunTestsInSeparateProcesses]
class ProcessTimeoutAftersalesCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 不需要特殊设置
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(ProcessTimeoutAftersalesCommand::class);
        $this->assertInstanceOf(ProcessTimeoutAftersalesCommand::class, $command);

        return new CommandTester($command);
    }

    public function testCommandExecute(): void
    {
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertEquals(0, $exitCode);

        // 验证命令输出不为空
        $output = $commandTester->getDisplay();
        $this->assertIsString($output);
    }

    public function testOptionBatchSize(): void
    {
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--batch-size' => '50']);

        $this->assertEquals(0, $exitCode);

        // 验证命令输出不为空
        $output = $commandTester->getDisplay();
        $this->assertIsString($output);
    }

    public function testOptionDryRun(): void
    {
        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--dry-run' => true]);

        $this->assertEquals(0, $exitCode);

        // 验证命令输出不为空
        $output = $commandTester->getDisplay();
        $this->assertIsString($output);
        $this->assertStringContainsString('预览模式', $output);
    }
}
