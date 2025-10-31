<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\OrderRefundBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    public function testInvokeMethod(): void
    {
        $menu = $this->createMock(ItemInterface::class);
        $subMenu = $this->createMock(ItemInterface::class);
        $menuItem = $this->createMock(ItemInterface::class);

        // Mock基本行为
        $menu->method('getChild')->with('订单退款管理')->willReturnOnConsecutiveCalls(null, $subMenu);
        $menu->method('addChild')->with('订单退款管理')->willReturn($subMenu);

        $subMenu->method('getChild')->willReturnCallback(function (string $childName) use ($menuItem): ?ItemInterface {
            // 对于 getChild 调用，首次返回null（表示不存在），然后返回新创建的菜单项
            /** @var array<string, int> $callCount */
            static $callCount = [];
            if (!isset($callCount[$childName])) {
                $callCount[$childName] = 0;
            }
            ++$callCount[$childName];

            // 首次调用返回null，后续返回menuItem
            return 1 === $callCount[$childName] ? null : $menuItem;
        });

        $subMenu->method('addChild')->willReturn($menuItem);

        $menuItem->method('setUri')->willReturnSelf();
        $menuItem->method('setAttribute')->willReturnSelf();
        $menuItem->method('addChild')->willReturn($menuItem);

        // 验证关键调用
        $menu->expects($this->atLeastOnce())->method('getChild');
        $menu->expects($this->once())->method('addChild');
        $subMenu->expects($this->atLeastOnce())->method('addChild');

        // 执行测试
        $this->adminMenu->__invoke($menu);
    }

    public function testServiceImplementsMenuProviderInterface(): void
    {
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }
}
