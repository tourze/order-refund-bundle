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
use Tourze\OrderRefundBundle\Param\Aftersales\UpdateAftersalesInfoFromOmsParam;
use Tourze\OrderRefundBundle\Service\OmsAftersalesSyncService;

#[MethodTag(name: '售后管理')]
#[MethodDoc(summary: '从外部OMS修改售后单信息（需要IP白名单验证）')]
#[MethodExpose(method: 'UpdateAftersalesInfoFromOms')]
#[CheckIp]
#[Log]
class UpdateAftersalesInfoFromOms extends BaseProcedure implements JsonRpcMethodInterface
{
    public function __construct(
        private readonly OmsAftersalesSyncService $syncService,
    ) {
    }

    /**
     * @phpstan-param UpdateAftersalesInfoFromOmsParam $param
     */
    public function execute(UpdateAftersalesInfoFromOmsParam|RpcParamInterface $param): ArrayResult
    {
        $this->validateInput($param);

        try {
            $updateData = [
                'aftersalesNo' => $param->aftersalesNo,
                'modifyReason' => $param->modifyReason,
            ];

            $modifiedFields = [];

            if (null !== $param->description) {
                $updateData['description'] = $param->description;
                $modifiedFields[] = 'description';
            }

            if (null !== $param->proofImages) {
                $updateData['proofImages'] = $param->proofImages;
                $modifiedFields[] = 'proofImages';
            }

            if (null !== $param->refundAmount) {
                $updateData['refundAmount'] = $param->refundAmount;
                $modifiedFields[] = 'refundAmount';
            }

            if (null !== $param->applicantName) {
                $updateData['applicantName'] = $param->applicantName;
                $modifiedFields[] = 'applicantName';
            }

            if (null !== $param->applicantPhone) {
                $updateData['applicantPhone'] = $param->applicantPhone;
                $modifiedFields[] = 'applicantPhone';
            }

            if (null !== $param->products) {
                $updateData['products'] = $param->products;
                $modifiedFields[] = 'products';
            }

            if (null !== $param->returnLogistics) {
                $updateData['returnLogistics'] = $param->returnLogistics;
                $modifiedFields[] = 'returnLogistics';
            }

            if (null !== $param->exchangeAddress) {
                $updateData['exchangeAddress'] = $param->exchangeAddress;
                $modifiedFields[] = 'exchangeAddress';
            }

            if (null !== $param->serviceNote) {
                $updateData['serviceNote'] = $param->serviceNote;
                $modifiedFields[] = 'serviceNote';
            }

            $aftersales = $this->syncService->updateInfoFromOms($updateData);

            return new ArrayResult([
                'success' => true,
                'message' => '售后单信息修改成功',
                'aftersalesId' => (string) $aftersales->getId(),
                'modifiedFields' => $modifiedFields,
            ]);
        } catch (AftersalesException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateInput(UpdateAftersalesInfoFromOmsParam $param): void
    {
        $this->validateBasicFields($param);
        $this->validateRefundAmount($param);
        $this->validateProductList($param);
        $this->validateHasModifications($param);
    }

    private function validateBasicFields(UpdateAftersalesInfoFromOmsParam $param): void
    {
        if (!isset($param->aftersalesNo) || '' === $param->aftersalesNo) {
            throw new ApiException('售后单号不能为空');
        }

        if ('' === $param->modifyReason) {
            throw new ApiException('修改原因不能为空');
        }
    }

    private function validateRefundAmount(UpdateAftersalesInfoFromOmsParam $param): void
    {
        if (null !== $param->refundAmount && $param->refundAmount < 0) {
            throw new ApiException('申请金额不能为负数');
        }
    }

    private function validateProductList(UpdateAftersalesInfoFromOmsParam $param): void
    {
        if (null === $param->products) {
            return;
        }

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

    private function validateHasModifications(UpdateAftersalesInfoFromOmsParam $param): void
    {
        $modifiableFields = [
            $param->description,
            $param->proofImages,
            $param->refundAmount,
            $param->applicantName,
            $param->applicantPhone,
            $param->products,
            $param->returnLogistics,
            $param->exchangeAddress,
            $param->serviceNote,
        ];

        $hasModification = array_reduce(
            $modifiableFields,
            fn ($carry, $field) => $carry || null !== $field,
            false
        );

        if (!$hasModification) {
            throw new ApiException('至少需要修改一个字段');
        }
    }
}
