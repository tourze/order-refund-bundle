<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Aftersales;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * ApplyAftersalesProcedure 的参数对象
 *
 * 用于申请售后的请求参数
 */
readonly class ApplyAftersalesParam implements RpcParamInterface
{
    /**
     * @param string[] $proofImages
     * @param array<array{orderProductId: string, quantity: int}> $items
     */
    public function __construct(
        #[MethodParam(description: '订单ID')]
        public string $contractId = '',

        #[MethodParam(description: '售后类型')]
        public ?string $type = null,

        #[MethodParam(description: '退款原因')]
        public ?string $reason = null,

        #[MethodParam(description: '问题描述')]
        public ?string $description = null,

        #[MethodParam(description: '凭证图片')]
        public array $proofImages = [],

        #[MethodParam(description: '售后商品列表')]
        public array $items = [],
    ) {
    }
}
