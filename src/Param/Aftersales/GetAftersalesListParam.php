<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Aftersales;

use Symfony\Component\Validator\Constraints as Assert;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;

/**
 * GetAftersalesListProcedure 的参数对象
 *
 * 用于获取售后列表的请求参数
 */
readonly class GetAftersalesListParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '页码')]
        #[Assert\Positive]
        public int $page = 1,

        #[MethodParam(description: '每页数量')]
        #[Assert\Range(min: 1, max: 50)]
        public int $limit = 10,

        #[MethodParam(description: '售后状态筛选')]
        #[Assert\Choice(callback: [AftersalesState::class, 'cases'])]
        public ?AftersalesState $state = null,

        #[MethodParam(description: '售后类型筛选')]
        #[Assert\Choice(callback: [AftersalesType::class, 'cases'])]
        public ?AftersalesType $type = null,
    ) {
    }
}
