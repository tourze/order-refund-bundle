<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Aftersales;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class UpdateAftersalesInfoFromOmsParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '售后单号')]
        #[Assert\NotBlank(message: '售后单号不能为空')]
        public string $aftersalesNo,

        #[MethodParam(description: '问题描述')]
        public ?string $description = null,

        /** @var array<string>|null */
        #[MethodParam(description: '凭证图片URL列表')]
        public ?array $proofImages = null,

        #[MethodParam(description: '申请金额(分)')]
        #[Assert\PositiveOrZero(message: '申请金额不能为负数')]
        public ?int $refundAmount = null,

        #[MethodParam(description: '申请人姓名')]
        public ?string $applicantName = null,

        #[MethodParam(description: '申请人电话')]
        public ?string $applicantPhone = null,

        /** @var array<int, array{productCode: string, productName: string, quantity: int, amount: int, reason?: string}>|null */
        #[MethodParam(description: '售后商品列表')]
        public ?array $products = null,

        /** @var array<string, string>|null */
        #[MethodParam(description: '退货物流信息')]
        public ?array $returnLogistics = null,

        /** @var array<string, string>|null */
        #[MethodParam(description: '换货收货地址')]
        public ?array $exchangeAddress = null,

        #[MethodParam(description: '客服备注')]
        public ?string $serviceNote = null,

        #[MethodParam(description: '修改原因')]
        #[Assert\NotBlank(message: '修改原因不能为空')]
        public string $modifyReason = '',
    ) {}
}
