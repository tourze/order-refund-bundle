<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\DTO\RefundCalculationItem;
use Tourze\OrderRefundBundle\DTO\RefundCalculationResult;

/**
 * @internal
 */
#[CoversClass(RefundCalculationResult::class)]
final class RefundCalculationResultTest extends TestCase
{
    public function testRefundCalculationResultCanBeInstantiated(): void
    {
        $result = new RefundCalculationResult(
            contractId: '12345',
            orderNumber: 'ORD20240101001',
            totalRefundableAmount: '0.00',
            canRefund: false,
            items: [],
            validationErrors: [],
            refundRules: []
        );

        $this->assertInstanceOf(RefundCalculationResult::class, $result);
        $this->assertSame('12345', $result->contractId);
        $this->assertSame('ORD20240101001', $result->orderNumber);
        $this->assertSame([], $result->items);
        $this->assertSame('0.00', $result->totalRefundableAmount);
        $this->assertFalse($result->canRefund);
        $this->assertSame([], $result->validationErrors);
        $this->assertSame([], $result->refundRules);
    }

    public function testRefundCalculationResultWithItems(): void
    {
        $item1 = new RefundCalculationItem(
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
            canRefund: true
        );

        $item2 = new RefundCalculationItem(
            orderProductId: '456',
            productName: 'iPad Pro',
            skuName: 'A2759',
            orderQuantity: 1,
            refundedQuantity: 0,
            maxRefundableQuantity: 1,
            requestQuantity: 1,
            unitPrice: '8799.00',
            refundableAmount: '8799.00',
            totalRefundedAmount: '0.00',
            mainThumb: '/uploads/ipad.jpg',
            canRefund: true
        );

        $refundRules = ['订单完成后30天内可申请退款', '商品必须处于有效状态'];

        $result = new RefundCalculationResult(
            contractId: '12345',
            orderNumber: 'ORD20240101001',
            totalRefundableAmount: '16798.00',
            canRefund: true,
            items: [$item1, $item2],
            validationErrors: [],
            refundRules: $refundRules
        );

        $this->assertCount(2, $result->items);
        $this->assertSame($item1, $result->items[0]);
        $this->assertSame($item2, $result->items[1]);
        $this->assertSame('16798.00', $result->totalRefundableAmount);
        $this->assertTrue($result->canRefund);
        $this->assertSame($refundRules, $result->refundRules);
    }

    public function testRefundCalculationResultWithErrors(): void
    {
        $validationErrors = ['商品不存在: 999', '申请数量超过可退数量'];

        $result = new RefundCalculationResult(
            contractId: '12345',
            orderNumber: 'ORD20240101001',
            totalRefundableAmount: '0.00',
            canRefund: false,
            items: [],
            validationErrors: $validationErrors,
            refundRules: []
        );

        $this->assertFalse($result->canRefund);
        $this->assertSame($validationErrors, $result->validationErrors);
        $this->assertSame('0.00', $result->totalRefundableAmount);
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
            canRefund: true
        );

        $result = new RefundCalculationResult(
            contractId: '12345',
            orderNumber: 'ORD20240101001',
            totalRefundableAmount: '12999.00',
            canRefund: true,
            items: [$item],
            validationErrors: [],
            refundRules: ['订单完成后30天内可申请退款']
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('contractId', $array);
        $this->assertArrayHasKey('orderNumber', $array);
        $this->assertArrayHasKey('items', $array);
        $this->assertArrayHasKey('totalRefundableAmount', $array);
        $this->assertArrayHasKey('canRefund', $array);
        $this->assertArrayHasKey('validationErrors', $array);
        $this->assertArrayHasKey('refundRules', $array);

        $this->assertSame('12345', $array['contractId']);
        $this->assertSame('ORD20240101001', $array['orderNumber']);
        $this->assertIsArray($array['items']);
        $this->assertCount(1, $array['items']);
        $this->assertSame('12999.00', $array['totalRefundableAmount']);
        $this->assertTrue($array['canRefund']);
        $this->assertSame([], $array['validationErrors']);
        $this->assertSame(['订单完成后30天内可申请退款'], $array['refundRules']);

        // 检验items数组格式
        $this->assertIsArray($array['items']);
        $this->assertArrayHasKey(0, $array['items']);
        $firstItem = $array['items'][0];
        $this->assertIsArray($firstItem);
        $this->assertArrayHasKey('orderProductId', $firstItem);
        $this->assertSame('789', $firstItem['orderProductId']);
    }

    public function testEmptyResult(): void
    {
        $result = new RefundCalculationResult(
            contractId: '',
            orderNumber: '',
            totalRefundableAmount: '0.00',
            canRefund: false
        );

        $array = $result->toArray();

        $this->assertSame('', $array['contractId']);
        $this->assertSame('', $array['orderNumber']);
        $this->assertSame([], $array['items']);
        $this->assertSame('0.00', $array['totalRefundableAmount']);
        $this->assertFalse($array['canRefund']);
        $this->assertSame([], $array['validationErrors']);
        $this->assertSame([], $array['refundRules']);
    }
}
