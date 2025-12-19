<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class OrderRefundExtension extends AutoExtension implements PrependExtensionInterface
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Configure templates directory parameter
        $container->setParameter('tourze_order_refund.templates_dir', __DIR__ . '/../Resources/views/pages');
    }
}
