<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * 商品图片提取服务
 */
final class ProductImageExtractor
{
    /**
     * 获取商品图片列表
     * @return array<array{url: string, sort: int}>|null
     */
    public function getProductImages(?Spu $spu): ?array
    {
        if (null === $spu) {
            return null;
        }

        return $spu->getImages();
    }

    /**
     * 获取商品主图
     */
    public function getProductMainImage(?Spu $spu): ?string
    {
        if (null === $spu) {
            return null;
        }

        $images = $spu->getImages();
        if ([] === $images) {
            return null;
        }

        // 返回第一张图片作为主图
        return $images[0]['url'] ?? null;
    }

    /**
     * 获取SKU图片
     * @return array<mixed>|null
     */
    public function getSkuImages(?Sku $sku): ?array
    {
        return $sku?->getThumbs();
    }

    /**
     * 获取SKU主图
     */
    public function getSkuMainImage(?Sku $sku): ?string
    {
        if (null === $sku) {
            return null;
        }

        $thumbs = $sku->getThumbs();
        if (null === $thumbs || [] === $thumbs) {
            return null;
        }

        return $this->extractImageUrlFromThumbs($thumbs);
    }

    /**
     * 从缩略图数组中提取图片URL
     * @param array<mixed> $thumbs
     */
    private function extractImageUrlFromThumbs(array $thumbs): ?string
    {
        $firstThumb = $thumbs[0] ?? null;
        if (null === $firstThumb) {
            return null;
        }

        // 如果是数组格式，返回第一个元素的 url
        if (is_array($firstThumb) && isset($firstThumb['url'])) {
            $url = $firstThumb['url'];

            return is_string($url) ? $url : null;
        }

        // 如果是字符串格式，直接返回
        if (is_string($firstThumb)) {
            return $firstThumb;
        }

        return null;
    }

    /**
     * 获取SKU规格属性
     * @return array<string, mixed>|null
     */
    public function getSkuSpecs(?Sku $sku): ?array
    {
        if (null === $sku) {
            return null;
        }

        $specs = [];

        // 基本信息
        $gtin = $sku->getGtin();
        if (null !== $gtin) {
            $specs['gtin'] = $gtin;
        }

        $title = $sku->getTitle();
        if (null !== $title) {
            $specs['title'] = $title;
        }

        $unit = $sku->getUnit();
        if (null !== $unit) {
            $specs['unit'] = $unit;
        }

        return [] === $specs ? null : $specs;
    }
}
