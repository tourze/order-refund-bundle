<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\OrderRefundBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(OrderRefundBundle::class)]
#[RunTestsInSeparateProcesses]
final class OrderRefundBundleTest extends AbstractBundleTestCase
{
}
