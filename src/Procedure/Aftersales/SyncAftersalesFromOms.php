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
use Tourze\OrderRefundBundle\Param\Aftersales\SyncAftersalesFromOmsParam;
use Tourze\OrderRefundBundle\Service\OmsAftersalesSyncService;

#[MethodTag(name: '售后管理')]
#[MethodDoc(summary: '从外部OMS同步售后信息（需要IP白名单验证）')]
#[MethodExpose(method: 'SyncAftersalesFromOms')]
#[CheckIp]
#[Log]
class SyncAftersalesFromOms extends BaseProcedure implements JsonRpcMethodInterface
{
    public function __construct(
        private readonly OmsAftersalesSyncService $syncService,
    ) {
    }

    /**
     * @phpstan-param SyncAftersalesFromOmsParam $param
     */
    public function execute(SyncAftersalesFromOmsParam|RpcParamInterface $param): ArrayResult
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
                'status' => $param->status,
                'refundAmount' => $param->refundAmount,
                'applicantName' => $param->applicantName,
                'applicantPhone' => $param->applicantPhone,
                'applyTime' => $param->applyTime,
                'auditor' => $param->auditor,
                'auditTime' => $param->auditTime,
                'auditRemark' => $param->auditRemark,
                'products' => $param->products,
                'returnLogistics' => $param->returnLogistics,
                'exchangeAddress' => $param->exchangeAddress,
            ];

            $aftersales = $this->syncService->syncFromOms($aftersalesData);

            return new ArrayResult([
                'success' => true,
                'message' => '售后信息同步成功',
                'aftersalesId' => (string) $aftersales->getId(),
            ]);
        } catch (AftersalesException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateInput(SyncAftersalesFromOmsParam $param): void
    {
        $this->validateAftersalesType($param);
        $this->validateProductList($param);
        $this->validateRefundAmount($param);
        $this->validateLogisticsRequirements($param);
    }

    private function validateAftersalesType(SyncAftersalesFromOmsParam $param): void
    {
        if (!in_array($param->aftersalesType, ['refund', 'return', 'exchange'], true)) {
            throw new ApiException('无效的售后类型: ' . $param->aftersalesType);
        }
    }

    private function validateProductList(SyncAftersalesFromOmsParam $param): void
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

    private function validateRefundAmount(SyncAftersalesFromOmsParam $param): void
    {
        if ($param->refundAmount < 0) {
            throw new ApiException('申请金额不能为负数');
        }
    }

    private function validateLogisticsRequirements(SyncAftersalesFromOmsParam $param): void
    {
        if ('exchange' === $param->aftersalesType && null === $param->exchangeAddress) {
            throw new ApiException('换货类型必须提供收货地址');
        }

        // 退货类型可以后续补充物流信息,暂不做验证
    }
}
