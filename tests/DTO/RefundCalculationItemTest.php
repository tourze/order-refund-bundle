<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\DTO\RefundCalculationItem;

/**
 * @internal
 */
#[CoversClass(RefundCalculationItem::class)]
final class RefundCalculationItemTest extends TestCase
{
    public function testRefundCalculationItemCanBeInstantiated(): void
    {
        $item = new RefundCalculationItem(
            orderProductId: '123',
            productName: 'iPhone 15 Pro',
            skuName: 'A2848',
            orderQuantity: 2,
            refundedQuantity: 0,
            maxRefundableQuantity: 2,
            requestQuantity: 1,
            unitPrice: '7999.00',
            refundableAmount: '7999.00',
            totalRefundedAmount: '0.00',
            mainThumb: '/uploads/iphone.jpg',
            canRefund: true,
            errors: []
        );

        $this->assertInstanceOf(RefundCalculationItem::class, $item);
        $this->assertSame('123', $item->orderProductId);
        $this->assertSame('iPhone 15 Pro', $item->productName);
        $this->assertSame('A2848', $item->skuName);
        $this->assertSame(2, $item->orderQuantity);
        $this->assertSame(0, $item->refundedQuantity);
        $this->assertSame(2, $item->maxRefundableQuantity);
        $this->assertSame(1, $item->requestQuantity);
        $this->assertSame('7999.00', $item->unitPrice);
        $this->assertSame('7999.00', $item->refundableAmount);
        $this->assertSame('0.00', $item->totalRefundedAmount);
        $this->assertTrue($item->canRefund);
        $this->assertSame([], $item->errors);
    }

    public function testRefundCalculationItemWithErrors(): void
    {
        $errors = ['申请数量超过可退数量', '商品状态无效'];

        $item = new RefundCalculationItem(
            orderProductId: '456',
            productName: 'iPad Pro',
            skuName: 'A2759',
            orderQuantity: 1,
            refundedQuantity: 1,
            maxRefundableQuantity: 0,
            requestQuantity: 1,
            unitPrice: '8799.00',
            refundableAmount: '0.00',
            totalRefundedAmount: '8799.00',
            mainThumb: '/uploads/ipad.jpg',
            canRefund: false,
            errors: $errors
        );

        $this->assertFalse($item->canRefund);
        $this->assertSame($errors, $item->errors);
        $this->assertSame(0, $item->maxRefundableQuantity);
        $this->assertSame('0.00', $item->refundableAmount);
    }

    public function testToArray(): void
    {
        $item = new RefundCalculationItem(
            orderProductId: '789',
            productName: 'MacBook Pro',
            skuName: 'A2485',
            orderQuantity: 1,
            refundedQuantity: 0,
            maxRefundableQuantity: 1,
            requestQuantity: 1,
            unitPrice: '12999.00',
            refundableAmount: '12999.00',
            totalRefundedAmount: '0.00',
            mainThumb: '/uploads/macbook.jpg',
            canRefund: true,
            errors: []
        );

        $array = $item->toArray();

        $expected = [
            'orderProductId' => '789',
            'productName' => 'MacBook Pro',
            'skuName' => 'A2485',
            'mainThumb' => '/uploads/macbook.jpg',
            'orderQuantity' => 1,
            'refundedQuantity' => 0,
            'maxRefundableQuantity' => 1,
            'requestQuantity' => 1,
            'unitPrice' => '12999.00',
            'refundableAmount' => '12999.00',
            'totalRefundedAmount' => '0.00',
            'canRefund' => true,
            'errors' => [],
        ];

        $this->assertSame($expected, $array);
    }

    public function testPartialRefundScenario(): void
    {
        $item = new RefundCalculationItem(
            orderProductId: '101',
            productName: 'AirPods Pro',
            skuName: 'A2084',
            orderQuantity: 3,
            refundedQuantity: 1,
            maxRefundableQuantity: 2,
            requestQuantity: 2,
            unitPrice: '1999.00',
            refundableAmount: '3998.00',
            totalRefundedAmount: '1999.00',
            mainThumb: '/uploads/airpods.jpg',
            canRefund: true,
            errors: []
        );

        $this->assertSame(3, $item->orderQuantity);
        $this->assertSame(1, $item->refundedQuantity);
        $this->assertSame(2, $item->maxRefundableQuantity);
        $this->assertSame(2, $item->requestQuantity);
        $this->assertSame('3998.00', $item->refundableAmount);
        $this->assertSame('1999.00', $item->totalRefundedAmount);
        $this->assertTrue($item->canRefund);
    }
}
