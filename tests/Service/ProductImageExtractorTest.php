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
        $spu = $this->createSpuMock($images);

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
        $spu = $this->createSpuMock($images);

        $result = $this->extractor->getProductMainImage($spu);

        $this->assertSame('main.jpg', $result);
    }

    public function testGetProductMainImageWithEmptyImages(): void
    {
        $spu = $this->createSpuMock([]);

        $result = $this->extractor->getProductMainImage($spu);

        $this->assertNull($result);
    }

    public function testGetProductMainImageWithoutUrl(): void
    {
        $images = [
            ['sort' => 1], // No url key
        ];
        $spu = $this->createSpuMock($images);

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
        $sku = $this->createSkuMock($thumbs);

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
        $sku = $this->createSkuMock(null);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithEmptyThumbs(): void
    {
        $sku = $this->createSkuMock([]);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithArrayThumbs(): void
    {
        $thumbs = [
            ['url' => 'main-thumb.jpg'],
            ['url' => 'second-thumb.jpg'],
        ];
        $sku = $this->createSkuMock($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertSame('main-thumb.jpg', $result);
    }

    public function testGetSkuMainImageWithStringThumbs(): void
    {
        $thumbs = [
            'string-thumb.jpg',
            'second-string-thumb.jpg',
        ];
        $sku = $this->createSkuMock($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertSame('string-thumb.jpg', $result);
    }

    public function testGetSkuMainImageWithInvalidFirstType(): void
    {
        $thumbs = [
            123, // Invalid type - this is checked first
            ['url' => 'valid.jpg'], // This won't be reached
        ];
        $sku = $this->createSkuMock($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        // Should return null because first element is invalid type
        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithArrayNoUrl(): void
    {
        $thumbs = [
            ['name' => 'no-url.jpg'], // No url key
        ];
        $sku = $this->createSkuMock($thumbs);

        $result = $this->extractor->getSkuMainImage($sku);

        $this->assertNull($result);
    }

    public function testGetSkuMainImageWithNonStringUrl(): void
    {
        $thumbs = [
            ['url' => 123], // Non-string url
        ];
        $sku = $this->createSkuMock($thumbs);

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
        $sku = $this->createSkuMock(null, 'ABC123', 'SKU标题', '件');

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
        $sku = $this->createSkuMock(null, 'DEF456', null, '套');

        $result = $this->extractor->getSkuSpecs($sku);

        $expected = [
            'gtin' => 'DEF456',
            'unit' => '套',
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetSkuSpecsWithOnlyTitle(): void
    {
        $sku = $this->createSkuMock(null, null, '仅标题SKU', null);

        $result = $this->extractor->getSkuSpecs($sku);

        $expected = [
            'title' => '仅标题SKU',
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetSkuSpecsWithNoData(): void
    {
        $sku = $this->createSkuMock(null, null, null, null);

        $result = $this->extractor->getSkuSpecs($sku);

        $this->assertNull($result);
    }

    public function testGetSkuSpecsWithEmptyStrings(): void
    {
        $sku = $this->createSkuMock(null, '', '', '');

        $result = $this->extractor->getSkuSpecs($sku);

        $expected = [
            'gtin' => '',
            'title' => '',
            'unit' => '',
        ];
        $this->assertSame($expected, $result);
    }

    /** @param array<mixed>|null $images */
    private function createSpuMock(?array $images = null): Spu
    {
        return new class($images) extends Spu {
            /**
             * @param array<mixed>|null $images
             */
            // @phpstan-ignore-next-line constructor.missingParentCall
            public function __construct(private readonly ?array $images)
            {
                // Skip parent constructor
            }

            /**
             * @return array<array{url: string, sort: int}>
             * @phpstan-ignore-next-line method.childReturnType
             */
            public function getImages(): array
            {
                // Test data may not strictly conform to parent type
                return $this->images ?? []; // @phpstan-ignore return.type
            }
        };
    }

    /**
     * @param array<mixed>|null $thumbs
     */
    private function createSkuMock(?array $thumbs = null, ?string $gtin = null, ?string $title = null, ?string $unit = null): Sku
    {
        return new class($thumbs, $gtin, $title, $unit) extends Sku {
            /**
             * @param array<mixed>|null $thumbs
             */
            // @phpstan-ignore-next-line constructor.missingParentCall
            public function __construct(
                private readonly ?array $thumbs,
                private readonly ?string $gtin,
                private readonly ?string $title,
                private readonly ?string $unit,
            ) {
                // Skip parent constructor
            }

            /** @return array<mixed>|null */
            public function getThumbs(): ?array
            {
                return $this->thumbs;
            }

            public function getGtin(): ?string
            {
                return $this->gtin;
            }

            public function getTitle(): ?string
            {
                return $this->title;
            }

            public function getUnit(): ?string
            {
                return $this->unit;
            }
        };
    }
}
