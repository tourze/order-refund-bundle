<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use OrderCoreBundle\Entity\Contract;
use Symfony\Component\Security\Core\User\UserInterface;
use OrderCoreBundle\Entity\OrderProduct;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * 售后数据构建服务
 */
final class AftersalesDataBuilder
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
        private readonly ProductImageExtractor $imageExtractor,
    ) {
    }

    /**
     * 构建基础订单数据
     * @return array<string, mixed>
     */
    public function buildBaseOrderData(Contract $contract, UserInterface $user): array
    {
        return [
            'orderNumber' => $contract->getSn(),
            'orderStatus' => $contract->getState()->value,
            'orderCreateTime' => $contract->getCreateTime(),
            'userId' => $user->getUserIdentifier(),
            'totalAmount' => $this->priceCalculator->calculateTotalAmount($contract),
            'extra' => [],
        ];
    }

    /**
     * 构建商品数据
     * @return array<string, mixed>
     */
    public function buildProductData(OrderProduct $orderProduct): array
    {
        $spu = $orderProduct->getSpu();
        $sku = $orderProduct->getSku();

        $paidPrice = $this->priceCalculator->getPaidPrice($orderProduct);
        $quantity = $orderProduct->getQuantity();
        $unitPrice = $this->priceCalculator->getUnitPrice($orderProduct);

        return [
            'productId' => (string) $orderProduct->getId(),
            'skuId' => $sku?->getId() ?? '',
            'productName' => $spu?->getTitle() ?? '未知商品',
            'skuName' => $sku?->getTitle() ?? '',
            'originalPrice' => $this->priceCalculator->getOriginalPrice($orderProduct),
            'paidPrice' => $paidPrice,
            'unitPrice' => $unitPrice,
            'discountAmount' => 0,
            'orderQuantity' => $quantity,
            'attributes' => [],
            'productImages' => $this->imageExtractor->getProductImages($spu),
            'productMainImage' => $this->imageExtractor->getProductMainImage($spu),
            'skuImages' => $this->imageExtractor->getSkuImages($sku),
            'skuMainImage' => $this->imageExtractor->getSkuMainImage($sku),
            'productSubtitle' => $spu?->getSubtitle() ?? null,
            'skuSpecs' => $this->imageExtractor->getSkuSpecs($sku),
        ];
    }

    /**
     * 构建售后响应数据
     * @param Aftersales $aftersales
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function buildAftersalesResponse(Aftersales $aftersales, OrderProduct $orderProduct, array $item): array
    {
        return [
            'aftersalesId' => $aftersales->getId(),
            'state' => $aftersales->getState()->value,
            'stage' => $aftersales->getStage()->value,
            'productName' => $orderProduct->getSpu()?->getTitle() ?? '未知商品',
            'orderProductId' => $item['orderProductId'],
            'quantity' => $item['quantity'],
        ];
    }

    /**
     * 构建最终结果
     * @param array<array<string, mixed>> $aftersalesList
     * @param array<string> $errors
     * @return array<string, mixed>
     */
    public function buildFinalResult(array $aftersalesList, array $errors): array
    {
        $result = [
            'aftersalesList' => $aftersalesList,
            'totalCount' => count($aftersalesList),
            'message' => '售后申请提交完成',
        ];

        if ([] !== $errors) {
            $result['errors'] = $errors;
            $result['message'] = '售后申请部分成功，部分失败';
        }

        return $result;
    }
}
