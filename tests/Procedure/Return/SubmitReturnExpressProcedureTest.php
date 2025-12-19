<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Return;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Param\Return\SubmitReturnExpressParam;
use Tourze\OrderRefundBundle\Procedure\Return\SubmitReturnExpressProcedure;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;
use Tourze\OrderRefundBundle\Repository\ReturnOrderRepository;
use Tourze\OrderRefundBundle\Service\ExpressTrackingService;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(SubmitReturnExpressProcedure::class)]
#[RunTestsInSeparateProcesses]
final class SubmitReturnExpressProcedureTest extends AbstractProcedureTestCase
{
    private SubmitReturnExpressProcedure $procedure;

    private AftersalesRepository $aftersalesRepository;

    private ReturnOrderRepository $returnOrderRepository;

    private ExpressCompanyRepository $expressCompanyRepository;

    private ExpressTrackingService&MockObject $expressTrackingService;

    private UserInterface $testUser;

    private Aftersales $testAftersales;

    private ExpressCompany $sfExpressCompany;

    private ExpressCompany $ytoExpressCompany;

    protected function onSetUp(): void
    {
        // 获取真实的 Repository 服务
        $this->aftersalesRepository = self::getService(AftersalesRepository::class);
        $this->returnOrderRepository = self::getService(ReturnOrderRepository::class);
        $this->expressCompanyRepository = self::getService(ExpressCompanyRepository::class);

        // 仅 Mock 网络请求相关的服务
        $this->expressTrackingService = $this->createMock(ExpressTrackingService::class);
        self::getContainer()->set(ExpressTrackingService::class, $this->expressTrackingService);

        // 创建测试用户
        $this->testUser = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($this->testUser);

        // 创建测试数据
        $this->createTestData();

        // 获取 Procedure 服务
        $this->procedure = self::getService(SubmitReturnExpressProcedure::class);
    }

    private function createTestData(): void
    {
        // 查找或创建快递公司数据
        $this->sfExpressCompany = $this->expressCompanyRepository->findByCode('SF');
        if (null === $this->sfExpressCompany) {
            $this->sfExpressCompany = new ExpressCompany();
            $this->sfExpressCompany->setCode('SF');
            $this->sfExpressCompany->setName('顺丰速运');
            $this->sfExpressCompany->setTrackingUrlTemplate('https://www.sf-express.com/track/%s');
            $this->sfExpressCompany->setIsActive(true);
            $this->sfExpressCompany->setSortOrder(1);
            $this->expressCompanyRepository->save($this->sfExpressCompany, true);
        }

        $this->ytoExpressCompany = $this->expressCompanyRepository->findByCode('YTO');
        if (null === $this->ytoExpressCompany) {
            $this->ytoExpressCompany = new ExpressCompany();
            $this->ytoExpressCompany->setCode('YTO');
            $this->ytoExpressCompany->setName('圆通速递');
            $this->ytoExpressCompany->setTrackingUrlTemplate('https://www.yto.net.cn/track/%s');
            $this->ytoExpressCompany->setIsActive(true);
            $this->ytoExpressCompany->setSortOrder(2);
            $this->expressCompanyRepository->save($this->ytoExpressCompany, true);
        }

        // 创建售后单数据
        $this->testAftersales = new Aftersales();
        $this->testAftersales->setUser($this->testUser);
        $this->testAftersales->setType(AftersalesType::RETURN_REFUND);
        $this->testAftersales->setState(AftersalesState::APPROVED);
        $this->testAftersales->setStage(AftersalesStage::RETURN);
        $this->testAftersales->setReason(RefundReason::QUALITY_ISSUE);
        $this->testAftersales->setReferenceNumber('ORDER-TEST-001');
        $this->testAftersales->setOrderProductId('OP-001');
        $this->testAftersales->setProductId('PROD-001');
        $this->testAftersales->setSkuId('SKU-001');
        $this->testAftersales->setProductName('测试商品');
        $this->testAftersales->setSkuName('测试SKU');
        $this->testAftersales->setQuantity(1);
        $this->testAftersales->setOriginalPrice('100.00');
        $this->testAftersales->setPaidPrice('90.00');
        $this->testAftersales->setRefundAmount('90.00');
        $this->testAftersales->setOriginalRefundAmount('90.00');
        $this->testAftersales->setActualRefundAmount('90.00');
        $this->aftersalesRepository->save($this->testAftersales, true);
    }

    public function testExecuteSuccessWithNewReturnOrder(): void
    {
        // 创建参数对象
        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $this->testAftersales->getId(),
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
            remark: '退货备注',
        );

        // Mock 快递服务方法
        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);
        $this->expressTrackingService->method('generateTrackingUrlForReturn')
            ->willReturn('https://www.sf-express.com/track/SF1234567890')
        ;

        // 执行测试
        $result = $this->procedure->execute($param);

        // 验证结果
        $resultArray = $result->toArray();
        self::assertIsArray($resultArray);
        self::assertTrue($resultArray['success']);
        self::assertSame('物流信息提交成功', $resultArray['message']);
        self::assertArrayHasKey('data', $resultArray);

        $data = $resultArray['data'];
        self::assertIsArray($data);
        self::assertSame('SF', $data['expressCompany']);
        self::assertSame('SF1234567890', $data['trackingNo']);
        self::assertIsString($data['shipTime']);

        // 验证数据库状态
        $savedReturnOrder = $this->returnOrderRepository->findOneBy(['aftersales' => $this->testAftersales]);
        self::assertNotNull($savedReturnOrder);
        self::assertSame('SF', $savedReturnOrder->getExpressCompany());
        self::assertSame('SF1234567890', $savedReturnOrder->getTrackingNo());
        self::assertTrue($savedReturnOrder->isShipped());

        // 验证售后单阶段已更新
        $updatedAftersales = $this->aftersalesRepository->find($this->testAftersales->getId());
        self::assertNotNull($updatedAftersales);
        self::assertSame(AftersalesStage::RECEIVE, $updatedAftersales->getStage());
    }

    public function testExecuteSuccessWithExistingReturnOrder(): void
    {
        // 先创建一个退货单
        $existingReturnOrder = new ReturnOrder();
        $existingReturnOrder->setAftersales($this->testAftersales);
        $this->returnOrderRepository->save($existingReturnOrder, true);

        // 创建参数对象
        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $this->testAftersales->getId(),
            expressCompany: 'YTO',
            trackingNo: 'YTO1234567890',
        );

        // Mock 快递服务方法
        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);
        $this->expressTrackingService->method('generateTrackingUrlForReturn')
            ->willReturn('https://www.yto.net.cn/track/YTO1234567890')
        ;

        // 执行测试
        $result = $this->procedure->execute($param);

        // 验证结果
        $resultArray = $result->toArray();
        self::assertIsArray($resultArray);
        self::assertTrue($resultArray['success']);
        self::assertArrayHasKey('data', $resultArray);

        $data = $resultArray['data'];
        self::assertIsArray($data);
        self::assertSame('YTO', $data['expressCompany']);
        self::assertSame('YTO1234567890', $data['trackingNo']);

        // 验证数据库中使用了同一个 ReturnOrder
        $savedReturnOrder = $this->returnOrderRepository->findOneBy(['aftersales' => $this->testAftersales]);
        self::assertNotNull($savedReturnOrder);
        self::assertSame($existingReturnOrder->getId(), $savedReturnOrder->getId());
    }

    public function testExecuteThrowsExceptionWhenUserNotLoggedIn(): void
    {
        // 测试用户未登录的场景
        self::markTestSkipped('Cannot test unauthenticated state with current testing framework');
    }

    public function testExecuteThrowsExceptionWhenAftersalesIdEmpty(): void
    {
        $param = new SubmitReturnExpressParam(
            aftersalesId: '',
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单ID不能为空');

        $this->procedure->execute($param);
    }

    public function testExecuteThrowsExceptionWhenAftersalesNotFound(): void
    {
        $param = new SubmitReturnExpressParam(
            aftersalesId: '999999999',
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单不存在');

        $this->procedure->execute($param);
    }

    public function testExecuteThrowsExceptionWhenUserNotAuthorized(): void
    {
        // 创建另一个用户的售后单
        $otherUser = $this->createNormalUser('other@example.com', 'password456');
        $otherAftersales = new Aftersales();
        $otherAftersales->setUser($otherUser);
        $otherAftersales->setType(AftersalesType::RETURN_REFUND);
        $otherAftersales->setState(AftersalesState::APPROVED);
        $otherAftersales->setStage(AftersalesStage::RETURN);
        $otherAftersales->setReason(RefundReason::QUALITY_ISSUE);
        $otherAftersales->setReferenceNumber('ORDER-OTHER-001');
        $otherAftersales->setOrderProductId('OP-002');
        $otherAftersales->setProductId('PROD-002');
        $otherAftersales->setSkuId('SKU-002');
        $otherAftersales->setProductName('其他商品');
        $otherAftersales->setSkuName('其他SKU');
        $otherAftersales->setQuantity(1);
        $otherAftersales->setOriginalPrice('100.00');
        $otherAftersales->setPaidPrice('90.00');
        $otherAftersales->setRefundAmount('90.00');
        $otherAftersales->setOriginalRefundAmount('90.00');
        $otherAftersales->setActualRefundAmount('90.00');
        $this->aftersalesRepository->save($otherAftersales, true);

        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $otherAftersales->getId(),
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无权限操作此售后单');

        $this->procedure->execute($param);
    }

    public function testExecuteThrowsExceptionWhenWrongAftersalesType(): void
    {
        // 创建仅退款类型的售后单
        $refundOnlyAftersales = new Aftersales();
        $refundOnlyAftersales->setUser($this->testUser);
        $refundOnlyAftersales->setType(AftersalesType::REFUND_ONLY);
        $refundOnlyAftersales->setState(AftersalesState::APPROVED);
        $refundOnlyAftersales->setStage(AftersalesStage::APPLY);
        $refundOnlyAftersales->setReason(RefundReason::QUALITY_ISSUE);
        $refundOnlyAftersales->setReferenceNumber('ORDER-REFUND-001');
        $refundOnlyAftersales->setOrderProductId('OP-003');
        $refundOnlyAftersales->setProductId('PROD-003');
        $refundOnlyAftersales->setSkuId('SKU-003');
        $refundOnlyAftersales->setProductName('仅退款商品');
        $refundOnlyAftersales->setSkuName('仅退款SKU');
        $refundOnlyAftersales->setQuantity(1);
        $refundOnlyAftersales->setOriginalPrice('100.00');
        $refundOnlyAftersales->setPaidPrice('90.00');
        $refundOnlyAftersales->setRefundAmount('90.00');
        $refundOnlyAftersales->setOriginalRefundAmount('90.00');
        $refundOnlyAftersales->setActualRefundAmount('90.00');
        $this->aftersalesRepository->save($refundOnlyAftersales, true);

        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $refundOnlyAftersales->getId(),
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('该售后单不需要退货');

        $this->procedure->execute($param);
    }

    public function testExecuteThrowsExceptionWhenAftersalesNotApproved(): void
    {
        // 创建待审核状态的售后单
        $pendingAftersales = new Aftersales();
        $pendingAftersales->setUser($this->testUser);
        $pendingAftersales->setType(AftersalesType::RETURN_REFUND);
        $pendingAftersales->setState(AftersalesState::PENDING_APPROVAL);
        $pendingAftersales->setStage(AftersalesStage::APPLY);
        $pendingAftersales->setReason(RefundReason::QUALITY_ISSUE);
        $pendingAftersales->setReferenceNumber('ORDER-PENDING-001');
        $pendingAftersales->setOrderProductId('OP-004');
        $pendingAftersales->setProductId('PROD-004');
        $pendingAftersales->setSkuId('SKU-004');
        $pendingAftersales->setProductName('待审核商品');
        $pendingAftersales->setSkuName('待审核SKU');
        $pendingAftersales->setQuantity(1);
        $pendingAftersales->setOriginalPrice('100.00');
        $pendingAftersales->setPaidPrice('90.00');
        $pendingAftersales->setRefundAmount('90.00');
        $pendingAftersales->setOriginalRefundAmount('90.00');
        $pendingAftersales->setActualRefundAmount('90.00');
        $this->aftersalesRepository->save($pendingAftersales, true);

        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $pendingAftersales->getId(),
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单未审核通过，无法提交物流信息');

        $this->procedure->execute($param);
    }

    public function testExecuteThrowsExceptionWhenWrongStage(): void
    {
        // 创建申请阶段的售后单
        $applyStageAftersales = new Aftersales();
        $applyStageAftersales->setUser($this->testUser);
        $applyStageAftersales->setType(AftersalesType::RETURN_REFUND);
        $applyStageAftersales->setState(AftersalesState::APPROVED);
        $applyStageAftersales->setStage(AftersalesStage::APPLY);
        $applyStageAftersales->setReason(RefundReason::QUALITY_ISSUE);
        $applyStageAftersales->setReferenceNumber('ORDER-APPLY-001');
        $applyStageAftersales->setOrderProductId('OP-005');
        $applyStageAftersales->setProductId('PROD-005');
        $applyStageAftersales->setSkuId('SKU-005');
        $applyStageAftersales->setProductName('申请阶段商品');
        $applyStageAftersales->setSkuName('申请阶段SKU');
        $applyStageAftersales->setQuantity(1);
        $applyStageAftersales->setOriginalPrice('100.00');
        $applyStageAftersales->setPaidPrice('90.00');
        $applyStageAftersales->setRefundAmount('90.00');
        $applyStageAftersales->setOriginalRefundAmount('90.00');
        $applyStageAftersales->setActualRefundAmount('90.00');
        $this->aftersalesRepository->save($applyStageAftersales, true);

        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $applyStageAftersales->getId(),
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('当前阶段无法提交物流信息');

        $this->procedure->execute($param);
    }

    #[DataProvider('invalidExpressInfoProvider')]
    public function testExecuteThrowsExceptionWithInvalidExpressInfo(
        string $expressCompany,
        string $trackingNo,
        string $expectedMessage,
    ): void {
        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $this->testAftersales->getId(),
            expressCompany: $expressCompany,
            trackingNo: $trackingNo,
        );

        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->procedure->execute($param);
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
        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $this->testAftersales->getId(),
            expressCompany: 'INVALID',
            trackingNo: 'INV1234567890',
        );

        $this->expressTrackingService->method('validateExpressCompany')->willReturn(false);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('不支持的快递公司或快递公司已停用');

        $this->procedure->execute($param);
    }

    public function testExecuteThrowsExceptionWhenAlreadyShipped(): void
    {
        // 先创建一个已发货的退货单
        $shippedReturnOrder = new ReturnOrder();
        $shippedReturnOrder->setAftersales($this->testAftersales);
        $shippedReturnOrder->markAsShipped('SF', 'SF0000000000');
        $this->returnOrderRepository->save($shippedReturnOrder, true);

        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $this->testAftersales->getId(),
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
        );

        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('物流信息已提交，无法重复提交');

        $this->procedure->execute($param);
    }

    public function testExecuteUpdatesAftersalesStageFromReturnToReceive(): void
    {
        // 创建退货阶段的售后单
        $returnStageAftersales = new Aftersales();
        $returnStageAftersales->setUser($this->testUser);
        $returnStageAftersales->setType(AftersalesType::RETURN_REFUND);
        $returnStageAftersales->setState(AftersalesState::APPROVED);
        $returnStageAftersales->setStage(AftersalesStage::RETURN);
        $returnStageAftersales->setReason(RefundReason::QUALITY_ISSUE);
        $returnStageAftersales->setReferenceNumber('ORDER-STAGE-001');
        $returnStageAftersales->setOrderProductId('OP-006');
        $returnStageAftersales->setProductId('PROD-006');
        $returnStageAftersales->setSkuId('SKU-006');
        $returnStageAftersales->setProductName('阶段测试商品');
        $returnStageAftersales->setSkuName('阶段测试SKU');
        $returnStageAftersales->setQuantity(1);
        $returnStageAftersales->setOriginalPrice('100.00');
        $returnStageAftersales->setPaidPrice('90.00');
        $returnStageAftersales->setRefundAmount('90.00');
        $returnStageAftersales->setOriginalRefundAmount('90.00');
        $returnStageAftersales->setActualRefundAmount('90.00');
        $this->aftersalesRepository->save($returnStageAftersales, true);

        $param = new SubmitReturnExpressParam(
            aftersalesId: (string) $returnStageAftersales->getId(),
            expressCompany: 'SF',
            trackingNo: 'SF1234567890',
        );

        $this->expressTrackingService->method('validateExpressCompany')->willReturn(true);
        $this->expressTrackingService->method('generateTrackingUrlForReturn')
            ->willReturn('https://www.sf-express.com/track/SF1234567890')
        ;

        $this->procedure->execute($param);

        // 验证阶段已更新
        $updatedAftersales = $this->aftersalesRepository->find($returnStageAftersales->getId());
        self::assertNotNull($updatedAftersales);
        self::assertSame(AftersalesStage::RECEIVE, $updatedAftersales->getStage());
    }
}
