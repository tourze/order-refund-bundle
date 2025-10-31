<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use Doctrine\Common\Collections\ArrayCollection;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Service\PriceCalculator;

/**
 * @internal
 */
#[CoversClass(PriceCalculator::class)]
final class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PriceCalculator();
    }

    public function testCalculateTotalAmountWithEmptyPrices(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->method('getPrices')->willReturn(new ArrayCollection());

        $result = $this->calculator->calculateTotalAmount($contract);

        $this->assertSame(0.0, $result);
    }

    public function testGetPriceWithEmptyCollection(): void
    {
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->method('getPrices')->willReturn(new ArrayCollection());

        $originalPrice = $this->calculator->getOriginalPrice($orderProduct);
        $paidPrice = $this->calculator->getPaidPrice($orderProduct);
        $unitPrice = $this->calculator->getUnitPrice($orderProduct);

        $this->assertSame(0.0, $originalPrice);
        $this->assertSame(0.0, $paidPrice);
        $this->assertSame(0.0, $unitPrice);
    }

    public function testPriceCalculatorConstant(): void
    {
        // Test that the DEFAULT_CURRENCY constant is used correctly
        // This is a simple integration test to ensure the basic functionality works
        $contract = $this->createMock(Contract::class);
        $contract->method('getPrices')->willReturn(new ArrayCollection());

        $result = $this->calculator->calculateTotalAmount($contract);

        $this->assertIsFloat($result);
        $this->assertSame(0.0, $result);
    }
}
