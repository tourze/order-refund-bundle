<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Aftersales;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;

#[MethodTag(name: '售后管理')]
#[MethodDoc(description: '获取售后列表')]
#[MethodExpose(method: 'GetAftersalesListProcedure')]
#[IsGranted(attribute: 'ROLE_USER')]
class GetAftersalesListProcedure extends BaseProcedure
{
    #[MethodParam(description: '页码')]
    #[Assert\Positive]
    public int $page = 1;

    #[MethodParam(description: '每页数量')]
    #[Assert\Range(min: 1, max: 50)]
    public int $limit = 10;

    #[MethodParam(description: '售后状态筛选')]
    #[Assert\Choice(callback: [AftersalesState::class, 'cases'])]
    public ?AftersalesState $state = null;

    #[MethodParam(description: '售后类型筛选')]
    #[Assert\Choice(callback: [AftersalesType::class, 'cases'])]
    public ?AftersalesType $type = null;

    public function __construct(
        private readonly Security $security,
        private readonly AftersalesRepository $aftersalesRepository,
    ) {
    }

    public function execute(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            throw new ApiException('用户未登录或类型错误');
        }

        $criteria = ['user' => $user];
        if (null !== $this->state) {
            $criteria['state'] = $this->state;
        }
        if (null !== $this->type) {
            $criteria['type'] = $this->type;
        }

        $offset = ($this->page - 1) * $this->limit;
        $orderBy = ['createTime' => 'DESC'];

        $aftersalesList = $this->aftersalesRepository->findBy($criteria, $orderBy, $this->limit, $offset);
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

        return [
            'list' => $items,
            'pagination' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $total,
                'pages' => (int) ceil($total / $this->limit),
            ],
        ];
    }
}
