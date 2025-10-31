<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Return;

use BizUserBundle\Entity\BizUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Repository\ReturnOrderRepository;
use Tourze\OrderRefundBundle\Service\ExpressTrackingService;

#[MethodTag(name: '退货管理')]
#[MethodDoc(description: '提交退货物流信息')]
#[MethodExpose(method: 'SubmitReturnExpressProcedure')]
#[IsGranted(attribute: 'ROLE_USER')]
class SubmitReturnExpressProcedure extends BaseProcedure
{
    #[MethodParam(description: '售后单ID')]
    public int|string $aftersalesId;

    #[MethodParam(description: '快递公司')]
    public string $expressCompany;

    #[MethodParam(description: '快递单号')]
    public string $trackingNo;

    #[MethodParam(description: '备注信息')]
    public ?string $remark = null;

    public function __construct(
        private readonly Security $security,
        private readonly AftersalesRepository $aftersalesRepository,
        private readonly ReturnOrderRepository $returnOrderRepository,
        private readonly ExpressTrackingService $expressTrackingService,
    ) {
    }

    public function execute(): array
    {
        $user = $this->getCurrentUser();
        $aftersales = $this->validateAftersales($user);
        $returnOrder = $this->getOrCreateReturnOrder($aftersales);

        // 验证物流信息格式
        $this->validateExpressInfo();

        // 检查是否已提交过物流信息
        $this->checkDuplicateSubmission($returnOrder);

        // 更新退货物流信息
        $returnOrder->markAsShipped($this->expressCompany, $this->trackingNo);

        if (null !== $this->remark && '' !== trim($this->remark)) {
            $returnOrder->setRemark($this->remark);
        }

        // 更新售后状态
        if (AftersalesStage::RETURN === $aftersales->getStage()) {
            $aftersales->setStage(AftersalesStage::RECEIVE);
            $this->aftersalesRepository->save($aftersales, true);
        }

        // 保存数据
        $this->returnOrderRepository->save($returnOrder, true);

        return [
            'success' => true,
            'message' => '物流信息提交成功',
            'data' => [
                'aftersalesId' => $aftersales->getId(),
                'returnOrderId' => $returnOrder->getId(),
                'expressCompany' => $returnOrder->getExpressCompany(),
                'trackingNo' => $returnOrder->getTrackingNo(),
                'shipTime' => $returnOrder->getShipTime()?->format('Y-m-d H:i:s'),
                'trackingUrl' => $this->expressTrackingService->generateTrackingUrlForReturn($returnOrder),
            ],
        ];
    }

    private function getCurrentUser(): BizUser
    {
        $user = $this->security->getUser();
        if (!$user instanceof BizUser) {
            throw new ApiException('用户未登录或类型错误');
        }

        return $user;
    }

    private function validateAftersales(BizUser $user): Aftersales
    {
        if ('' === $this->aftersalesId) {
            throw new ApiException('售后单ID不能为空');
        }

        $aftersales = $this->aftersalesRepository->find($this->aftersalesId);
        if (null === $aftersales) {
            throw new ApiException('售后单不存在');
        }

        // 验证权限
        if ($aftersales->getUser() !== $user) {
            throw new ApiException('无权限操作此售后单');
        }

        // 验证售后类型
        if (AftersalesType::RETURN_REFUND !== $aftersales->getType()) {
            throw new ApiException('该售后单不需要退货');
        }

        // 验证售后状态
        if (AftersalesState::APPROVED !== $aftersales->getState()) {
            throw new ApiException('售后单未审核通过，无法提交物流信息');
        }

        // 验证售后阶段
        if (!in_array($aftersales->getStage(), [AftersalesStage::RETURN, AftersalesStage::RECEIVE], true)) {
            throw new ApiException('当前阶段无法提交物流信息');
        }

        return $aftersales;
    }

    private function getOrCreateReturnOrder(Aftersales $aftersales): ReturnOrder
    {
        // 查找现有的退货单
        $returnOrder = $this->returnOrderRepository->findOneBy(['aftersales' => $aftersales]);

        if (null === $returnOrder) {
            // 如果不存在则创建新的退货单
            $returnOrder = new ReturnOrder();
            $returnOrder->setAftersales($aftersales);
        }

        return $returnOrder;
    }

    private function validateExpressInfo(): void
    {
        if ('' === trim($this->expressCompany)) {
            throw new ApiException('快递公司不能为空');
        }

        if ('' === trim($this->trackingNo)) {
            throw new ApiException('快递单号不能为空');
        }

        if (strlen($this->expressCompany) > 50) {
            throw new ApiException('快递公司名称过长');
        }

        if (strlen($this->trackingNo) > 50) {
            throw new ApiException('快递单号过长');
        }

        // 快递单号格式验证（基础验证，避免明显的错误）
        if (1 !== preg_match('/^[A-Za-z0-9]+$/', $this->trackingNo)) {
            throw new ApiException('快递单号格式不正确，只能包含字母和数字');
        }

        // 验证快递公司是否存在且启用
        if (!$this->expressTrackingService->validateExpressCompany($this->expressCompany)) {
            throw new ApiException('不支持的快递公司或快递公司已停用');
        }
    }

    private function checkDuplicateSubmission(ReturnOrder $returnOrder): void
    {
        // 如果已经填写过物流信息且状态不是待处理，则不允许重复提交
        if ($returnOrder->isShipped()) {
            throw new ApiException('物流信息已提交，无法重复提交');
        }
    }
}
