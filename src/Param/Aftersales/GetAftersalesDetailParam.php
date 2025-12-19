<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Aftersales;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * GetAftersalesDetailProcedure 的参数对象
 *
 * 用于获取售后单详情的请求参数
 */
readonly class GetAftersalesDetailParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '售后单ID')]
        public int|string $id,
    ) {
    }
}
