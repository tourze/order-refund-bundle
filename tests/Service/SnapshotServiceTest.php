<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;
use Tourze\OrderRefundBundle\Service\SnapshotService;

/**
 * @internal
 */
#[CoversClass(SnapshotService::class)]
class SnapshotServiceTest extends TestCase
{
    private SnapshotService $service;

    protected function setUp(): void
    {
        $this->service = new SnapshotService();
    }

    public function testCreateOrderSnapshot(): void
    {
        $aftersales = new Aftersales();

        $orderData = new OrderDataDTO(
            'ORD001',
            'paid',
            new \DateTime(),
            'user_001',
            100.00
        );

        $snapshot = $this->service->createOrderSnapshot($aftersales, $orderData);

        $this->assertEquals('ORD001', $snapshot->getOrderNumber());
        $this->assertEquals('paid', $snapshot->getOrderStatus());
        $this->assertEquals('100', $snapshot->getTotalAmount());
        $this->assertEquals('user_001', $snapshot->getUserId());
    }

    public function testCreateProductSnapshot(): void
    {
        $aftersales = new Aftersales();

        $productData = new ProductDataDTO(
            'prod_001',
            'sku_001',
            '商品',
            'SKU',
            100.00,
            90.00,
            95.00,
            10.00,
            2
        );

        $this->service->createProductSnapshot($aftersales, $productData, 1);

        // 验证 Aftersales 实体的字段已被正确设置
        $this->assertEquals('prod_001', $aftersales->getProductId());
        $this->assertEquals('sku_001', $aftersales->getSkuId());
        $this->assertEquals('商品', $aftersales->getProductName());
        $this->assertEquals('SKU', $aftersales->getSkuName());
        $this->assertEquals('100', $aftersales->getOriginalPrice());
        $this->assertEquals('90', $aftersales->getPaidPrice());
        $this->assertEquals(1, $aftersales->getQuantity());
        $this->assertEquals('90', $aftersales->getRefundAmount());
        $this->assertEquals('90', $aftersales->getOriginalRefundAmount());
        $this->assertEquals('90', $aftersales->getActualRefundAmount());

        // 验证商品快照数据
        $productSnapshot = $aftersales->getProductSnapshot();
        $this->assertNotNull($productSnapshot);
        $this->assertEquals('prod_001', $productSnapshot['productId']);
        $this->assertEquals(1, $productSnapshot['refundQuantity']);
        $this->assertEquals(90.0, $productSnapshot['refundAmount']);
    }

    public function testNoContractUsage(): void
    {
        $reflection = new \ReflectionClass(SnapshotService::class);
        $filename = $reflection->getFileName();
        $this->assertIsString($filename);
        $source = file_get_contents($filename);
        $this->assertIsString($source);

        $this->assertStringNotContainsString(
            'use Tourze\OrderCoreBundle\Entity\Contract',
            $source
        );
        $this->assertStringNotContainsString(
            'Contract $',
            $source
        );
    }

    public function testCreateProductSnapshots(): void
    {
        $aftersales = new Aftersales();

        $productDataList = [
            new ProductDataDTO(
                productId: 'prod_001',
                skuId: 'sku_001',
                productName: 'Product 1',
                skuName: 'SKU 1',
                originalPrice: 100.0,
                paidPrice: 90.0,
                unitPrice: 95.0,
                discountAmount: 10.0,
                orderQuantity: 3
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
                orderQuantity: 2
            ),
        ];

        $refundQuantities = [
            'prod_001' => 2,
            'prod_002' => 1,
        ];

        $results = $this->service->createProductSnapshots($aftersales, $productDataList, $refundQuantities);

        // 现在方法只处理第一个有效商品（因为Aftersales实体设计为单商品）
        $this->assertCount(1, $results);

        // 验证返回的结果信息
        $result = $results[0];
        $this->assertEquals('prod_001', $result['productId']);
        $this->assertEquals(2, $result['refundQuantity']);
        $this->assertEquals('180', $result['refundAmount']); // 90.0 * 2
        $this->assertTrue($result['processed']);

        // 验证 Aftersales 实体已被设置
        $this->assertEquals('prod_001', $aftersales->getProductId());
        $this->assertEquals(2, $aftersales->getQuantity());
        $this->assertEquals('180', $aftersales->getRefundAmount());
    }

    public function testCreateProductSnapshotsWithZeroQuantity(): void
    {
        $aftersales = new Aftersales();

        $productDataList = [
            new ProductDataDTO(
                productId: 'prod_001',
                skuId: 'sku_001',
                productName: 'Product 1',
                skuName: 'SKU 1',
                originalPrice: 100.0,
                paidPrice: 90.0,
                unitPrice: 95.0,
                discountAmount: 10.0,
                orderQuantity: 3
            ),
        ];

        $refundQuantities = [
            'prod_001' => 0, // Zero quantity should be skipped
        ];

        $results = $this->service->createProductSnapshots($aftersales, $productDataList, $refundQuantities);

        $this->assertCount(0, $results);
    }

    public function testValidateSnapshot(): void
    {
        $orderSnapshot = new AftersalesOrder();
        $orderSnapshot->setOrderNumber('ORD001');
        $orderSnapshot->setUserId('user_001');

        $aftersales = new Aftersales();
        $aftersales->setProductId('prod_001');
        $aftersales->setQuantity(2);
        $aftersales->setProductSnapshot([
            'productId' => 'prod_001',
            'orderQuantity' => 3,
            'refundQuantity' => 2,
        ]);

        $errors = $this->service->validateSnapshot($orderSnapshot, $aftersales);

        $this->assertEmpty($errors);
    }

    public function testValidateSnapshotWithErrors(): void
    {
        // Test with empty order number
        $orderSnapshot = new AftersalesOrder();
        $orderSnapshot->setOrderNumber('');
        $orderSnapshot->setUserId('');

        $aftersales = new Aftersales();
        $aftersales->setProductId('');
        $aftersales->setQuantity(3); // Exceeds order quantity
        $aftersales->setProductSnapshot([
            'productId' => '',
            'orderQuantity' => 2,
            'refundQuantity' => 3,
        ]);

        $errors = $this->service->validateSnapshot($orderSnapshot, $aftersales);

        $this->assertNotEmpty($errors);
        $this->assertContains('订单编号不能为空', $errors);
        $this->assertContains('用户ID不能为空', $errors);
        $this->assertContains('商品ID不能为空', $errors);
        $this->assertStringContainsString('退款数量(3)不能超过订单数量(2)', implode(', ', $errors));
    }

    public function testValidateSnapshotWithEmptyProducts(): void
    {
        $orderSnapshot = new AftersalesOrder();
        $orderSnapshot->setOrderNumber('ORD001');
        $orderSnapshot->setUserId('user_001');

        // Test with aftersales that has no product data
        $aftersales = new Aftersales();
        $aftersales->setProductId(''); // Empty product ID should trigger error

        $errors = $this->service->validateSnapshot($orderSnapshot, $aftersales);

        $this->assertNotEmpty($errors);
        $this->assertContains('商品ID不能为空', $errors);
    }
}
