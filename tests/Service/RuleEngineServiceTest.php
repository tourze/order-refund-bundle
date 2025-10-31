<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;
use Tourze\OrderRefundBundle\Service\RuleEngineService;

/**
 * @internal
 */
#[CoversClass(RuleEngineService::class)]
class RuleEngineServiceTest extends TestCase
{
    private RuleEngineService $service;

    protected function setUp(): void
    {
        $this->service = new RuleEngineService();
        $_ENV['AFTERSALES_MAX_DAYS'] = '30';
    }

    public function testCanCreateAftersales(): void
    {
        $orderData = new OrderDataDTO(
            'ORD001',
            'paid',
            new \DateTime(),
            'user',
            100
        );

        $this->assertTrue($this->service->canCreateAftersales($orderData));

        // 测试超时订单
        $oldOrder = new OrderDataDTO(
            'ORD002',
            'paid',
            new \DateTime('-60 days'),
            'user',
            100
        );

        $this->assertFalse($this->service->canCreateAftersales($oldOrder));
    }

    public function testValidateRefundItems(): void
    {
        $products = [
            new ProductDataDTO('prod_001', 'sku', 'name', 'sku', 100, 90, 95, 10, 2),
        ];

        $refundItems = [
            ['productId' => 'prod_001', 'quantity' => 3], // 超过库存
        ];

        $errors = $this->service->validateRefundItems($products, $refundItems);
        $this->assertContains('退款数量超过订单数量: prod_001', $errors);
    }

    public function testGetAllowedAftersalesTypes(): void
    {
        $paidOrder = new OrderDataDTO('ORD001', 'paid', new \DateTime(), 'user', 100);
        $shippedOrder = new OrderDataDTO('ORD002', 'shipped', new \DateTime(), 'user', 100);
        $receivedOrder = new OrderDataDTO('ORD003', 'received', new \DateTime(), 'user', 100);

        $this->assertEquals(['refund_only'], $this->service->getAllowedAftersalesTypes($paidOrder));
        $this->assertEquals(['return_refund', 'exchange'], $this->service->getAllowedAftersalesTypes($shippedOrder));
        $this->assertEquals(['return_refund', 'exchange', 'refund_only'], $this->service->getAllowedAftersalesTypes($receivedOrder));
    }

    public function testIsReasonValid(): void
    {
        $this->assertTrue($this->service->isReasonValid('seven_days', 'refund_only'));
        $this->assertTrue($this->service->isReasonValid('quality', 'return_refund'));
        $this->assertFalse($this->service->isReasonValid('quality', 'refund_only'));
    }

    public function testApplyCustomRules(): void
    {
        $orderData = new OrderDataDTO('ORD001', 'paid', new \DateTime(), 'user', 100);

        $context = [
            'userLevel' => 'vip',
            'productCategory' => 'digital',
            'previousAftersalesCount' => 2,
        ];

        $results = $this->service->applyCustomRules($orderData, $context);

        $this->assertTrue($results['user_level_check']['auto_approve']);
        $this->assertFalse($results['category_check']['allowed']);
        $this->assertTrue($results['frequency_check']['allowed']);
    }

    public function testCalculateMaxRefundAmount(): void
    {
        $products = [
            new ProductDataDTO(
                productId: 'prod_001',
                skuId: 'sku_001',
                productName: 'Product 1',
                skuName: 'SKU 1',
                originalPrice: 100.0,
                paidPrice: 90.0,
                unitPrice: 95.0,
                discountAmount: 10.0,
                orderQuantity: 2
            ),
            new ProductDataDTO(
                productId: 'prod_002',
                skuId: 'sku_002',
                productName: 'Product 2',
                skuName: 'SKU 2',
                originalPrice: 200.0,
                paidPrice: 180.0,
                unitPrice: 190.0,
                discountAmount: 20.0,
                orderQuantity: 1
            ),
        ];

        $maxAmount = $this->service->calculateMaxRefundAmount($products);

        // (90.0 * 2) + (180.0 * 1) = 180.0 + 180.0 = 360.0
        $this->assertEquals(360.0, $maxAmount);
    }

    public function testCalculateMaxRefundAmountEmpty(): void
    {
        $maxAmount = $this->service->calculateMaxRefundAmount([]);

        $this->assertEquals(0.0, $maxAmount);
    }
}
