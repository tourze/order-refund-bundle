<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Service\DataValidationService;

/**
 * @internal
 */
#[CoversClass(DataValidationService::class)]
class DataValidationServiceTest extends TestCase
{
    private DataValidationService $service;

    protected function setUp(): void
    {
        $this->service = new DataValidationService();
    }

    public function testValidateOrderData(): void
    {
        $orderData = new OrderDataDTO('', 'paid', new \DateTime(), 'user', 100);
        $errors = $this->service->validateOrderData($orderData);

        $this->assertNotEmpty($errors);
        $this->assertContains('订单编号不能为空', $errors);
    }

    public function testValidateProductData(): void
    {
        $productData = new ProductDataDTO(
            'prod',
            'sku',
            'name',
            'sku',
            100,
            90,
            95,
            10,
            0
        );
        $errors = $this->service->validateProductData($productData);

        $this->assertContains('商品数量必须大于0', $errors);
    }

    public function testValidateRefundRequest(): void
    {
        $orderProducts = [
            new ProductDataDTO('prod_001', 'sku_001', 'Product 1', 'SKU 1', 100, 90, 95, 10, 2),
        ];

        $refundItems = [
            'prod_001' => 3,  // 超过订单数量
        ];

        $errors = $this->service->validateRefundRequest($refundItems, $orderProducts);

        $this->assertStringContainsString('不能超过订单数量', implode(', ', $errors));
    }

    public function testValidateAftersalesData(): void
    {
        $orderData = new OrderDataDTO(
            orderNumber: 'TEST-ORDER-001',
            orderStatus: 'paid',
            orderCreateTime: new \DateTime(),
            userId: 'user_001',
            totalAmount: 100.0
        );

        $productData = new ProductDataDTO(
            productId: 'PROD-001',
            skuId: 'SKU-001',
            productName: 'Test Product',
            skuName: 'Test SKU',
            originalPrice: 60.0,
            paidPrice: 50.0,
            unitPrice: 55.0,
            discountAmount: 10.0,
            orderQuantity: 2
        );

        $errors = $this->service->validateAftersalesData(
            $orderData,
            $productData,
            'ORDER-PRODUCT-001', // orderProductId
            1, // quantity
            AftersalesType::REFUND_ONLY,
            RefundReason::QUALITY_ISSUE
        );

        $this->assertIsArray($errors);
    }

    public function testValidateProductDataList(): void
    {
        $productDataList = [
            new ProductDataDTO(
                productId: 'PROD-001',
                skuId: 'SKU-001',
                productName: 'Test Product',
                skuName: 'Test SKU',
                originalPrice: 60.0,
                paidPrice: 50.0,
                unitPrice: 55.0,
                discountAmount: 10.0,
                orderQuantity: 2
            ),
            new ProductDataDTO(
                productId: '',
                skuId: '',
                productName: 'Invalid Product',
                skuName: 'Invalid SKU',
                originalPrice: 0.0,
                paidPrice: 0.0,
                unitPrice: 0.0,
                discountAmount: 0.0,
                orderQuantity: 0
            ),
        ];

        $errors = $this->service->validateProductDataList($productDataList);

        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    public function testValidateProductDataListEmpty(): void
    {
        $errors = $this->service->validateProductDataList([]);

        $this->assertNotEmpty($errors);
        $this->assertContains('商品数据不能为空', $errors);
    }
}
