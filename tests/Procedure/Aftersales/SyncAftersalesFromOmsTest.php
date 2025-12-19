<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Param\Aftersales\SyncAftersalesFromOmsParam;
use Tourze\OrderRefundBundle\Procedure\Aftersales\SyncAftersalesFromOms;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(SyncAftersalesFromOms::class)]
#[RunTestsInSeparateProcesses]
final class SyncAftersalesFromOmsTest extends AbstractProcedureTestCase
{
    private SyncAftersalesFromOms $procedure;

    private AftersalesRepository $aftersalesRepository;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(SyncAftersalesFromOms::class);
        $this->aftersalesRepository = self::getService(AftersalesRepository::class);
    }

    public function testSyncNewAftersalesSuccess(): void
    {
        $aftersalesNo = 'TEST-AS-' . uniqid();
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: $aftersalesNo,
            aftersalesType: 'refund',
            orderNo: 'ORDER-' . uniqid(),
            reason: '质量问题',
            description: '商品存在质量缺陷',
            proofImages: [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
            ],
            status: 'pending',
            refundAmount: 10000, // 100元
            applicantName: '张三',
            applicantPhone: '13800138000',
            applyTime: '2024-01-01 10:00:00',
            products: [
                [
                    'productCode' => 'PROD001',
                    'productName' => '测试商品1',
                    'quantity' => 2,
                    'amount' => 5000,
                    'reason' => '质量问题',
                    'productId' => 'prod-001',
                    'skuId' => 'sku-001',
                    'skuName' => '测试商品1-规格1',
                    'originalPrice' => '100.00',
                    'paidPrice' => '50.00',
                    'discountAmount' => '50.00',
                    'orderQuantity' => 2,
                    'refundQuantity' => 2,
                    'refundAmount' => '50.00',
                    'orderProductId' => 'order-prod-001',
                ],
                [
                    'productCode' => 'PROD002',
                    'productName' => '测试商品2',
                    'quantity' => 1,
                    'amount' => 5000,
                    'productId' => 'prod-002',
                    'skuId' => 'sku-002',
                    'skuName' => '测试商品2-规格1',
                    'originalPrice' => '100.00',
                    'paidPrice' => '50.00',
                    'discountAmount' => '50.00',
                    'orderQuantity' => 1,
                    'refundQuantity' => 1,
                    'refundAmount' => '50.00',
                    'orderProductId' => 'order-prod-002',
                ],
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $this->assertTrue($result['success']);
        $this->assertEquals('售后信息同步成功', $result['message']);
        $this->assertArrayHasKey('aftersalesId', $result->data);

        // 验证数据是否正确保存
        $aftersales = $this->aftersalesRepository->find($result['aftersalesId']);
        $this->assertNotNull($aftersales);
        $this->assertEquals($aftersalesNo, $aftersales->getReferenceNumber());
        $this->assertEquals(AftersalesType::REFUND_ONLY, $aftersales->getType());
        $this->assertEquals(AftersalesState::PENDING_APPROVAL, $aftersales->getState());
    }

    public function testSyncExchangeAftersalesWithAddress(): void
    {
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: 'TEST-EX-' . uniqid(),
            aftersalesType: 'exchange',
            orderNo: 'ORDER-' . uniqid(),
            reason: '发错货',
            description: '收到的商品与订单不符',
            status: 'approved',
            refundAmount: 0,
            applicantName: '李四',
            applicantPhone: '13900139000',
            applyTime: '2024-01-01 11:00:00',
            auditor: '客服小王',
            auditTime: '2024-01-01 12:00:00',
            auditRemark: '同意换货',
            products: [
                [
                    'productCode' => 'PROD003',
                    'productName' => '换货商品',
                    'quantity' => 1,
                    'amount' => 0,
                    'productId' => 'prod-003',
                    'skuId' => 'sku-003',
                    'skuName' => '换货商品-规格1',
                    'originalPrice' => '100.00',
                    'paidPrice' => '100.00',
                    'discountAmount' => '0.00',
                    'orderQuantity' => 1,
                    'refundQuantity' => 1,
                    'refundAmount' => '0.00',
                    'orderProductId' => 'order-prod-003',
                ],
            ],
            exchangeAddress: [
                'name' => '李四',
                'phone' => '13900139000',
                'province' => '上海市',
                'city' => '上海市',
                'district' => '浦东新区',
                'address' => '测试地址123号',
                'zipCode' => '200000',
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('aftersalesId', $result->data);

        $aftersales = $this->aftersalesRepository->find($result['aftersalesId']);
        $this->assertNotNull($aftersales);
        $this->assertEquals(AftersalesType::EXCHANGE, $aftersales->getType());
        $this->assertEquals(AftersalesState::APPROVED, $aftersales->getState());
    }

    public function testSyncReturnAftersalesWithLogistics(): void
    {
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: 'TEST-RT-' . uniqid(),
            aftersalesType: 'return',
            orderNo: 'ORDER-' . uniqid(),
            reason: '不想要了',
            status: 'processing',
            refundAmount: 20000, // 200元
            applicantName: '王五',
            applicantPhone: '13700137000',
            applyTime: '2024-01-01 09:00:00',
            products: [
                [
                    'productCode' => 'PROD004',
                    'productName' => '退货商品',
                    'quantity' => 1,
                    'amount' => 20000,
                    'productId' => 'prod-004',
                    'skuId' => 'sku-004',
                    'skuName' => '退货商品-规格1',
                    'originalPrice' => '200.00',
                    'paidPrice' => '200.00',
                    'discountAmount' => '0.00',
                    'orderQuantity' => 1,
                    'refundQuantity' => 1,
                    'refundAmount' => '200.00',
                    'orderProductId' => 'order-prod-004',
                ],
            ],
            returnLogistics: [
                'company' => '顺丰快递',
                'trackingNumber' => 'SF' . uniqid(),
                'returnTime' => '2024-01-02 10:00:00',
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $this->assertTrue($result['success']);

        $aftersales = $this->aftersalesRepository->find($result['aftersalesId']);
        $this->assertNotNull($aftersales);
        $this->assertEquals(AftersalesType::RETURN_REFUND, $aftersales->getType());
    }

    public function testUpdateExistingAftersales(): void
    {
        // 首先创建一个售后单
        $aftersalesNo = 'TEST-UPDATE-' . uniqid();
        $orderNo = 'ORDER-' . uniqid();
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: $aftersalesNo,
            aftersalesType: 'refund',
            orderNo: $orderNo,
            reason: '质量问题',
            status: 'pending',
            refundAmount: 5000,
            applicantName: '赵六',
            applicantPhone: '13600136000',
            applyTime: '2024-01-01 08:00:00',
            products: [
                [
                    'productCode' => 'PROD005',
                    'productName' => '测试商品',
                    'quantity' => 1,
                    'amount' => 5000,
                    'productId' => 'prod-005',
                    'skuId' => 'sku-005',
                    'skuName' => '测试商品-规格1',
                    'originalPrice' => '50.00',
                    'paidPrice' => '50.00',
                    'discountAmount' => '0.00',
                    'orderQuantity' => 1,
                    'refundQuantity' => 1,
                    'refundAmount' => '50.00',
                    'orderProductId' => 'order-prod-005',
                ],
            ],
        );

        $firstResult = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $firstResult);
        $aftersalesId = $firstResult['aftersalesId'];

        // 再次同步，模拟状态更新
        $paramUpdated = new SyncAftersalesFromOmsParam(
            aftersalesNo: $aftersalesNo,
            aftersalesType: 'refund',
            orderNo: $orderNo,
            reason: '质量问题',
            status: 'approved',
            refundAmount: 5000,
            applicantName: '赵六',
            applicantPhone: '13600136000',
            applyTime: '2024-01-01 08:00:00',
            auditor: '审核员',
            auditTime: '2024-01-01 09:00:00',
            auditRemark: '审核通过',
            products: [
                [
                    'productCode' => 'PROD005',
                    'productName' => '测试商品',
                    'quantity' => 1,
                    'amount' => 5000,
                    'productId' => 'prod-005',
                    'skuId' => 'sku-005',
                    'skuName' => '测试商品-规格1',
                    'originalPrice' => '50.00',
                    'paidPrice' => '50.00',
                    'discountAmount' => '0.00',
                    'orderQuantity' => 1,
                    'refundQuantity' => 1,
                    'refundAmount' => '50.00',
                    'orderProductId' => 'order-prod-005',
                ],
            ],
        );

        $secondResult = $this->procedure->execute($paramUpdated);

        // 应该返回同一个售后单ID
        $this->assertInstanceOf(ArrayResult::class, $secondResult);
        $this->assertEquals($aftersalesId, $secondResult['aftersalesId']);

        $aftersales = $this->aftersalesRepository->find($aftersalesId);
        $this->assertNotNull($aftersales, '售后记录不应为null');
        $this->assertEquals(AftersalesState::APPROVED, $aftersales->getState());
    }

    public function testValidateInvalidAftersalesType(): void
    {
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: 'TEST-INVALID-' . uniqid(),
            aftersalesType: 'invalid_type',
            orderNo: 'ORDER-' . uniqid(),
            reason: '测试',
            status: 'pending',
            refundAmount: 1000,
            applicantName: '测试',
            applicantPhone: '13500135000',
            applyTime: '2024-01-01 10:00:00',
            products: [
                [
                    'productCode' => 'PROD001',
                    'productName' => '商品',
                    'quantity' => 1,
                    'amount' => 1000,
                    'productId' => 'prod-001',
                    'skuId' => 'sku-001',
                    'skuName' => '商品-规格1',
                    'originalPrice' => '10.00',
                    'paidPrice' => '10.00',
                    'refundAmount' => '10.00',
                    'orderProductId' => 'order-prod-001',
                ],
            ],
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无效的售后类型');

        $this->procedure->execute($param);
    }

    public function testValidateEmptyProducts(): void
    {
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: 'TEST-EMPTY-' . uniqid(),
            aftersalesType: 'refund',
            orderNo: 'ORDER-' . uniqid(),
            reason: '测试',
            status: 'pending',
            refundAmount: 1000,
            applicantName: '测试',
            applicantPhone: '13500135000',
            applyTime: '2024-01-01 10:00:00',
            products: [],
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后商品列表不能为空');

        $this->procedure->execute($param);
    }

    public function testValidateNegativeAmount(): void
    {
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: 'TEST-NEG-' . uniqid(),
            aftersalesType: 'refund',
            orderNo: 'ORDER-' . uniqid(),
            reason: '测试',
            status: 'pending',
            refundAmount: -1000,
            applicantName: '测试',
            applicantPhone: '13500135000',
            applyTime: '2024-01-01 10:00:00',
            products: [
                [
                    'productCode' => 'PROD001',
                    'productName' => '商品',
                    'quantity' => 1,
                    'amount' => -1000,
                    'productId' => 'prod-001',
                    'skuId' => 'sku-001',
                    'skuName' => '商品-规格1',
                    'originalPrice' => '10.00',
                    'paidPrice' => '10.00',
                    'refundAmount' => '10.00',
                    'orderProductId' => 'order-prod-001',
                ],
            ],
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个商品金额不能为负数');

        $this->procedure->execute($param);
    }

    public function testExchangeRequiresAddress(): void
    {
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: 'TEST-EX-NO-ADDR-' . uniqid(),
            aftersalesType: 'exchange',
            orderNo: 'ORDER-' . uniqid(),
            reason: '换货',
            status: 'pending',
            refundAmount: 0,
            applicantName: '测试',
            applicantPhone: '13500135000',
            applyTime: '2024-01-01 10:00:00',
            products: [
                [
                    'productCode' => 'PROD001',
                    'productName' => '商品',
                    'quantity' => 1,
                    'amount' => 0,
                    'productId' => 'prod-001',
                    'skuId' => 'sku-001',
                    'skuName' => '商品-规格1',
                    'originalPrice' => '10.00',
                    'paidPrice' => '10.00',
                    'refundAmount' => '0.00',
                    'orderProductId' => 'order-prod-001',
                ],
            ],
            exchangeAddress: null,
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('换货类型必须提供收货地址');

        $this->procedure->execute($param);
    }

    public function testExecute(): void
    {
        $param = new SyncAftersalesFromOmsParam(
            aftersalesNo: 'TEST-EXEC-' . uniqid(),
            aftersalesType: 'refund',
            orderNo: 'ORDER-' . uniqid(),
            reason: '质量问题',
            status: 'pending',
            refundAmount: 1000,
            applicantName: '测试用户',
            applicantPhone: '13800138000',
            applyTime: '2024-01-01 10:00:00',
            products: [
                [
                    'productCode' => 'PROD-TEST',
                    'productName' => '测试商品',
                    'quantity' => 1,
                    'amount' => 1000,
                    'productId' => 'prod-test',
                    'skuId' => 'sku-test',
                    'skuName' => '测试商品-规格',
                    'originalPrice' => '10.00',
                    'paidPrice' => '10.00',
                    'discountAmount' => '0.00',
                    'orderQuantity' => 1,
                    'refundQuantity' => 1,
                    'refundAmount' => '10.00',
                    'orderProductId' => 'order-prod-test',
                ],
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $this->assertTrue($result['success']);
    }
}
