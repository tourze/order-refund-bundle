<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DTO;

/**
 * 商品数据传输对象
 */
class ProductDataDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly string $skuId,
        public readonly string $productName,
        public readonly string $skuName,
        public readonly float $originalPrice,
        public readonly float $paidPrice,
        public readonly float $unitPrice,
        public readonly float $discountAmount,
        public readonly int $orderQuantity,
        /** @var array<string, mixed>|null */
        public readonly ?array $attributes = null,
        /** @var array<array{url: string, sort: int}>|null */
        public readonly ?array $productImages = null,
        public readonly ?string $productMainImage = null,
        /** @var array<mixed>|null */
        public readonly ?array $skuImages = null,
        public readonly ?string $skuMainImage = null,
        public readonly ?string $productSubtitle = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $skuSpecs = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $attributes = $data['attributes'] ?? null;
        /** @var array<string, mixed>|null $validAttributes */
        $validAttributes = is_array($attributes) ? $attributes : null;

        $productImages = $data['productImages'] ?? null;
        /** @var array<array{url: string, sort: int}>|null $validProductImages */
        $validProductImages = is_array($productImages) ? $productImages : null;

        $skuImages = $data['skuImages'] ?? null;
        /** @var array<mixed>|null $validSkuImages */
        $validSkuImages = is_array($skuImages) ? $skuImages : null;

        $skuSpecs = $data['skuSpecs'] ?? null;
        /** @var array<string, mixed>|null $validSkuSpecs */
        $validSkuSpecs = is_array($skuSpecs) ? $skuSpecs : null;

        return new self(
            productId: self::ensureString($data['productId'] ?? '', 'productId'),
            skuId: self::ensureString($data['skuId'] ?? '', 'skuId'),
            productName: self::ensureString($data['productName'] ?? '', 'productName'),
            skuName: self::ensureString($data['skuName'] ?? '', 'skuName'),
            originalPrice: self::ensureFloat($data['originalPrice'] ?? 0, 'originalPrice'),
            paidPrice: self::ensureFloat($data['paidPrice'] ?? 0, 'paidPrice'),
            unitPrice: self::ensureFloat($data['unitPrice'] ?? 0, 'unitPrice'),
            discountAmount: self::ensureFloat($data['discountAmount'] ?? 0, 'discountAmount'),
            orderQuantity: self::ensureInt($data['orderQuantity'] ?? 0, 'orderQuantity'),
            attributes: $validAttributes,
            productImages: $validProductImages,
            productMainImage: is_string($data['productMainImage'] ?? null) ? $data['productMainImage'] : null,
            skuImages: $validSkuImages,
            skuMainImage: is_string($data['skuMainImage'] ?? null) ? $data['skuMainImage'] : null,
            productSubtitle: is_string($data['productSubtitle'] ?? null) ? $data['productSubtitle'] : null,
            skuSpecs: $validSkuSpecs
        );
    }

    private static function ensureString(mixed $value, string $fieldName): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Field {$fieldName} must be a string, " . gettype($value) . ' given');
        }

        return $value;
    }

    private static function ensureFloat(mixed $value, string $fieldName): float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Field {$fieldName} must be numeric, " . gettype($value) . ' given');
        }

        return (float) $value;
    }

    private static function ensureInt(mixed $value, string $fieldName): int
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Field {$fieldName} must be numeric, " . gettype($value) . ' given');
        }

        return (int) $value;
    }

    /**
     * @return array<string>
     */
    public function validate(): array
    {
        $errors = [];

        if ('' === $this->productId) {
            $errors[] = '商品ID不能为空';
        }

        if ('' === $this->skuId) {
            $errors[] = 'SKU ID不能为空';
        }

        if ('' === $this->productName) {
            $errors[] = '商品名称不能为空';
        }

        if ($this->originalPrice < 0) {
            $errors[] = '原价不能为负数';
        }

        if ($this->paidPrice < 0) {
            $errors[] = '实付价不能为负数';
        }

        if ($this->unitPrice < 0) {
            $errors[] = '单价不能为负数';
        }

        if ($this->orderQuantity <= 0) {
            $errors[] = '商品数量必须大于0';
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'productId' => $this->productId,
            'skuId' => $this->skuId,
            'productName' => $this->productName,
            'skuName' => $this->skuName,
            'originalPrice' => $this->originalPrice,
            'paidPrice' => $this->paidPrice,
            'unitPrice' => $this->unitPrice,
            'discountAmount' => $this->discountAmount,
            'orderQuantity' => $this->orderQuantity,
            'attributes' => $this->attributes,
            'productImages' => $this->productImages,
            'productMainImage' => $this->productMainImage,
            'skuImages' => $this->skuImages,
            'skuMainImage' => $this->skuMainImage,
            'productSubtitle' => $this->productSubtitle,
            'skuSpecs' => $this->skuSpecs,
        ];
    }
}
