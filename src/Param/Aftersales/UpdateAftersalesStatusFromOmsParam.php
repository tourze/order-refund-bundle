<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Aftersales;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class UpdateAftersalesStatusFromOmsParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '售后单号')]
        #[Assert\NotBlank(message: '售后单号不能为空')]
        public string $aftersalesNo,

        #[MethodParam(description: '新的售后状态')]
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['pending', 'approved', 'rejected', 'processing', 'completed', 'cancelled'])]
        public string $status,

        #[MethodParam(description: '审核人')]
        public ?string $auditor = null,

        #[MethodParam(description: '审核时间')]
        public ?string $auditTime = null,

        #[MethodParam(description: '审核备注')]
        public ?string $auditRemark = null,

        #[MethodParam(description: '批准金额(分)')]
        #[Assert\PositiveOrZero(message: '批准金额不能为负数')]
        public ?int $approvedAmount = null,

        /** @var array<string, string>|null */
        #[MethodParam(description: '退货物流信息')]
        public ?array $returnLogistics = null,

        #[MethodParam(description: '处理时间')]
        public ?string $processTime = null,

        #[MethodParam(description: '完成时间')]
        public ?string $completedTime = null,
    ) {}
}
