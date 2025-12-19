<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Service\ProductImageExtractor;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(ProductImageExtractor::class)]
final class ProductImageExtractorTest extends TestCase
{
    private ProductImageExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ProductImageExtractor();
    }

    public function testGetProductImagesWithNull(): void
    {
        $result = $this->extractor->getProductImages(null);

        $this->assertNull($result);
    }

    public function testGetProductImagesWithSpu(): void
    {
        $images = [
            ['url' => 'image1.jpg', 'sort' => 1],
            ['url' => 'image2.jpg', 'sort' => 2],
        ];
        $spu = new Spu();
        $spu->setImages($images);

        $result = $this->extractor->getProductImages($spu);

        $this->assertSame($images, $result);
    }

    public function testGetProductMainImageWithNull(): void
    {
        $result = $this->extractor->getProductMainImage(null);

        $this->assertNull($result);
    }

    public function testGetProductMainImageWithImages(): void
    {
        $images = [
            ['url' => 'main.jpg', 'sort' => 1],
            ['url' => 'secondary.jpg', 'sort' => 2],
        ];
        $spu = new Spu();
        $spu->setImages($images);

        $result = $this->extractor->getProductMainImage($spu);

        $this->assertSame('main.jpg', $result);
    }

    public function testGetProductMainImageWithEmptyImages(): void
    {
        $spu = new Spu();
        $spu->setImages([]);

        $result = $this->extractor->getProductMainImage($spu);

        $this->assertNull($result);
    }

    public function testGetProductMainImageWithoutUrl(): void
    {
        $images = [
            ['sort' => 1], // No url key
        ];
        $spu = new Spu();
        $spu->setImages($images);

        $result = $this->extractor->getProductMainImage($spu);

        $this->assertNull($result);
    }

    public function testGetSkuImagesWithNull(): void
    {
        $result = $this->extractor->getSkuImages(null);

        $this->assertNull($result);
    }

    public function testGetSkuImagesWithThumbs(): void
    {
        $thumbs = [
            ['url' => 'thumb1.jpg'],
            ['url' => 'thumb2.jpg'],
        ];
        $sku = new Sku();
        $sku->setThumbs($thumbs);

        $result = $this->extractor->getSkuImages($sku);

        $this->assertSame($thumbs, $result);
    }

    public function testGetSkuMainImageWithNull(): void
    {
        $result = $this->extractor->getSkuMainImage(null);

        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithNullThumbs(): void
    {
        $sku = new Sku();
        $sku->setThumbs(null);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithEmptyThumbs(): void
    {
        $sku = new Sku();
        $sku->setThumbs([]);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithArrayThumbs(): void
    {
        $thumbs = [
            ['url' => 'main-thumb.jpg'],
            ['url' => 'second-thumb.jpg'],
        ];
        $sku = new Sku();
        $sku->setThumbs($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertSame('main-thumb.jpg', $result);
    }

    public function testGetSkuMainImageWithStringThumbs(): void
    {
        $thumbs = [
            'string-thumb.jpg',
            'second-string-thumb.jpg',
        ];
        $sku = new Sku();
        $sku->setThumbs($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertSame('string-thumb.jpg', $result);
    }

    public function testGetSkuMainImageWithInvalidFirstType(): void
    {
        $thumbs = [
            123, // Invalid type - this is checked first
            ['url' => 'valid.jpg'], // This won't be reached
        ];
        $sku = new Sku();
        $sku->setThumbs($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        // Should return null because first element is invalid type
        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithArrayNoUrl(): void
    {
        $thumbs = [
            ['name' => 'no-url.jpg'], // No url key
        ];
        $sku = new Sku();
        $sku->setThumbs($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithNonStringUrl(): void
    {
        $thumbs = [
            ['url' => 123], // Non-string url
        ];
        $sku = new Sku();
        $sku->setThumbs($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertNull($result);
    }

    public function testGetSkuSpecsWithNull(): void
    {
        $result = $this->extractor->getSkuSpecs(null);

        $this->assertNull($result);
    }

    public function testGetSkuSpecsWithCompleteData(): void
    {
        $sku = new Sku();
        $sku->setGtin('ABC123');
        $sku->setTitle('SKU标题');
        $sku->setUnit('件');

        $result = $this->extractor->getSkuSpecs($sku);

        $expected = [
            'gtin' => 'ABC123',
            'title' => 'SKU标题',
            'unit' => '件',
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetSkuSpecsWithPartialData(): void
    {
        $sku = new Sku();
        $sku->setGtin('DEF456');
        $sku->setUnit('套');

        $result = $this->extractor->getSkuSpecs($sku);

        $expected = [
            'gtin' => 'DEF456',
            'unit' => '套',
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetSkuSpecsWithOnlyTitle(): void
    {
        $sku = new Sku();
        $sku->setGtin(null);
        $sku->setTitle('仅标题SKU');

        $result = $this->extractor->getSkuSpecs($sku);

        $expected = [
            'title' => '仅标题SKU',
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetSkuSpecsWithNoData(): void
    {
        $sku = new Sku();
        $sku->setGtin(null);
        $sku->setTitle(null);

        $result = $this->extractor->getSkuSpecs($sku);

        $this->assertNull($result);
    }

    public function testGetSkuSpecsWithEmptyStrings(): void
    {
        $sku = new Sku();
        $sku->setGtin('');
        $sku->setTitle('');
        $sku->setUnit('');

        $result = $this->extractor->getSkuSpecs($sku);

        $expected = [
            'gtin' => '',
            'title' => '',
            'unit' => '',
        ];
        $this->assertSame($expected, $result);
    }
}
