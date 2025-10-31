<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\OrderRefundBundle\Command\InitReturnAddressesCommand;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\OrderRefundBundle\Service\ReturnAddressService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(InitReturnAddressesCommand::class)]
#[RunTestsInSeparateProcesses]
class InitReturnAddressesCommandTest extends AbstractCommandTestCase
{
    private MockObject&ReturnAddressService $service;

    protected function onSetUp(): void
    {
        $this->service = $this->createMock(ReturnAddressService::class);
        self::getContainer()->set(ReturnAddressService::class, $this->service);
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(InitReturnAddressesCommand::class);
        $this->assertInstanceOf(InitReturnAddressesCommand::class, $command);

        return new CommandTester($command);
    }

    public function testExecuteWithNoExistingAddresses(): void
    {
        // 模拟没有现有地址
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->exactly(3)) // 3个默认地址
            ->method('findByName')
            ->willReturn(null)
        ;

        $this->service->expects($this->exactly(3))
            ->method('createReturnAddress')
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(true)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(3)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('已创建: 主要退货仓库 (默认)', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 3，跳过: 0', $commandTester->getDisplay());
    }

    public function testExecuteWithExistingAddressesAndNoForce(): void
    {
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(true)
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['no']); // 用户选择不继续

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('系统中已存在寄回地址记录', $commandTester->getDisplay());
    }

    public function testExecuteWithExistingAddressesAndContinue(): void
    {
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(true)
        ;

        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturn(null) // 模拟没有重复的名称
        ;

        $this->service->expects($this->exactly(3))
            ->method('createReturnAddress')
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(true)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(6)
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['yes']); // 用户选择继续

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('初始化完成！创建: 3，跳过: 0', $commandTester->getDisplay());
    }

    public function testExecuteWithForceOption(): void
    {
        // 当使用force选项时，不应该调用hasAvailableAddress方法
        $this->service->expects($this->never())
            ->method('hasAvailableAddress')
        ;

        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ReturnAddress::class), // 第一个存在
                null, // 第二个不存在
                null  // 第三个不存在
            )
        ;

        $this->service->expects($this->exactly(2)) // 只创建2个新的
            ->method('createReturnAddress')
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(true)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(3)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('地址 "主要退货仓库" 已存在，强制模式下跳过更新', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 2，跳过: 1', $commandTester->getDisplay());
    }

    public function testExecuteWithInactiveOption(): void
    {
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturn(null)
        ;

        $this->service->expects($this->exactly(3))
            ->method('createReturnAddress')
            ->with(
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                false, // isDefault = false (因为inactive=true)
                false, // isActive = false
                static::isInt()
            )
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(0)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--inactive' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('已创建: 主要退货仓库 (未激活)', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 3，跳过: 0', $commandTester->getDisplay());
    }

    public function testExecuteWithNoDefaultOption(): void
    {
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturn(null)
        ;

        $this->service->expects($this->exactly(3))
            ->method('createReturnAddress')
            ->with(
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                false, // isDefault = false (因为no-default=true)
                true,  // isActive = true
                static::isInt()
            )
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(3)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--no-default' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('初始化完成！创建: 3，跳过: 0', $commandTester->getDisplay());
        $this->assertStringContainsString('建议通过管理界面设置一个默认寄回地址', $commandTester->getDisplay());
    }

    public function testExecuteWithCreateException(): void
    {
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturn(null)
        ;

        $this->service->expects($this->exactly(3))
            ->method('createReturnAddress')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new \Exception('创建失败')), // 第一个失败
                $this->createMock(ReturnAddress::class), // 第二个成功
                $this->createMock(ReturnAddress::class)  // 第三个成功
            )
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(2)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('创建地址 "主要退货仓库" 失败: 创建失败', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 2，跳过: 1', $commandTester->getDisplay());
    }

    public function testExecuteWithSkippedExisting(): void
    {
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(true)
        ;

        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ReturnAddress::class), // 第一个存在
                null, // 第二个不存在
                null  // 第三个不存在
            )
        ;

        $this->service->expects($this->exactly(2)) // 只创建2个新的
            ->method('createReturnAddress')
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(true)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(3)
        ;

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['yes']); // 用户选择继续

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('已跳过: 主要退货仓库 - 已存在', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 2，跳过: 1', $commandTester->getDisplay());
    }

    public function testOptionForce(): void
    {
        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturnOnConsecutiveCalls(
                $this->createMock(ReturnAddress::class), // 第一个存在
                null, // 第二个不存在
                null  // 第三个不存在
            )
        ;

        $this->service->expects($this->exactly(2)) // 只创建2个新的
            ->method('createReturnAddress')
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(true)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(3)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('地址 "主要退货仓库" 已存在，强制模式下跳过更新', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 2，跳过: 1', $commandTester->getDisplay());
    }

    public function testOptionInactive(): void
    {
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturn(null)
        ;

        $this->service->expects($this->exactly(3))
            ->method('createReturnAddress')
            ->with(
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                false, // isDefault = false (因为inactive=true)
                false, // isActive = false
                static::isInt()
            )
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(0)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--inactive' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('已创建: 主要退货仓库 (未激活)', $commandTester->getDisplay());
        $this->assertStringContainsString('初始化完成！创建: 3，跳过: 0', $commandTester->getDisplay());
    }

    public function testOptionNoDefault(): void
    {
        $this->service->expects($this->once())
            ->method('hasAvailableAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->exactly(3))
            ->method('findByName')
            ->willReturn(null)
        ;

        $this->service->expects($this->exactly(3))
            ->method('createReturnAddress')
            ->with(
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                static::isString(),
                false, // isDefault = false (因为no-default=true)
                true,  // isActive = true
                static::isInt()
            )
            ->willReturn($this->createMock(ReturnAddress::class))
        ;

        $this->service->expects($this->once())
            ->method('hasDefaultAddress')
            ->willReturn(false)
        ;

        $this->service->expects($this->once())
            ->method('countActiveAddresses')
            ->willReturn(3)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute(['--no-default' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('初始化完成！创建: 3，跳过: 0', $commandTester->getDisplay());
        $this->assertStringContainsString('建议通过管理界面设置一个默认寄回地址', $commandTester->getDisplay());
    }
}
