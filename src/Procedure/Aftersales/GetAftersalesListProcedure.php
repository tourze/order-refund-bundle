<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Aftersales;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderRefundBundle\Param\Aftersales\GetAftersalesListParam;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;

#[MethodTag(name: '售后管理')]
#[MethodDoc(description: '获取售后列表')]
#[MethodExpose(method: 'GetAftersalesListProcedure')]
#[IsGranted(attribute: 'ROLE_USER')]
class GetAftersalesListProcedure extends BaseProcedure
{
    public function __construct(
        private readonly Security $security,
        private readonly AftersalesRepository $aftersalesRepository,
    ) {
    }

    /**
     * @phpstan-param GetAftersalesListParam $param
     */
    public function execute(GetAftersalesListParam|RpcParamInterface $param): ArrayResult
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            throw new ApiException('用户未登录或类型错误');
        }

        $criteria = ['user' => $user];
        if (null !== $param->state) {
            $criteria['state'] = $param->state;
        }
        if (null !== $param->type) {
            $criteria['type'] = $param->type;
        }

        $offset = ($param->page - 1) * $param->limit;
        $orderBy = ['createTime' => 'DESC'];

        $aftersalesList = $this->aftersalesRepository->findBy($criteria, $orderBy, $param->limit, $offset);
        $total = $this->aftersalesRepository->count($criteria);

        $items = [];
        foreach ($aftersalesList as $aftersales) {
            $type = $aftersales->getType();
            $reason = $aftersales->getReason();
            $productSnapshot = $aftersales->getProductSnapshot();
            $items[] = [
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
                'productName' => $aftersales->getProductName(),
                'skuName' => $aftersales->getSkuName(),
                'quantity' => $aftersales->getQuantity(),
                'description' => $aftersales->getDescription(),
                'proofImages' => $aftersales->getProofImages(),
                'canModify' => $aftersales->canModify(),
                'canCancel' => $aftersales->canCancel(),
                'availableActions' => $aftersales->getAvailableActions(),
                'createTime' => $aftersales->getCreateTime()?->format('Y-m-d H:i:s'),
                'auditTime' => $aftersales->getAuditTime()?->format('Y-m-d H:i:s'),
                'completedTime' => $aftersales->getCompletedTime()?->format('Y-m-d H:i:s'),
                'products' => [
                    [
                        'id' => $aftersales->getProductId(),
                        'name' => $aftersales->getProductName(),
                        'skuId' => $aftersales->getSkuId(),
                        'skuName' => $aftersales->getSkuName(),
                        'mainThumb' => $productSnapshot['skuMainImage'] ?? '',
                        'quantity' => $aftersales->getQuantity(),
                        'totalPrice' => $productSnapshot['paidPrice'] ?? '',
                    ],
                ],
            ];
        }

        return new ArrayResult([
            'list' => $items,
            'pagination' => [
                'page' => $param->page,
                'limit' => $param->limit,
                'total' => $total,
                'pages' => (int) ceil($total / $param->limit),
            ],
        ]);
    }
}
