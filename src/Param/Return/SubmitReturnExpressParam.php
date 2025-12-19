<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Return;

use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

/**
 * SubmitReturnExpressProcedure 的参数对象
 *
 * 用于提交退货物流信息的请求参数
 */
readonly class SubmitReturnExpressParam implements RpcParamInterface
{
    public function __construct(
        #[MethodParam(description: '售后单ID')]
        public int|string $aftersalesId,

        #[MethodParam(description: '快递公司')]
        public string $expressCompany,

        #[MethodParam(description: '快递单号')]
        public string $trackingNo,

        #[MethodParam(description: '备注信息')]
        public ?string $remark = null,
    ) {
    }
}
