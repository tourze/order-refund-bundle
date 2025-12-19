<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Param\Return;

use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;

readonly class GetExpressCompaniesProcedureParam implements RpcParamInterface
{
    public function __construct()
    {
    }
}
