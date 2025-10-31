<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Aftersales;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCCheckIPBundle\Attribute\CheckIp;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\OrderRefundBundle\Exception\AftersalesException;
use Tourze\OrderRefundBundle\Service\OmsAftersalesSyncService;

#[MethodTag(name: '售后管理')]
#[MethodDoc(summary: '从外部OMS创建新的售后单（需要IP白名单验证）')]
#[MethodExpose(method: 'CreateAftersalesFromOms')]
#[CheckIp]
#[Log]
class CreateAftersalesFromOms extends BaseProcedure implements JsonRpcMethodInterface
{
    #[MethodParam(description: '售后单号')]
    public string $aftersalesNo;

    #[MethodParam(description: '售后类型: refund(仅退款), return(退货退款), exchange(换货)')]
    public string $aftersalesType;

    #[MethodParam(description: '关联订单号')]
    public string $orderNo;

    #[MethodParam(description: '申请原因')]
    public string $reason;

    #[MethodParam(description: '问题描述')]
    public ?string $description = null;

    /** @var array<string> */
    #[MethodParam(description: '凭证图片URL列表')]
    public array $proofImages = [];

    #[MethodParam(description: '申请金额(分)')]
    public int $refundAmount;

    #[MethodParam(description: '申请人姓名')]
    public string $applicantName;

    #[MethodParam(description: '申请人电话')]
    public string $applicantPhone;

    #[MethodParam(description: '申请时间')]
    public string $applyTime;

    /** @var array<int, array{productCode: string, productName: string, quantity: int, amount: int, reason?: string}> */
    #[MethodParam(description: '售后商品列表')]
    public array $products;

    /** @var array<string, string>|null */
    #[MethodParam(description: '换货收货地址')]
    public ?array $exchangeAddress = null;

    public function __construct(
        private readonly OmsAftersalesSyncService $syncService,
    ) {
    }

    public static function getMockResult(): ?array
    {
        return [
            'success' => true,
            'message' => '售后单创建成功',
            'aftersalesId' => '12345678',
            'aftersalesNo' => 'AS202401010001',
        ];
    }

    public function execute(): array
    {
        $this->validateInput();

        try {
            $aftersalesData = [
                'aftersalesNo' => $this->aftersalesNo,
                'aftersalesType' => $this->aftersalesType,
                'orderNo' => $this->orderNo,
                'reason' => $this->reason,
                'description' => $this->description,
                'proofImages' => $this->proofImages,
                'status' => 'pending',
                'refundAmount' => $this->refundAmount,
                'applicantName' => $this->applicantName,
                'applicantPhone' => $this->applicantPhone,
                'applyTime' => $this->applyTime,
                'products' => $this->products,
                'exchangeAddress' => $this->exchangeAddress,
            ];

            $aftersales = $this->syncService->createFromOms($aftersalesData);

            return [
                'success' => true,
                'message' => '售后单创建成功',
                'aftersalesId' => (string) $aftersales->getId(),
                'aftersalesNo' => $this->aftersalesNo,
            ];
        } catch (AftersalesException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateInput(): void
    {
        $this->validateAftersalesType();
        $this->validateProductList();
        $this->validateRefundAmount();
        $this->validateExchangeRequirements();
        $this->validateImageUrls();
    }

    private function validateAftersalesType(): void
    {
        if (!in_array($this->aftersalesType, ['refund', 'return', 'exchange'], true)) {
            throw new ApiException('无效的售后类型: ' . $this->aftersalesType);
        }
    }

    private function validateProductList(): void
    {
        if ([] === $this->products) {
            throw new ApiException('售后商品列表不能为空');
        }

        foreach ($this->products as $index => $product) {
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

    private function validateRefundAmount(): void
    {
        if ($this->refundAmount < 0) {
            throw new ApiException('申请金额不能为负数');
        }
    }

    private function validateExchangeRequirements(): void
    {
        if ('exchange' === $this->aftersalesType && null === $this->exchangeAddress) {
            throw new ApiException('换货类型必须提供收货地址');
        }
    }

    private function validateImageUrls(): void
    {
        // 图片数量限制：最多9张
        if (count($this->proofImages) > 9) {
            throw new ApiException('凭证图片最多只能上传9张');
        }

        // 验证每个URL格式
        foreach ($this->proofImages as $index => $url) {
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
                throw new ApiException(sprintf('第%d个URL不是有效的图片格式，支持格式: %s', $index + 1, implode(', ', $allowedExtensions)));
            }
        }
    }
}
