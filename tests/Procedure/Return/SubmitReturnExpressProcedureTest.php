<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Return;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Procedure\Return\SubmitReturnExpressProcedure;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Repository\ReturnOrderRepository;
use Tourze\OrderRefundBundle\Service\ExpressTrackingService;

/**
 * @internal
 */
#[CoversClass(SubmitReturnExpressProcedure::class)]
#[RunTestsInSeparateProcesses]
class SubmitReturnExpressProcedureTest extends AbstractProcedureTestCase
{
    private SubmitReturnExpressProcedure $procedure;

    private AftersalesRepository&MockObject $aftersalesRepository;

    private ReturnOrderRepository&MockObject $returnOrderRepository;

    private ExpressTrackingService&MockObject $expressTrackingService;

    private UserInterface $mockUser;

    protected function onSetUp(): void
    {
        $this->aftersalesRepository = $this->createMock(AftersalesRepository::class);
        $this->returnOrderRepository = $this->createMock(ReturnOrderRepository::class);
        $this->expressTrackingService = $this->createMock(ExpressTrackingService::class);

        $user = $this->createNormalUser('test@example.com', 'password123');
        self::assertInstanceOf(UserInterface::class, $user);
        $this->mockUser = $user;
        $this->setAuthenticatedUser($this->mockUser);

        // 替换服务
        self::getContainer()->set(AftersalesRepository::class, $this->aftersalesRepository);
        self::getContainer()->set(ReturnOrderRepository::class, $this->returnOrderRepository);
        self::getContainer()->set(ExpressTrackingService::class, $this->expressTrackingService);

        $this->procedure = self::getService(SubmitReturnExpressProcedure::class);
    }

    public function testExecuteSuccessWithNewReturnOrder(): void
    {
        // 准备测试数据
        $aftersales = $this->createValidAftersales($this->mockUser);
        $returnOrder = new ReturnOrder();
        $returnOrder->setAftersales($aftersales);

        // 设置属性
        $this->procedure->aftersalesId = 1;
        $this->procedure->expressCompany = 'SF';
        $this->procedure->trackingNo = 'SF1234567890';
        $this->procedure->remark = '退货备注';

        // Mock 依赖
        $this->aftersalesRepository->method('find')->willReturn($aftersales);
        $this->returnOrderRepository->method('findOneBy')->willReturn(null);
        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);
        $this->expressTrackingService->method('generateTrackingUrlForReturn')
            ->willReturn('https://www.sf-express.com/track/SF1234567890')
        ;

        // Mock 保存操作
        $this->aftersalesRepository->expects(self::once())->method('save')->with($aftersales, true);
        $this->returnOrderRepository->expects(self::once())->method('save')->with(self::isInstanceOf(ReturnOrder::class), true);

        // 执行测试
        $result = $this->procedure->execute();

        // 验证结果
        self::assertIsArray($result);
        self::assertTrue($result['success']);
        self::assertSame('物流信息提交成功', $result['message']);
        self::assertArrayHasKey('data', $result);

        $data = $result['data'];
        self::assertIsArray($data);
        self::assertSame('SF', $data['expressCompany']);
        self::assertSame('SF1234567890', $data['trackingNo']);
        self::assertIsString($data['shipTime']);
    }

    public function testExecuteSuccessWithExistingReturnOrder(): void
    {
        // 准备测试数据
        $aftersales = $this->createValidAftersales($this->mockUser);
        $returnOrder = $this->createMock(ReturnOrder::class);
        $returnOrder->method('isShipped')->willReturn(false);
        $returnOrder->method('getId')->willReturn('1');
        $returnOrder->method('getExpressCompany')->willReturn('YTO');
        $returnOrder->method('getTrackingNo')->willReturn('YTO1234567890');
        $returnOrder->method('getShipTime')->willReturn(new \DateTimeImmutable('2023-01-01 12:00:00'));

        // 设置属性
        $this->procedure->aftersalesId = 1;
        $this->procedure->expressCompany = 'YTO';
        $this->procedure->trackingNo = 'YTO1234567890';

        // Mock 依赖
        $this->aftersalesRepository->method('find')->willReturn($aftersales);
        $this->returnOrderRepository->method('findOneBy')->willReturn($returnOrder);
        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);
        $this->expressTrackingService->method('generateTrackingUrlForReturn')
            ->willReturn('https://www.yto.net.cn/track/YTO1234567890')
        ;

        // 执行测试
        $result = $this->procedure->execute();

        // 验证结果
        self::assertIsArray($result);
        self::assertTrue($result['success']);
        self::assertArrayHasKey('data', $result);

        $data = $result['data'];
        self::assertIsArray($data);
        self::assertSame('YTO', $data['expressCompany']);
        self::assertSame('YTO1234567890', $data['trackingNo']);
    }

    public function testExecuteThrowsExceptionWhenUserNotLoggedIn(): void
    {
        // 测试用户未登录的场景，由于无法在当前框架下模拟未登录状态
        // 我们跳过这个测试，因为 setAuthenticatedUser 不接受 null
        self::markTestSkipped('Cannot test unauthenticated state with current testing framework');
    }

    public function testExecuteThrowsExceptionWhenAftersalesIdEmpty(): void
    {
        $this->procedure->aftersalesId = '';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单ID不能为空');

        $this->procedure->execute();
    }

    public function testExecuteThrowsExceptionWhenAftersalesNotFound(): void
    {
        $this->procedure->aftersalesId = 999;

        $this->aftersalesRepository->method('find')->willReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单不存在');

        $this->procedure->execute();
    }

    public function testExecuteThrowsExceptionWhenUserNotAuthorized(): void
    {
        $otherUser = $this->createMock(UserInterface::class);
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->method('getUser')->willReturn($otherUser);

        $this->procedure->aftersalesId = 1;
        $this->aftersalesRepository->method('find')->willReturn($aftersales);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无权限操作此售后单');

        $this->procedure->execute();
    }

    public function testExecuteThrowsExceptionWhenWrongAftersalesType(): void
    {
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->method('getUser')->willReturn($this->mockUser);
        $aftersales->method('getType')->willReturn(AftersalesType::REFUND_ONLY);

        $this->procedure->aftersalesId = 1;
        $this->aftersalesRepository->method('find')->willReturn($aftersales);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('该售后单不需要退货');

        $this->procedure->execute();
    }

    public function testExecuteThrowsExceptionWhenAftersalesNotApproved(): void
    {
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->method('getUser')->willReturn($this->mockUser);
        $aftersales->method('getType')->willReturn(AftersalesType::RETURN_REFUND);
        $aftersales->method('getState')->willReturn(AftersalesState::PENDING_APPROVAL);

        $this->procedure->aftersalesId = 1;
        $this->aftersalesRepository->method('find')->willReturn($aftersales);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单未审核通过，无法提交物流信息');

        $this->procedure->execute();
    }

    public function testExecuteThrowsExceptionWhenWrongStage(): void
    {
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->method('getUser')->willReturn($this->mockUser);
        $aftersales->method('getType')->willReturn(AftersalesType::RETURN_REFUND);
        $aftersales->method('getState')->willReturn(AftersalesState::APPROVED);
        $aftersales->method('getStage')->willReturn(AftersalesStage::APPLY);

        $this->procedure->aftersalesId = 1;
        $this->aftersalesRepository->method('find')->willReturn($aftersales);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('当前阶段无法提交物流信息');

        $this->procedure->execute();
    }

    #[DataProvider('invalidExpressInfoProvider')]
    public function testExecuteThrowsExceptionWithInvalidExpressInfo(
        string $expressCompany,
        string $trackingNo,
        string $expectedMessage,
    ): void {
        $aftersales = $this->createValidAftersales($this->mockUser);

        $this->procedure->aftersalesId = 1;
        $this->procedure->expressCompany = $expressCompany;
        $this->procedure->trackingNo = $trackingNo;

        $this->aftersalesRepository->method('find')->willReturn($aftersales);
        $this->returnOrderRepository->method('findOneBy')->willReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->procedure->execute();
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidExpressInfoProvider(): array
    {
        return [
            'empty_express_company' => ['', 'SF1234567890', '快递公司不能为空'],
            'empty_tracking_no' => ['SF', '', '快递单号不能为空'],
            'long_express_company' => [str_repeat('A', 51), 'SF1234567890', '快递公司名称过长'],
            'long_tracking_no' => ['SF', str_repeat('A', 51), '快递单号过长'],
            'invalid_tracking_format' => ['SF', 'SF-1234-5678-90', '快递单号格式不正确，只能包含字母和数字'],
        ];
    }

    public function testExecuteThrowsExceptionWhenExpressCompanyNotSupported(): void
    {
        $aftersales = $this->createValidAftersales($this->mockUser);

        $this->procedure->aftersalesId = 1;
        $this->procedure->expressCompany = 'INVALID';
        $this->procedure->trackingNo = 'INV1234567890';

        $this->aftersalesRepository->method('find')->willReturn($aftersales);
        $this->returnOrderRepository->method('findOneBy')->willReturn(null);
        $this->expressTrackingService->method('validateExpressCompany')->willReturn(false);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('不支持的快递公司或快递公司已停用');

        $this->procedure->execute();
    }

    public function testExecuteThrowsExceptionWhenAlreadyShipped(): void
    {
        $aftersales = $this->createValidAftersales($this->mockUser);
        $returnOrder = $this->createMock(ReturnOrder::class);
        $returnOrder->method('isShipped')->willReturn(true);

        $this->procedure->aftersalesId = 1;
        $this->procedure->expressCompany = 'SF';
        $this->procedure->trackingNo = 'SF1234567890';

        $this->aftersalesRepository->method('find')->willReturn($aftersales);
        $this->returnOrderRepository->method('findOneBy')->willReturn($returnOrder);
        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('物流信息已提交，无法重复提交');

        $this->procedure->execute();
    }

    public function testExecuteUpdatesAftersalesStageFromReturnToReceive(): void
    {
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->method('getUser')->willReturn($this->mockUser);
        $aftersales->method('getType')->willReturn(AftersalesType::RETURN_REFUND);
        $aftersales->method('getState')->willReturn(AftersalesState::APPROVED);
        $aftersales->method('getStage')->willReturn(AftersalesStage::RETURN);
        $aftersales->method('getId')->willReturn('1');

        $returnOrder = new ReturnOrder();

        $this->procedure->aftersalesId = 1;
        $this->procedure->expressCompany = 'SF';
        $this->procedure->trackingNo = 'SF1234567890';

        $this->aftersalesRepository->method('find')->willReturn($aftersales);
        $this->returnOrderRepository->method('findOneBy')->willReturn(null);
        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);

        // 验证阶段更新
        $aftersales->expects(self::once())->method('setStage')->with(AftersalesStage::RECEIVE);

        $this->procedure->execute();
    }

    private function createValidAftersales(UserInterface $user): Aftersales&MockObject
    {
        $aftersales = $this->createMock(Aftersales::class);
        $aftersales->method('getUser')->willReturn($user);
        $aftersales->method('getType')->willReturn(AftersalesType::RETURN_REFUND);
        $aftersales->method('getState')->willReturn(AftersalesState::APPROVED);
        $aftersales->method('getStage')->willReturn(AftersalesStage::RETURN);
        $aftersales->method('getId')->willReturn('1');

        return $aftersales;
    }
}
