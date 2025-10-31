<?php

namespace Tourze\OrderRefundBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\OrderRefundBundle\DependencyInjection\OrderRefundExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(OrderRefundExtension::class)]
final class OrderRefundExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ContainerBuilder();
        // 添加必要的参数
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testExtensionExtendsSymfonyExtension(): void
    {
        $extension = new OrderRefundExtension();
        $this->assertInstanceOf(Extension::class, $extension);
    }

    public function testExtensionInstantiation(): void
    {
        $extension = new OrderRefundExtension();
        $this->assertInstanceOf(OrderRefundExtension::class, $extension);
    }

    public function testLoadMethod(): void
    {
        $configs = [];
        $extension = new OrderRefundExtension();
        $extension->load($configs, $this->container);

        // 验证扩展能够正常加载而不抛出异常
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testLoadWithConfiguration(): void
    {
        $configs = [
            'order_refund' => [],
        ];
        $extension = new OrderRefundExtension();
        $extension->load($configs, $this->container);

        // 验证扩展能够使用配置正常加载而不抛出异常
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testPrepend(): void
    {
        $extension = new OrderRefundExtension();

        // 执行 prepend 方法
        $extension->prepend($this->container);

        // 验证模板目录参数被正确设置
        $this->assertTrue($this->container->hasParameter('tourze_order_refund.templates_dir'));

        $templatesDir = $this->container->getParameter('tourze_order_refund.templates_dir');
        $expectedPath = __DIR__ . '/../../src/Resources/views/pages';

        // 确保 $templatesDir 是字符串后再使用 realpath
        $this->assertIsString($templatesDir);

        // 使用 realpath 进行比较，避免路径形式不同的问题
        $this->assertSame(realpath($expectedPath), realpath($templatesDir));
    }
}
