<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Service\OmsProductDataMapper;

/**
 * @internal
 */
#[CoversClass(OmsProductDataMapper::class)]
final class OmsProductDataMapperTest extends TestCase
{
    private OmsProductDataMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new OmsProductDataMapper();
    }

    public function testSetAftersalesProductDataWithEmptyArray(): void
    {
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->expects($this->never())->method('setOrderProductId');

        $this->mapper->setAftersalesProductData($aftersales, []);
    }

    public function testSetAftersalesProductDataWithCompleteProduct(): void
    {
        $products = [
            [
                'orderProductId' => 'OP-12345',
                'productId' => 'PROD-001',
                'productName' => 'Test Product',
                'skuId' => 'SKU-001',
                'skuName' => 'Test SKU',
                'quantity' => 2,
                'originalPrice' => '99.99',
                'paidPrice' => '89.99',
                'refundAmount' => '89.99',
            ],
        ];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        $this->assertSame('OP-12345', $aftersales->getOrderProductId());
        $this->assertSame('PROD-001', $aftersales->getProductId());
        $this->assertSame('Test Product', $aftersales->getProductName());
        $this->assertSame('SKU-001', $aftersales->getSkuId());
        $this->assertSame('Test SKU', $aftersales->getSkuName());
        $this->assertSame(2, $aftersales->getQuantity());
        $this->assertSame('99.99', $aftersales->getOriginalPrice());
        $this->assertSame('89.99', $aftersales->getPaidPrice());
        $this->assertSame('89.99', $aftersales->getRefundAmount());
        $this->assertSame('89.99', $aftersales->getOriginalRefundAmount());
        $this->assertSame('89.99', $aftersales->getActualRefundAmount());
        $this->assertSame(['products' => $products], $aftersales->getProductSnapshot());
    }

    public function testSetAftersalesProductDataWithMinimalProduct(): void
    {
        $products = [
            ['productName' => 'Test'], // Not truly empty, as empty array causes early return
        ];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        $this->assertSame('unknown-order-product', $aftersales->getOrderProductId());
        $this->assertSame('unknown-product', $aftersales->getProductId());
        $this->assertSame('Test', $aftersales->getProductName());
        $this->assertSame('unknown-product-sku', $aftersales->getSkuId());
        $this->assertSame('Test (Default SKU)', $aftersales->getSkuName());
        $this->assertSame(1, $aftersales->getQuantity());
        $this->assertSame('0.00', $aftersales->getOriginalPrice());
        $this->assertSame('0.00', $aftersales->getPaidPrice());
        $this->assertSame('0.00', $aftersales->getRefundAmount());
    }

    public function testSetAftersalesProductDataWithProductCodeFallback(): void
    {
        $products = [
            [
                'productCode' => 'CODE-123',
                'productName' => 'Product by Code',
            ],
        ];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        $this->assertSame('CODE-123', $aftersales->getOrderProductId());
        $this->assertSame('CODE-123', $aftersales->getProductId());
        $this->assertSame('CODE-123-sku', $aftersales->getSkuId());
    }

    public function testSetAftersalesProductDataWithSkuFallback(): void
    {
        $products = [
            [
                'productId' => 'PROD-999',
                'productName' => 'Product with SKU Fallback',
            ],
        ];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        $this->assertSame('PROD-999-sku', $aftersales->getSkuId());
        $this->assertSame('Product with SKU Fallback (Default SKU)', $aftersales->getSkuName());
    }

    /**
     * @param array<string, mixed> $product
     */
    #[DataProvider('quantityDataProvider')]
    public function testSetAftersalesProductDataWithVariousQuantities(array $product, int $expected): void
    {
        $products = [$product];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        $this->assertSame($expected, $aftersales->getQuantity());
    }

    /**
     * @return iterable<string, array{product: array<string, mixed>, expected: int}>
     */
    public static function quantityDataProvider(): iterable
    {
        yield 'valid quantity' => [
            'product' => ['quantity' => 5],
            'expected' => 5,
        ];

        yield 'zero quantity' => [
            'product' => ['quantity' => 0],
            'expected' => 0,
        ];

        yield 'missing quantity' => [
            'product' => ['productName' => 'Test'], // Need at least one field to avoid early return
            'expected' => 1,
        ];

        yield 'string quantity' => [
            'product' => ['quantity' => '10', 'productName' => 'Test'],
            'expected' => 1,
        ];

        yield 'float quantity' => [
            'product' => ['quantity' => 3.5, 'productName' => 'Test'],
            'expected' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $product
     */
    #[DataProvider('refundAmountDataProvider')]
    public function testCalculateRefundAmountWithVariousFormats(array $product, string $expected): void
    {
        $products = [$product];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        $this->assertSame($expected, $aftersales->getRefundAmount());
    }

    /**
     * @return iterable<string, array{product: array<string, mixed>, expected: string}>
     */
    public static function refundAmountDataProvider(): iterable
    {
        yield 'explicit refund amount' => [
            'product' => ['refundAmount' => '123.45'],
            'expected' => '123.45',
        ];

        yield 'amount in cents' => [
            'product' => ['amount' => 12345],
            'expected' => '123.45',
        ];

        yield 'amount as string' => [
            'product' => ['amount' => '9999'],
            'expected' => '99.99',
        ];

        yield 'amount as float' => [
            'product' => ['amount' => 5050.5],
            'expected' => '50.51',
        ];

        yield 'no amount fields' => [
            'product' => ['productName' => 'Test'], // Need at least one field to avoid early return
            'expected' => '0.00',
        ];

        yield 'non-numeric amount' => [
            'product' => ['amount' => 'invalid', 'productName' => 'Test'],
            'expected' => '0.00',
        ];

        yield 'refund amount takes precedence' => [
            'product' => ['refundAmount' => '50.00', 'amount' => 10000],
            'expected' => '50.00',
        ];
    }

    public function testSetAftersalesProductDataWithNonStringValues(): void
    {
        $products = [
            [
                'orderProductId' => 12345,
                'productId' => true,
                'productName' => ['not', 'a', 'string'],
                'skuId' => null,
                'skuName' => 3.14,
                'originalPrice' => 100,
                'paidPrice' => false,
            ],
        ];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        // All non-string values should fall back to defaults
        $this->assertSame('unknown-order-product', $aftersales->getOrderProductId());
        $this->assertSame('unknown-product', $aftersales->getProductId());
        $this->assertSame('Unknown Product', $aftersales->getProductName());
        $this->assertSame('unknown-product-sku', $aftersales->getSkuId());
        $this->assertSame('Unknown Product (Default SKU)', $aftersales->getSkuName());
        $this->assertSame('0.00', $aftersales->getOriginalPrice());
        $this->assertSame('0.00', $aftersales->getPaidPrice());
    }

    public function testSetAftersalesProductDataWithMultipleProducts(): void
    {
        $products = [
            [
                'orderProductId' => 'OP-FIRST',
                'productName' => 'First Product',
                'quantity' => 1,
            ],
            [
                'orderProductId' => 'OP-SECOND',
                'productName' => 'Second Product',
                'quantity' => 2,
            ],
        ];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        // Should only use the first product for basic info
        $this->assertSame('OP-FIRST', $aftersales->getOrderProductId());
        $this->assertSame('First Product', $aftersales->getProductName());
        $this->assertSame(1, $aftersales->getQuantity());

        // But snapshot should contain all products
        $snapshot = $aftersales->getProductSnapshot();
        $this->assertIsArray($snapshot);
        $this->assertArrayHasKey('products', $snapshot);
        $this->assertIsArray($snapshot['products']);
        $this->assertCount(2, $snapshot['products']);
    }

    public function testSetAftersalesProductDataWithEmptyStrings(): void
    {
        $products = [
            [
                'orderProductId' => '',
                'productId' => '',
                'productCode' => '',
                'productName' => '',
                'skuId' => '',
                'skuName' => '',
            ],
        ];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        // Empty strings should fall back to defaults
        $this->assertSame('unknown-order-product', $aftersales->getOrderProductId());
        $this->assertSame('unknown-product', $aftersales->getProductId());
        $this->assertSame('', $aftersales->getProductName()); // Empty string is still set
        $this->assertSame('unknown-product-sku', $aftersales->getSkuId());
        // For SKU name, empty productName results in ' (Default SKU)'
        $this->assertSame(' (Default SKU)', $aftersales->getSkuName());
    }

    public function testSetAftersalesProductDataPriceFormatting(): void
    {
        $products = [
            [
                'originalPrice' => '100.5',
                'paidPrice' => '99.999',
                'amount' => 8888,
            ],
        ];

        $aftersales = new Aftersales();
        $this->mapper->setAftersalesProductData($aftersales, $products);

        $this->assertSame('100.5', $aftersales->getOriginalPrice());
        $this->assertSame('99.999', $aftersales->getPaidPrice());
        $this->assertSame('88.88', $aftersales->getRefundAmount());
    }
}
