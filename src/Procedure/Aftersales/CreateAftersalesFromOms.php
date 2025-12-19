<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Aftersales;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCCheckIPBundle\Attribute\CheckIp;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\OrderRefundBundle\Exception\AftersalesException;
use Tourze\OrderRefundBundle\Param\Aftersales\CreateAftersalesFromOmsParam;
use Tourze\OrderRefundBundle\Service\OmsAftersalesSyncService;

#[MethodTag(name: '售后管理')]
#[MethodDoc(summary: '从外部OMS创建新的售后单（需要IP白名单验证）')]
#[MethodExpose(method: 'CreateAftersalesFromOms')]
#[CheckIp]
#[Log]
class CreateAftersalesFromOms extends BaseProcedure implements JsonRpcMethodInterface
{
    public function __construct(
        private readonly OmsAftersalesSyncService $syncService,
    ) {
    }

    /**
     * @phpstan-param CreateAftersalesFromOmsParam $param
     */
    public function execute(CreateAftersalesFromOmsParam|RpcParamInterface $param): ArrayResult
    {
        $this->validateInput($param);

        try {
            $aftersalesData = [
                'aftersalesNo' => $param->aftersalesNo,
                'aftersalesType' => $param->aftersalesType,
                'orderNo' => $param->orderNo,
                'reason' => $param->reason,
                'description' => $param->description,
                'proofImages' => $param->proofImages,
                'status' => 'pending',
                'refundAmount' => $param->refundAmount,
                'applicantName' => $param->applicantName,
                'applicantPhone' => $param->applicantPhone,
                'applyTime' => $param->applyTime,
                'products' => $param->products,
                'exchangeAddress' => $param->exchangeAddress,
            ];

            $aftersales = $this->syncService->createFromOms($aftersalesData);

            return new ArrayResult([
                'success' => true,
                'message' => '售后单创建成功',
                'aftersalesId' => (string) $aftersales->getId(),
                'aftersalesNo' => $param->aftersalesNo,
            ]);
        } catch (AftersalesException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateInput(CreateAftersalesFromOmsParam $param): void
    {
        $this->validateAftersalesType($param);
        $this->validateProductList($param);
        $this->validateRefundAmount($param);
        $this->validateExchangeRequirements($param);
        $this->validateImageUrls($param);
    }

    private function validateAftersalesType(CreateAftersalesFromOmsParam $param): void
    {
        if (!in_array($param->aftersalesType, ['refund', 'return', 'exchange'], true)) {
            throw new ApiException('无效的售后类型: ' . $param->aftersalesType);
        }
    }

    private function validateProductList(CreateAftersalesFromOmsParam $param): void
    {
        if ([] === $param->products) {
            throw new ApiException('售后商品列表不能为空');
        }

        foreach ($param->products as $index => $product) {
            $this->validateProduct($product, $index + 1);
        }
    }

    /**
     * @param array{productCode?: string, productName?: string, quantity?: int, amount?: int} $product
     */
    private function validateProduct(array $product, int $position): void
    {
        if (!isset($product['productCode']) || '' === $product['productCode']) {
            throw new ApiException(sprintf('第%d个商品编码不能为空', $position));
        }

        if (!isset($product['productName']) || '' === $product['productName']) {
            throw new ApiException(sprintf('第%d个商品名称不能为空', $position));
        }

        if (!isset($product['quantity']) || $product['quantity'] <= 0) {
            throw new ApiException(sprintf('第%d个商品数量必须大于0', $position));
        }

        if (!isset($product['amount']) || $product['amount'] < 0) {
            throw new ApiException(sprintf('第%d个商品金额不能为负数', $position));
        }
    }

    private function validateRefundAmount(CreateAftersalesFromOmsParam $param): void
    {
        if ($param->refundAmount < 0) {
            throw new ApiException('申请金额不能为负数');
        }
    }

    private function validateExchangeRequirements(CreateAftersalesFromOmsParam $param): void
    {
        if ('exchange' === $param->aftersalesType && null === $param->exchangeAddress) {
            throw new ApiException('换货类型必须提供收货地址');
        }
    }

    private function validateImageUrls(CreateAftersalesFromOmsParam $param): void
    {
        // 图片数量限制:最多9张
        if (count($param->proofImages) > 9) {
            throw new ApiException('凭证图片最多只能上传9张');
        }

        // 验证每个URL格式
        foreach ($param->proofImages as $index => $url) {
            if ('' === $url) {
                throw new ApiException(sprintf('第%d个图片URL不能为空', $index + 1));
            }

            if (false === filter_var($url, FILTER_VALIDATE_URL)) {
                throw new ApiException(sprintf('第%d个图片URL格式无效: %s', $index + 1, $url));
            }

            // 检查是否为图片URL (支持常见图片格式)
            $parsedPath = parse_url($url, PHP_URL_PATH);
            if (null === $parsedPath || false === $parsedPath) {
                continue; // 跳过无法解析路径的URL
            }
            $extension = strtolower(pathinfo($parsedPath, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

            if ('' !== $extension && !in_array($extension, $allowedExtensions, true)) {
                throw new ApiException(sprintf('第%d个URL不是有效的图片格式,支持格式: %s', $index + 1, implode(', ', $allowedExtensions)));
            }
        }
    }
}
