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
#[MethodDoc(summary: '从外部OMS修改售后单信息（需要IP白名单验证）')]
#[MethodExpose(method: 'UpdateAftersalesInfoFromOms')]
#[CheckIp]
#[Log]
class UpdateAftersalesInfoFromOms extends BaseProcedure implements JsonRpcMethodInterface
{
    #[MethodParam(description: '售后单号')]
    public string $aftersalesNo;

    #[MethodParam(description: '问题描述')]
    public ?string $description = null;

    /** @var array<string> */
    #[MethodParam(description: '凭证图片URL列表')]
    public ?array $proofImages = null;

    #[MethodParam(description: '申请金额(分)')]
    public ?int $refundAmount = null;

    #[MethodParam(description: '申请人姓名')]
    public ?string $applicantName = null;

    #[MethodParam(description: '申请人电话')]
    public ?string $applicantPhone = null;

    /** @var array<int, array{productCode: string, productName: string, quantity: int, amount: int, reason?: string}> */
    #[MethodParam(description: '售后商品列表')]
    public ?array $products = null;

    /** @var array<string, string>|null */
    #[MethodParam(description: '退货物流信息')]
    public ?array $returnLogistics = null;

    /** @var array<string, string>|null */
    #[MethodParam(description: '换货收货地址')]
    public ?array $exchangeAddress = null;

    #[MethodParam(description: '客服备注')]
    public ?string $serviceNote = null;

    #[MethodParam(description: '修改原因')]
    public string $modifyReason = '';

    public function __construct(
        private readonly OmsAftersalesSyncService $syncService,
    ) {
    }

    public static function getMockResult(): ?array
    {
        return [
            'success' => true,
            'message' => '售后单信息修改成功',
            'aftersalesId' => '12345678',
            'modifiedFields' => ['description', 'refundAmount'],
        ];
    }

    public function execute(): array
    {
        $this->validateInput();

        try {
            $updateData = [
                'aftersalesNo' => $this->aftersalesNo,
                'modifyReason' => $this->modifyReason,
            ];

            $modifiedFields = [];

            if (null !== $this->description) {
                $updateData['description'] = $this->description;
                $modifiedFields[] = 'description';
            }

            if (null !== $this->proofImages) {
                $updateData['proofImages'] = $this->proofImages;
                $modifiedFields[] = 'proofImages';
            }

            if (null !== $this->refundAmount) {
                $updateData['refundAmount'] = $this->refundAmount;
                $modifiedFields[] = 'refundAmount';
            }

            if (null !== $this->applicantName) {
                $updateData['applicantName'] = $this->applicantName;
                $modifiedFields[] = 'applicantName';
            }

            if (null !== $this->applicantPhone) {
                $updateData['applicantPhone'] = $this->applicantPhone;
                $modifiedFields[] = 'applicantPhone';
            }

            if (null !== $this->products) {
                $updateData['products'] = $this->products;
                $modifiedFields[] = 'products';
            }

            if (null !== $this->returnLogistics) {
                $updateData['returnLogistics'] = $this->returnLogistics;
                $modifiedFields[] = 'returnLogistics';
            }

            if (null !== $this->exchangeAddress) {
                $updateData['exchangeAddress'] = $this->exchangeAddress;
                $modifiedFields[] = 'exchangeAddress';
            }

            if (null !== $this->serviceNote) {
                $updateData['serviceNote'] = $this->serviceNote;
                $modifiedFields[] = 'serviceNote';
            }

            $aftersales = $this->syncService->updateInfoFromOms($updateData);

            return [
                'success' => true,
                'message' => '售后单信息修改成功',
                'aftersalesId' => (string) $aftersales->getId(),
                'modifiedFields' => $modifiedFields,
            ];
        } catch (AftersalesException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateInput(): void
    {
        $this->validateBasicFields();
        $this->validateRefundAmount();
        $this->validateProductList();
        $this->validateHasModifications();
    }

    private function validateBasicFields(): void
    {
        if (!isset($this->aftersalesNo) || '' === $this->aftersalesNo) {
            throw new ApiException('售后单号不能为空');
        }

        if ('' === $this->modifyReason) {
            throw new ApiException('修改原因不能为空');
        }
    }

    private function validateRefundAmount(): void
    {
        if (null !== $this->refundAmount && $this->refundAmount < 0) {
            throw new ApiException('申请金额不能为负数');
        }
    }

    private function validateProductList(): void
    {
        if (null === $this->products) {
            return;
        }

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

    private function validateHasModifications(): void
    {
        $modifiableFields = [
            $this->description,
            $this->proofImages,
            $this->refundAmount,
            $this->applicantName,
            $this->applicantPhone,
            $this->products,
            $this->returnLogistics,
            $this->exchangeAddress,
            $this->serviceNote,
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
