<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Aftersales;

use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Domain\JsonRpcMethodInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\JsonRPCCheckIPBundle\Attribute\CheckIp;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\OrderRefundBundle\Exception\AftersalesException;
use Tourze\OrderRefundBundle\Service\OmsAftersalesSyncService;

#[MethodTag(name: '售后管理')]
#[MethodDoc(summary: '从外部OMS更新售后单状态（需要IP白名单验证）')]
#[MethodExpose(method: 'UpdateAftersalesStatusFromOms')]
#[CheckIp]
#[Log]
class UpdateAftersalesStatusFromOms extends BaseProcedure implements JsonRpcMethodInterface
{
    #[MethodParam(description: '售后单号')]
    public string $aftersalesNo;

    #[MethodParam(description: '新的售后状态')]
    public string $status;

    #[MethodParam(description: '审核人')]
    public ?string $auditor = null;

    #[MethodParam(description: '审核时间')]
    public ?string $auditTime = null;

    #[MethodParam(description: '审核备注')]
    public ?string $auditRemark = null;

    #[MethodParam(description: '批准金额(分)')]
    public ?int $approvedAmount = null;

    /** @var array<string, string>|null */
    #[MethodParam(description: '退货物流信息')]
    public ?array $returnLogistics = null;

    #[MethodParam(description: '处理时间')]
    public ?string $processTime = null;

    #[MethodParam(description: '完成时间')]
    public ?string $completedTime = null;

    public function __construct(
        private readonly OmsAftersalesSyncService $syncService,
    ) {
    }

    public static function getMockResult(): ?array
    {
        return [
            'success' => true,
            'message' => '售后单状态更新成功',
            'aftersalesId' => '12345678',
            'oldStatus' => 'pending',
            'newStatus' => 'approved',
        ];
    }

    public function execute(): array
    {
        $this->validateInput();

        try {
            $statusData = [
                'aftersalesNo' => $this->aftersalesNo,
                'status' => $this->status,
                'auditor' => $this->auditor,
                'auditTime' => $this->auditTime,
                'auditRemark' => $this->auditRemark,
                'approvedAmount' => $this->approvedAmount,
                'returnLogistics' => $this->returnLogistics,
                'processTime' => $this->processTime,
                'completedTime' => $this->completedTime,
            ];

            $result = $this->syncService->updateStatusFromOms($statusData);

            return [
                'success' => true,
                'message' => '售后单状态更新成功',
                'aftersalesId' => (string) $result['aftersales']->getId(),
                'oldStatus' => $result['oldStatus'],
                'newStatus' => $this->status,
            ];
        } catch (AftersalesException $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function validateInput(): void
    {
        if (!isset($this->aftersalesNo) || '' === $this->aftersalesNo) {
            throw new ApiException('售后单号不能为空');
        }

        $validStatuses = ['pending', 'approved', 'rejected', 'processing', 'completed', 'cancelled'];
        if (!in_array($this->status, $validStatuses, true)) {
            throw new ApiException('无效的售后状态: ' . $this->status);
        }

        if (null !== $this->approvedAmount && $this->approvedAmount < 0) {
            throw new ApiException('批准金额不能为负数');
        }

        if (null !== $this->auditTime && false === strtotime($this->auditTime)) {
            throw new ApiException('审核时间格式无效');
        }

        if (null !== $this->processTime && false === strtotime($this->processTime)) {
            throw new ApiException('处理时间格式无效');
        }

        if (null !== $this->completedTime && false === strtotime($this->completedTime)) {
            throw new ApiException('完成时间格式无效');
        }
    }
}
