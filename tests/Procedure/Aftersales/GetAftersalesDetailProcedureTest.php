<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Procedure\Aftersales\GetAftersalesDetailProcedure;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Repository\ReturnOrderRepository;
use Tourze\OrderRefundBundle\Service\ExpressTrackingService;
use Tourze\OrderRefundBundle\Service\ReturnAddressService;

/**
 * @internal
 */
#[CoversClass(GetAftersalesDetailProcedure::class)]
#[RunTestsInSeparateProcesses]
final class GetAftersalesDetailProcedureTest extends AbstractProcedureTestCase
{
    private GetAftersalesDetailProcedure $procedure;

    private AftersalesRepository&MockObject $aftersalesRepository;

    private ReturnAddressService&MockObject $returnAddressService;

    private ReturnOrderRepository&MockObject $returnOrderRepository;

    private ExpressTrackingService&MockObject $expressTrackingService;

    protected function onSetUp(): void
    {
        // Mock 所有需要Mock的依赖服务
        $this->aftersalesRepository = $this->createMock(AftersalesRepository::class);
        $this->returnAddressService = $this->createMock(ReturnAddressService::class);
        $this->returnOrderRepository = $this->createMock(ReturnOrderRepository::class);
        $this->expressTrackingService = $this->createMock(ExpressTrackingService::class);

        // 默认行为：返回地址服务返回空数组
        $this->returnAddressService->method('getDefaultAddressForApi')->willReturn(null);
        // 默认行为：退货订单不存在
        $this->returnOrderRepository->method('findOneBy')->willReturn(null);

        // 将Mock服务注入到容器中
        self::getContainer()->set(AftersalesRepository::class, $this->aftersalesRepository);
        self::getContainer()->set(ReturnAddressService::class, $this->returnAddressService);
        self::getContainer()->set(ReturnOrderRepository::class, $this->returnOrderRepository);
        self::getContainer()->set(ExpressTrackingService::class, $this->expressTrackingService);

        // 从容器获取procedure实例（这样Security会从容器自动注入）
        $this->procedure = self::getService(GetAftersalesDetailProcedure::class);
    }

    public function testExecuteSuccess(): void
    {
        // 创建并设置认证用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($user);

        // 创建Mock售后数据
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->method('getId')->willReturn('123');
        $aftersales->method('getReferenceNumber')->willReturn('TEST-REF-001');
        $aftersales->method('getProductId')->willReturn('product-1');
        $aftersales->method('getProductName')->willReturn('Test Product');
        $aftersales->method('getSkuId')->willReturn('sku-1');
        $aftersales->method('getSkuName')->willReturn('Test SKU');
        $aftersales->method('getQuantity')->willReturn(1);
        $aftersales->method('getType')->willReturn(AftersalesType::REFUND_ONLY);
        $aftersales->method('getReason')->willReturn(RefundReason::QUALITY_ISSUE);
        $aftersales->method('getState')->willReturn(AftersalesState::PENDING_APPROVAL);
        $aftersales->method('getStage')->willReturn(AftersalesStage::APPLY);
        $aftersales->method('getDescription')->willReturn('Test aftersales');
        $aftersales->method('getTotalRefundAmount')->willReturn(90.0);
        $aftersales->method('getOriginalRefundAmount')->willReturn('90.00');
        $aftersales->method('getActualRefundAmount')->willReturn('90.00');
        $aftersales->method('isRefundAmountModified')->willReturn(false);
        $aftersales->method('getRefundAmountModifyReason')->willReturn(null);
        $aftersales->method('getProofImages')->willReturn([]);
        $aftersales->method('getRejectReason')->willReturn(null);
        $aftersales->method('getServiceNote')->willReturn(null);
        $aftersales->method('canModify')->willReturn(true);
        $aftersales->method('canCancel')->willReturn(true);
        $aftersales->method('getAvailableActions')->willReturn(['modify', 'cancel']);
        $aftersales->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $aftersales->method('getAuditTime')->willReturn(null);
        $aftersales->method('getCompletedTime')->willReturn(null);
        $aftersales->method('getUpdateTime')->willReturn(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $aftersales->method('getProductSnapshot')->willReturn([
            'productMainImage' => 'https://example.com/image.jpg',
            'skuMainImage' => 'https://example.com/sku-image.jpg',
            'originalPrice' => '100.00',
            'paidPrice' => '90.00',
        ]);
        $aftersales->method('getModificationCount')->willReturn(0);
        $aftersales->method('getUser')->willReturn($user);

        // Mock Repository 返回售后数据
        $this->aftersalesRepository->method('find')->with(123)->willReturn($aftersales);

        // 设置参数并执行
        $this->procedure->id = '123';
        $result = $this->procedure->execute();

        // 验证返回结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('123', $result['id']);
        $this->assertEquals('TEST-REF-001', $result['referenceNumber']);
    }

    public function testExecuteWithUserNotLoggedIn(): void
    {
        // 不设置认证用户，模拟未登录状态
        // （在setUp中没有调用setAuthenticatedUser，所以默认是未登录）

        // 设置参数并执行，应该抛出异常
        $this->procedure->id = 999;

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('用户未登录或类型错误');

        $this->procedure->execute();
    }

    public function testExecuteWithAftersalesNotFound(): void
    {
        // 创建并设置认证用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($user);

        // Mock Repository 返回 null（售后单不存在）
        $this->aftersalesRepository->method('find')->with(999999)->willReturn(null);

        // 设置不存在的售后单ID
        $this->procedure->id = 999999;

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单不存在');

        $this->procedure->execute();
    }

    public function testExecuteWithUnauthorizedAccess(): void
    {
        // 创建两个用户
        $user1 = $this->createNormalUser('user1@example.com', 'password123');
        $user2 = $this->createNormalUser('user2@example.com', 'password123');

        // 创建属于用户1的Mock售后数据
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->method('getId')->willReturn('456');
        $aftersales->method('getUser')->willReturn($user1);

        // 使用用户2登录
        $this->setAuthenticatedUser($user2);

        // Mock Repository 返回属于用户1的售后单
        $this->aftersalesRepository->method('find')->with(456)->willReturn($aftersales);

        // 尝试访问用户1的售后单
        $this->procedure->id = '456';

        // 根据实际的授权逻辑，这应该抛出相应的异常
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无权限访问此售后单');

        $this->procedure->execute();
    }
}
