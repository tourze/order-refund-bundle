<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcRequest;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Procedure\Aftersales\SyncAftersalesFromOms;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;

/**
 * @internal
 */
#[CoversClass(SyncAftersalesFromOms::class)]
#[RunTestsInSeparateProcesses]
class SyncAftersalesFromOmsTest extends AbstractProcedureTestCase
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
        $this->procedure->aftersalesNo = 'TEST-AS-' . uniqid();
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '质量问题';
        $this->procedure->description = '商品存在质量缺陷';
        $this->procedure->proofImages = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
        ];
        $this->procedure->status = 'pending';
        $this->procedure->refundAmount = 10000; // 100元
        $this->procedure->applicantName = '张三';
        $this->procedure->applicantPhone = '13800138000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
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
        ];

        $request = new JsonRpcRequest();
        $request->setMethod('syncAftersalesFromOms');
        $result = $this->procedure->__invoke($request);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('售后信息同步成功', $result['message']);
        $this->assertArrayHasKey('aftersalesId', $result);

        // 验证数据是否正确保存
        $aftersales = $this->aftersalesRepository->find($result['aftersalesId']);
        $this->assertNotNull($aftersales);
        $this->assertEquals($this->procedure->aftersalesNo, $aftersales->getReferenceNumber());
        $this->assertEquals(AftersalesType::REFUND_ONLY, $aftersales->getType());
        $this->assertEquals(AftersalesState::PENDING_APPROVAL, $aftersales->getState());
    }

    public function testSyncExchangeAftersalesWithAddress(): void
    {
        $this->procedure->aftersalesNo = 'TEST-EX-' . uniqid();
        $this->procedure->aftersalesType = 'exchange';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '发错货';
        $this->procedure->description = '收到的商品与订单不符';
        $this->procedure->status = 'approved';
        $this->procedure->refundAmount = 0;
        $this->procedure->applicantName = '李四';
        $this->procedure->applicantPhone = '13900139000';
        $this->procedure->applyTime = '2024-01-01 11:00:00';
        $this->procedure->auditor = '客服小王';
        $this->procedure->auditTime = '2024-01-01 12:00:00';
        $this->procedure->auditRemark = '同意换货';
        $this->procedure->products = [
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
        ];
        $this->procedure->exchangeAddress = [
            'name' => '李四',
            'phone' => '13900139000',
            'province' => '上海市',
            'city' => '上海市',
            'district' => '浦东新区',
            'address' => '测试地址123号',
            'zipCode' => '200000',
        ];

        $request = new JsonRpcRequest();
        $request->setMethod('syncAftersalesFromOms');
        $result = $this->procedure->__invoke($request);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('aftersalesId', $result);

        $aftersales = $this->aftersalesRepository->find($result['aftersalesId']);
        $this->assertNotNull($aftersales);
        $this->assertEquals(AftersalesType::EXCHANGE, $aftersales->getType());
        $this->assertEquals(AftersalesState::APPROVED, $aftersales->getState());
    }

    public function testSyncReturnAftersalesWithLogistics(): void
    {
        $this->procedure->aftersalesNo = 'TEST-RT-' . uniqid();
        $this->procedure->aftersalesType = 'return';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '不想要了';
        $this->procedure->status = 'processing';
        $this->procedure->refundAmount = 20000; // 200元
        $this->procedure->applicantName = '王五';
        $this->procedure->applicantPhone = '13700137000';
        $this->procedure->applyTime = '2024-01-01 09:00:00';
        $this->procedure->products = [
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
        ];
        $this->procedure->returnLogistics = [
            'company' => '顺丰快递',
            'trackingNumber' => 'SF' . uniqid(),
            'returnTime' => '2024-01-02 10:00:00',
        ];

        $request = new JsonRpcRequest();
        $request->setMethod('syncAftersalesFromOms');
        $result = $this->procedure->__invoke($request);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);

        $aftersales = $this->aftersalesRepository->find($result['aftersalesId']);
        $this->assertNotNull($aftersales);
        $this->assertEquals(AftersalesType::RETURN_REFUND, $aftersales->getType());
    }

    public function testUpdateExistingAftersales(): void
    {
        // 首先创建一个售后单
        $aftersalesNo = 'TEST-UPDATE-' . uniqid();
        $this->procedure->aftersalesNo = $aftersalesNo;
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '质量问题';
        $this->procedure->status = 'pending';
        $this->procedure->refundAmount = 5000;
        $this->procedure->applicantName = '赵六';
        $this->procedure->applicantPhone = '13600136000';
        $this->procedure->applyTime = '2024-01-01 08:00:00';
        $this->procedure->products = [
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
        ];

        $request = new JsonRpcRequest();
        $request->setMethod('syncAftersalesFromOms');
        $firstResult = $this->procedure->__invoke($request);

        $this->assertIsArray($firstResult);
        $aftersalesId = $firstResult['aftersalesId'];

        // 再次同步，模拟状态更新
        $this->procedure->status = 'approved';
        $this->procedure->auditor = '审核员';
        $this->procedure->auditTime = '2024-01-01 09:00:00';
        $this->procedure->auditRemark = '审核通过';

        $secondResult = $this->procedure->__invoke($request);

        // 应该返回同一个售后单ID
        $this->assertIsArray($secondResult);
        $this->assertEquals($aftersalesId, $secondResult['aftersalesId']);

        $aftersales = $this->aftersalesRepository->find($aftersalesId);
        $this->assertNotNull($aftersales, '售后记录不应为null');
        $this->assertEquals(AftersalesState::APPROVED, $aftersales->getState());
    }

    public function testValidateInvalidAftersalesType(): void
    {
        $this->procedure->aftersalesNo = 'TEST-INVALID-' . uniqid();
        $this->procedure->aftersalesType = 'invalid_type';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '测试';
        $this->procedure->status = 'pending';
        $this->procedure->refundAmount = 1000;
        $this->procedure->applicantName = '测试';
        $this->procedure->applicantPhone = '13500135000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
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
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无效的售后类型');

        $request = new JsonRpcRequest();
        $request->setMethod('syncAftersalesFromOms');
        $this->procedure->__invoke($request);
    }

    public function testValidateEmptyProducts(): void
    {
        $this->procedure->aftersalesNo = 'TEST-EMPTY-' . uniqid();
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '测试';
        $this->procedure->status = 'pending';
        $this->procedure->refundAmount = 1000;
        $this->procedure->applicantName = '测试';
        $this->procedure->applicantPhone = '13500135000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后商品列表不能为空');

        $request = new JsonRpcRequest();
        $request->setMethod('syncAftersalesFromOms');
        $this->procedure->__invoke($request);
    }

    public function testValidateNegativeAmount(): void
    {
        $this->procedure->aftersalesNo = 'TEST-NEG-' . uniqid();
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '测试';
        $this->procedure->status = 'pending';
        $this->procedure->refundAmount = -1000;
        $this->procedure->applicantName = '测试';
        $this->procedure->applicantPhone = '13500135000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
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
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个商品金额不能为负数');

        $request = new JsonRpcRequest();
        $request->setMethod('syncAftersalesFromOms');
        $this->procedure->__invoke($request);
    }

    public function testExchangeRequiresAddress(): void
    {
        $this->procedure->aftersalesNo = 'TEST-EX-NO-ADDR-' . uniqid();
        $this->procedure->aftersalesType = 'exchange';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '换货';
        $this->procedure->status = 'pending';
        $this->procedure->refundAmount = 0;
        $this->procedure->applicantName = '测试';
        $this->procedure->applicantPhone = '13500135000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
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
        ];
        $this->procedure->exchangeAddress = null;

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('换货类型必须提供收货地址');

        $request = new JsonRpcRequest();
        $request->setMethod('syncAftersalesFromOms');
        $this->procedure->__invoke($request);
    }

    public function testExecute(): void
    {
        $this->procedure->aftersalesNo = 'TEST-EXEC-' . uniqid();
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER-' . uniqid();
        $this->procedure->reason = '质量问题';
        $this->procedure->status = 'pending';
        $this->procedure->refundAmount = 1000;
        $this->procedure->applicantName = '测试用户';
        $this->procedure->applicantPhone = '13800138000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
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
        ];

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function testGetMockResult(): void
    {
        $mockResult = SyncAftersalesFromOms::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertTrue($mockResult['success']);
        $this->assertEquals('售后信息同步成功', $mockResult['message']);
        $this->assertEquals('12345678', $mockResult['aftersalesId']);
    }
}
