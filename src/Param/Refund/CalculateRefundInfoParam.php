<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Refund;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * CalculateRefundInfoProcedure 的参数对象
 *
 * 用于计算退款信息的请求参数
 */
readonly class CalculateRefundInfoParam implements RpcParamInterface
{
    /**
     * @param array<array{orderProductId: string, quantity: int}> $items
     */
    public function __construct(
        #[MethodParam(description: '订单ID')]
        #[Assert\NotBlank]
        public string $contractId = '',

        #[MethodParam(description: '商品退款申请列表')]
        #[Assert\NotBlank]
        #[Assert\Type(type: 'array')]
        public array $items = [],
    ) {
    }
}
