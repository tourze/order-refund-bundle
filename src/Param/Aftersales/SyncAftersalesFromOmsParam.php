<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Aftersales;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class SyncAftersalesFromOmsParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '售后单号')]
        #[Assert\NotBlank]
        public string $aftersalesNo = '',

        #[MethodParam(description: '售后类型: refund(仅退款), return(退货退款), exchange(换货)')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['refund', 'return', 'exchange'])]
        public string $aftersalesType = '',

        #[MethodParam(description: '关联订单号')]
        #[Assert\NotBlank]
        public string $orderNo = '',

        #[MethodParam(description: '申请原因')]
        #[Assert\NotBlank]
        public string $reason = '',

        #[MethodParam(description: '问题描述')]
        public ?string $description = null,

        /** @var array<string> */
        #[MethodParam(description: '凭证图片URL列表')]
        public array $proofImages = [],

        #[MethodParam(description: '售后状态')]
        #[Assert\NotBlank]
        public string $status = '',

        #[MethodParam(description: '申请金额(分)')]
        #[Assert\PositiveOrZero]
        public int $refundAmount = 0,

        #[MethodParam(description: '申请人姓名')]
        #[Assert\NotBlank]
        public string $applicantName = '',

        #[MethodParam(description: '申请人电话')]
        #[Assert\NotBlank]
        public string $applicantPhone = '',

        #[MethodParam(description: '申请时间')]
        #[Assert\NotBlank]
        public string $applyTime = '',

        #[MethodParam(description: '审核人')]
        public ?string $auditor = null,

        #[MethodParam(description: '审核时间')]
        public ?string $auditTime = null,

        #[MethodParam(description: '审核备注')]
        public ?string $auditRemark = null,

        /** @var array<int, array{productCode: string, productName: string, quantity: int, amount: int, reason?: string}> */
        #[MethodParam(description: '售后商品列表')]
        #[Assert\NotBlank]
        public array $products = [],

        /** @var array<string, string>|null */
        #[MethodParam(description: '退货物流信息')]
        public ?array $returnLogistics = null,

        /** @var array<string, string>|null */
        #[MethodParam(description: '换货收货地址')]
        public ?array $exchangeAddress = null,
    ) {}
}
