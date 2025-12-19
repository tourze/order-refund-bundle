<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Aftersales;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class CreateAftersalesFromOmsParam implements RpcParamInterface
{
    /**
     * @param array<string> $proofImages
     * @param array<int, array{productCode: string, productName: string, quantity: int, amount: int, reason?: string}> $products
     * @param array<string, string>|null $exchangeAddress
     */
    public function __construct(
        #[MethodParam(description: '售后单号')]
        #[Assert\NotBlank]
        public string $aftersalesNo,

        #[MethodParam(description: '售后类型: refund(仅退款), return(退货退款), exchange(换货)')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['refund', 'return', 'exchange'])]
        public string $aftersalesType,

        #[MethodParam(description: '关联订单号')]
        #[Assert\NotBlank]
        public string $orderNo,

        #[MethodParam(description: '申请原因')]
        #[Assert\NotBlank]
        public string $reason,

        #[MethodParam(description: '申请金额(分)')]
        #[Assert\PositiveOrZero]
        public int $refundAmount,

        #[MethodParam(description: '申请人姓名')]
        #[Assert\NotBlank]
        public string $applicantName,

        #[MethodParam(description: '申请人电话')]
        #[Assert\NotBlank]
        public string $applicantPhone,

        #[MethodParam(description: '申请时间')]
        #[Assert\NotBlank]
        public string $applyTime,

        #[MethodParam(description: '售后商品列表')]
        #[Assert\NotBlank]
        public array $products,

        #[MethodParam(description: '问题描述')]
        public ?string $description = null,

        #[MethodParam(description: '凭证图片URL列表')]
        public array $proofImages = [],

        #[MethodParam(description: '换货收货地址')]
        public ?array $exchangeAddress = null,
    ) {
    }
}
