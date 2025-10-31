<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Aftersales;

use BizUserBundle\Entity\BizUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Repository\ReturnOrderRepository;
use Tourze\OrderRefundBundle\Service\ExpressTrackingService;
use Tourze\OrderRefundBundle\Service\ReturnAddressService;

#[MethodTag(name: '售后管理')]
#[MethodDoc(description: '获取售后单详情')]
#[MethodExpose(method: 'GetAftersalesDetailProcedure')]
#[IsGranted(attribute: 'ROLE_USER')]
class GetAftersalesDetailProcedure extends BaseProcedure
{
    #[MethodParam(description: '售后单ID')]
    public int|string $id;

    public function __construct(
        private readonly Security $security,
        private readonly AftersalesRepository $aftersalesRepository,
        private readonly ReturnAddressService $returnAddressService,
        private readonly ReturnOrderRepository $returnOrderRepository,
        private readonly ExpressTrackingService $expressTrackingService,
    ) {
    }

    public function execute(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof BizUser) {
            throw new ApiException('用户未登录或类型错误');
        }

        $aftersales = $this->aftersalesRepository->find($this->id);

        if (null === $aftersales) {
            throw new ApiException('售后单不存在');
        }

        // 验证售后单是否属于当前用户
        if ($aftersales->getUser() !== $user) {
            throw new ApiException('无权限访问此售后单');
        }

        // 格式化返回数据，参考列表接口格式
        $type = $aftersales->getType();
        $reason = $aftersales->getReason();
        $productSnapshot = $aftersales->getProductSnapshot();

        $returnAddress = $this->returnAddressService->getDefaultAddressForApi();
        $returnLogistics = $this->getReturnLogisticsInfo($aftersales);

        return [
            'id' => $aftersales->getId(),
            'referenceNumber' => $aftersales->getReferenceNumber(),
            'type' => $type?->value,
            'typeLabel' => $type?->getLabel(),
            'reason' => $reason?->value,
            'reasonLabel' => $reason?->getLabel(),
            'state' => $aftersales->getState()->value,
            'stateLabel' => $aftersales->getState()->getUserLabel(),
            'stage' => $aftersales->getStage()->value,
            'stageLabel' => $aftersales->getStage()->getLabel(),
            'totalAmount' => $aftersales->getTotalRefundAmount(),
            'originalRefundAmount' => $aftersales->getOriginalRefundAmount(),
            'actualRefundAmount' => $aftersales->getActualRefundAmount(),
            'refundAmountModified' => $aftersales->isRefundAmountModified(),
            'refundAmountModifyReason' => $aftersales->getRefundAmountModifyReason(),
            'productName' => $aftersales->getProductName(),
            'skuName' => $aftersales->getSkuName(),
            'quantity' => $aftersales->getQuantity(),
            'description' => $aftersales->getDescription(),
            'proofImages' => $aftersales->getProofImages(),
            'rejectReason' => $aftersales->getRejectReason(),
            'serviceNote' => $aftersales->getServiceNote(),
            'canModify' => $aftersales->canModify(),
            'canCancel' => $aftersales->canCancel(),
            'availableActions' => $aftersales->getAvailableActions(),
            'createTime' => $aftersales->getCreateTime()?->format('Y-m-d H:i:s'),
            'auditTime' => $aftersales->getAuditTime()?->format('Y-m-d H:i:s'),
            'completedTime' => $aftersales->getCompletedTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $aftersales->getUpdateTime()?->format('Y-m-d H:i:s'),
            'products' => [
                [
                    'id' => $aftersales->getProductId(),
                    'name' => $aftersales->getProductName(),
                    'skuId' => $aftersales->getSkuId(),
                    'skuName' => $aftersales->getSkuName(),
                    'mainThumb' => $productSnapshot['skuMainImage'] ?? $productSnapshot['productMainImage'] ?? '',
                    'quantity' => $aftersales->getQuantity(),
                    'originalPrice' => $productSnapshot['originalPrice'] ?? '',
                    'paidPrice' => $productSnapshot['paidPrice'] ?? '',
                    'totalPrice' => $productSnapshot['paidPrice'] ?? '',
                ],
            ],
            'productSnapshot' => $productSnapshot,
            'modificationCount' => $aftersales->getModificationCount(),
            'returnAddress' => $returnAddress,
            'returnLogistics' => $returnLogistics,
        ];
    }

    /**
     * 获取退货物流信息
     * @return array<string, mixed>|null
     */
    private function getReturnLogisticsInfo(Aftersales $aftersales): ?array
    {
        $returnOrder = $this->returnOrderRepository->findOneBy(['aftersales' => $aftersales]);

        if (null === $returnOrder || !$returnOrder->isShipped()) {
            return null;
        }

        $trackingUrl = $this->expressTrackingService->generateTrackingUrlForReturn($returnOrder);

        return [
            'expressCompany' => $returnOrder->getExpressCompany(),
            'trackingNo' => $returnOrder->getTrackingNo(),
            'shipTime' => $returnOrder->getShipTime()?->format('Y-m-d H:i:s'),
            'status' => $returnOrder->getStatus()->value,
            'statusLabel' => $returnOrder->getStatus()->getLabel(),
            'trackingUrl' => $trackingUrl,
            'remark' => $returnOrder->getRemark(),
        ];
    }
}
