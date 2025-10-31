<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;

/**
 * @internal
 */
#[CoversClass(ProductDataDTO::class)]
class ProductDataDTOTest extends TestCase
{
    public function testCreateFromArray(): void
    {
        $data = [
            'productId' => 'prod_001',
            'skuId' => 'sku_001',
            'productName' => '测试商品',
            'skuName' => '默认规格',
            'originalPrice' => 100.00,
            'paidPrice' => 90.00,
            'unitPrice' => 95.00,
            'discountAmount' => 10.00,
            'orderQuantity' => 2,
        ];

        $dto = ProductDataDTO::fromArray($data);

        $this->assertEquals('prod_001', $dto->productId);
        $this->assertEquals('sku_001', $dto->skuId);
        $this->assertEquals('测试商品', $dto->productName);
        $this->assertEquals(90.00, $dto->paidPrice);
        $this->assertEquals(2, $dto->orderQuantity);
    }

    public function testValidation(): void
    {
        $dto = new ProductDataDTO('', 'sku', 'name', 'sku', 100, 90, 95, 10, 0);
        $errors = $dto->validate();

        $this->assertContains('商品ID不能为空', $errors);
        $this->assertContains('商品数量必须大于0', $errors);
    }
}
