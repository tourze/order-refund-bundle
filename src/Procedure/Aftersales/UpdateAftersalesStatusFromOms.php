<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Aftersales;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Contracts\RpcParamInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCCheckIPBundle\Attribute\CheckIp;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\OrderRefundBundle\Exception\AftersalesException;
use Tourze\OrderRefundBundle\Param\Aftersales\UpdateAftersalesStatusFromOmsParam;
use Tourze\OrderRefundBundle\Service\OmsAftersalesSyncService;

#[MethodTag(name: '售后管理')]
#[MethodDoc(summary: '从外部OMS更新售后单状态（需要IP白名单验证）')]
#[MethodExpose(method: 'UpdateAftersalesStatusFromOms')]
#[CheckIp]
#[Log]
class UpdateAftersalesStatusFromOms extends BaseProcedure implements JsonRpcMethodInterface
{
    public function __construct(
        private readonly OmsAftersalesSyncService $syncService,
    ) {
    }

    /**
     * @phpstan-param UpdateAftersalesStatusFromOmsParam $param
     */
    public function execute(UpdateAftersalesStatusFromOmsParam|RpcParamInterface $param): ArrayResult
    {
        $this->validateInput($param);

        try {
            $statusData = [
                'aftersalesNo' => $param->aftersalesNo,
                'status' => $param->status,
                'auditor' => $param->auditor,
                'auditTime' => $param->auditTime,
                'auditRemark' => $param->auditRemark,
                'approvedAmount' => $param->approvedAmount,
                'returnLogistics' => $param->returnLogistics,
                'processTime' => $param->processTime,
                'completedTime' => $param->completedTime,
            ];

            $result = $this->syncService->updateStatusFromOms($statusData);

            return new ArrayResult([
                'success' => true,
                'message' => '售后单状态更新成功',
                'aftersalesId' => (string) $result['aftersales']->getId(),
                'oldStatus' => $result['oldStatus'],
                'newStatus' => $param->status,
            ]);
        } catch (AftersalesException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateInput(UpdateAftersalesStatusFromOmsParam $param): void
    {
        if (!isset($param->aftersalesNo) || '' === $param->aftersalesNo) {
            throw new ApiException('售后单号不能为空');
        }

        $validStatuses = ['pending', 'approved', 'rejected', 'processing', 'completed', 'cancelled'];
        if (!in_array($param->status, $validStatuses, true)) {
            throw new ApiException('无效的售后状态: ' . $param->status);
        }

        if (null !== $param->approvedAmount && $param->approvedAmount < 0) {
            throw new ApiException('批准金额不能为负数');
        }

        if (null !== $param->auditTime && false === strtotime($param->auditTime)) {
            throw new ApiException('审核时间格式无效');
        }

        if (null !== $param->processTime && false === strtotime($param->processTime)) {
            throw new ApiException('处理时间格式无效');
        }

        if (null !== $param->completedTime && false === strtotime($param->completedTime)) {
            throw new ApiException('完成时间格式无效');
        }
    }
}
