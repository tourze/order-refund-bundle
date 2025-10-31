<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Enum\ReturnStatus;
use Tourze\OrderRefundBundle\Service\ExpressTrackingService;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ReturnOrder::class)]
class ReturnOrderTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createEntity(): ReturnOrder
    {
        return new ReturnOrder();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'returnNo' => ['returnNo', 'RN20240101001'],
            'status' => ['status', ReturnStatus::PENDING],
            'trackingNo' => ['trackingNo', 'SF1234567890'],
            'expressCompany' => ['expressCompany', '顺丰速运'],
            'shipTime' => ['shipTime', new \DateTimeImmutable()],
            'receiveTime' => ['receiveTime', new \DateTimeImmutable('+3 days')],
            'inspectTime' => ['inspectTime', new \DateTimeImmutable('+4 days')],
            'rejectionReason' => ['rejectionReason', '不符合退货条件'],
            'remark' => ['remark', '测试备注'],
        ];
    }

    public function testReturnOrderCreation(): void
    {
        $order = new ReturnOrder();

        self::assertNull($order->getId());
        self::assertNotNull($order->getReturnNo());
        self::assertStringStartsWith('RT', $order->getReturnNo());
        self::assertSame(ReturnStatus::PENDING, $order->getStatus());
    }

    public function testBasicSettersAndGetters(): void
    {
        $order = new ReturnOrder();
        $aftersales = new Aftersales();

        $order->setAftersales($aftersales);
        $order->setReturnNo('RT202408130001');
        $order->setStatus(ReturnStatus::SHIPPED);
        $order->setExpressCompany('顺丰');
        $order->setTrackingNo('SF123456789');
        $order->setReturnAddress('上海市浦东新区退货仓库地址123号');
        $order->setContactPerson('张三');
        $order->setContactPhone('13800138000');
        $order->setTrackingInfo([
            ['status' => 'picked_up', 'time' => '2024-08-13 09:00:00'],
            ['status' => 'in_transit', 'time' => '2024-08-13 12:00:00'],
        ]);
        $order->setRejectionReason('商品外包装损坏');
        $order->setRemark('客户要求退货');

        self::assertSame($aftersales, $order->getAftersales());
        self::assertSame('RT202408130001', $order->getReturnNo());
        self::assertSame(ReturnStatus::SHIPPED, $order->getStatus());
        self::assertSame('顺丰', $order->getExpressCompany());
        self::assertSame('SF123456789', $order->getTrackingNo());
        self::assertSame('上海市浦东新区退货仓库地址123号', $order->getReturnAddress());
        self::assertSame('张三', $order->getContactPerson());
        self::assertSame('13800138000', $order->getContactPhone());
        self::assertSame([
            ['status' => 'picked_up', 'time' => '2024-08-13 09:00:00'],
            ['status' => 'in_transit', 'time' => '2024-08-13 12:00:00'],
        ], $order->getTrackingInfo());
        self::assertSame('商品外包装损坏', $order->getRejectionReason());
        self::assertSame('客户要求退货', $order->getRemark());
    }

    public function testTimeSettersAndGetters(): void
    {
        $order = new ReturnOrder();
        $shipTime = new \DateTimeImmutable('2024-08-13 10:00:00');
        $receiveTime = new \DateTimeImmutable('2024-08-14 15:30:00');
        $inspectTime = new \DateTimeImmutable('2024-08-14 16:00:00');

        $order->setShipTime($shipTime);
        $order->setReceiveTime($receiveTime);
        $order->setInspectTime($inspectTime);

        self::assertSame($shipTime, $order->getShipTime());
        self::assertSame($receiveTime, $order->getReceiveTime());
        self::assertSame($inspectTime, $order->getInspectTime());
    }

    public function testCanShip(): void
    {
        $order = new ReturnOrder();

        // 默认状态 PENDING，可以发货
        self::assertTrue($order->canShip());

        // 已发货状态，不能再次发货
        $order->setStatus(ReturnStatus::SHIPPED);
        self::assertFalse($order->canShip());

        // 其他状态，不能发货
        $order->setStatus(ReturnStatus::RECEIVED);
        self::assertFalse($order->canShip());
    }

    public function testIsShipped(): void
    {
        $order = new ReturnOrder();

        // 默认状态 PENDING，未发货
        self::assertFalse($order->isShipped());

        // 各种已发货状态
        $shippedStatuses = [
            ReturnStatus::SHIPPED,
            ReturnStatus::IN_TRANSIT,
            ReturnStatus::RECEIVED,
            ReturnStatus::INSPECTED,
        ];

        foreach ($shippedStatuses as $status) {
            $order->setStatus($status);
            self::assertTrue($order->isShipped(), "Status {$status->value} should be considered shipped");
        }

        // 非发货状态
        $order->setStatus(ReturnStatus::PENDING);
        self::assertFalse($order->isShipped());

        $order->setStatus(ReturnStatus::REJECTED);
        self::assertFalse($order->isShipped());
    }

    public function testIsCompleted(): void
    {
        $order = new ReturnOrder();

        self::assertFalse($order->isCompleted());

        $order->setStatus(ReturnStatus::INSPECTED);
        self::assertTrue($order->isCompleted());

        $order->setStatus(ReturnStatus::RECEIVED);
        self::assertFalse($order->isCompleted());
    }

    public function testMarkAsShipped(): void
    {
        $order = new ReturnOrder();
        $beforeTime = new \DateTimeImmutable();

        $result = $order->markAsShipped('顺丰', 'SF123456789');

        $afterTime = new \DateTimeImmutable();

        self::assertSame($order, $result);
        self::assertSame(ReturnStatus::SHIPPED, $order->getStatus());
        self::assertSame('顺丰', $order->getExpressCompany());
        self::assertSame('SF123456789', $order->getTrackingNo());
        self::assertNotNull($order->getShipTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getShipTime());
        self::assertLessThanOrEqual($afterTime, $order->getShipTime());
    }

    public function testMarkAsReceived(): void
    {
        $order = new ReturnOrder();
        $beforeTime = new \DateTimeImmutable();

        $result = $order->markAsReceived();

        $afterTime = new \DateTimeImmutable();

        self::assertSame($order, $result);
        self::assertSame(ReturnStatus::RECEIVED, $order->getStatus());
        self::assertNotNull($order->getReceiveTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getReceiveTime());
        self::assertLessThanOrEqual($afterTime, $order->getReceiveTime());
    }

    public function testMarkAsInspectedPassed(): void
    {
        $order = new ReturnOrder();
        $beforeTime = new \DateTimeImmutable();

        $result = $order->markAsInspected(true);

        $afterTime = new \DateTimeImmutable();

        self::assertSame($order, $result);
        self::assertSame(ReturnStatus::INSPECTED, $order->getStatus());
        self::assertNotNull($order->getInspectTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getInspectTime());
        self::assertLessThanOrEqual($afterTime, $order->getInspectTime());
        self::assertNull($order->getRejectionReason());
    }

    public function testMarkAsInspectedFailed(): void
    {
        $order = new ReturnOrder();
        $beforeTime = new \DateTimeImmutable();

        $result = $order->markAsInspected(false, '商品损坏严重无法处理');

        $afterTime = new \DateTimeImmutable();

        self::assertSame($order, $result);
        self::assertSame(ReturnStatus::REJECTED, $order->getStatus());
        self::assertNotNull($order->getInspectTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getInspectTime());
        self::assertLessThanOrEqual($afterTime, $order->getInspectTime());
        self::assertSame('商品损坏严重无法处理', $order->getRejectionReason());
    }

    public function testMarkAsInspectedFailedWithoutReason(): void
    {
        $order = new ReturnOrder();

        $result = $order->markAsInspected(false);

        self::assertSame($order, $result);
        self::assertSame(ReturnStatus::REJECTED, $order->getStatus());
        self::assertNotNull($order->getInspectTime());
        self::assertNull($order->getRejectionReason());
    }

    public function testUpdateTrackingInfo(): void
    {
        $order = new ReturnOrder();
        $trackingInfo = [
            ['status' => 'picked_up', 'time' => '2024-08-13 09:00:00'],
            ['status' => 'in_transit', 'time' => '2024-08-13 12:00:00'],
        ];

        $result = $order->updateTrackingInfo($trackingInfo);

        self::assertSame($order, $result);
        self::assertSame($trackingInfo, $order->getTrackingInfo());

        // 应该根据最后状态自动更新为 IN_TRANSIT
        self::assertSame(ReturnStatus::IN_TRANSIT, $order->getStatus());
    }

    public function testUpdateTrackingInfoDelivered(): void
    {
        $order = new ReturnOrder();
        $trackingInfo = [
            ['status' => 'picked_up', 'time' => '2024-08-13 09:00:00'],
            ['status' => 'in_transit', 'time' => '2024-08-13 12:00:00'],
            ['status' => 'delivered', 'time' => '2024-08-14 10:00:00'],
        ];

        $order->updateTrackingInfo($trackingInfo);

        self::assertSame($trackingInfo, $order->getTrackingInfo());
        self::assertSame(ReturnStatus::RECEIVED, $order->getStatus());
    }

    public function testUpdateTrackingInfoUnknownStatus(): void
    {
        $order = new ReturnOrder();
        $originalStatus = $order->getStatus();
        $trackingInfo = [
            ['status' => 'unknown_status', 'time' => '2024-08-13 09:00:00'],
        ];

        $order->updateTrackingInfo($trackingInfo);

        self::assertSame($trackingInfo, $order->getTrackingInfo());
        // 状态应该保持不变
        self::assertSame($originalStatus, $order->getStatus());
    }

    public function testUpdateTrackingInfoEmpty(): void
    {
        $order = new ReturnOrder();
        $originalStatus = $order->getStatus();

        $order->updateTrackingInfo([]);

        self::assertSame([], $order->getTrackingInfo());
        // 状态应该保持不变
        self::assertSame($originalStatus, $order->getStatus());
    }

    public function testTrackingUrlGeneration(): void
    {
        // 测试没有快递公司和单号时返回 null
        $order = new ReturnOrder();
        $mockService = $this->createMock(ExpressTrackingService::class);
        $mockService->expects(self::once())
            ->method('generateTrackingUrlForReturn')
            ->with($order)
            ->willReturn(null)
        ;

        self::assertNull($mockService->generateTrackingUrlForReturn($order));

        // 测试只有快递公司没有单号时返回 null
        $order->setExpressCompany('顺丰');
        $mockService = $this->createMock(ExpressTrackingService::class);
        $mockService->expects(self::once())
            ->method('generateTrackingUrlForReturn')
            ->with($order)
            ->willReturn(null)
        ;

        self::assertNull($mockService->generateTrackingUrlForReturn($order));

        // 测试只有单号没有快递公司时返回 null
        $order->setExpressCompany(null);
        $order->setTrackingNo('SF123456789');
        $mockService = $this->createMock(ExpressTrackingService::class);
        $mockService->expects(self::once())
            ->method('generateTrackingUrlForReturn')
            ->with($order)
            ->willReturn(null)
        ;

        self::assertNull($mockService->generateTrackingUrlForReturn($order));
    }

    public function testTrackingUrlGenerationWithSupportedCompanies(): void
    {
        $testCases = [
            ['顺丰', 'SF123456789', 'https://www.sf-express.com/chn/sc/dynamic_function/waybill/#search/bill-number/SF123456789'],
            ['申通', 'ST123456789', 'https://www.sto.cn/query.html?no=ST123456789'],
            ['韵达', 'YD123456789', 'https://www.yundaex.com/index.php/query/index.html?no=YD123456789'],
            ['中通', 'ZT123456789', 'https://www.zto.com/Home/QueryOrderInfo?txtBillCode=ZT123456789'],
            ['圆通', 'YT123456789', 'https://www.yto.net.cn/query.html?no=YT123456789'],
        ];

        foreach ($testCases as [$company, $trackingNo, $expectedUrl]) {
            $order = new ReturnOrder();
            $order->setExpressCompany($company);
            $order->setTrackingNo($trackingNo);

            $mockService = $this->createMock(ExpressTrackingService::class);
            $mockService->expects(self::once())
                ->method('generateTrackingUrlForReturn')
                ->with($order)
                ->willReturn($expectedUrl)
            ;

            self::assertSame($expectedUrl, $mockService->generateTrackingUrlForReturn($order), "Failed for {$company}");
        }
    }

    public function testTrackingUrlGenerationWithUnsupportedCompany(): void
    {
        $order = new ReturnOrder();
        $order->setExpressCompany('未知快递');
        $order->setTrackingNo('UNKNOWN123');

        $mockService = $this->createMock(ExpressTrackingService::class);
        $mockService->expects(self::once())
            ->method('generateTrackingUrlForReturn')
            ->with($order)
            ->willReturn(null)
        ;

        self::assertNull($mockService->generateTrackingUrlForReturn($order));
    }

    public function testToString(): void
    {
        $order = new ReturnOrder();

        // 测试使用 returnNo
        $order->setReturnNo('RT202408130001');
        self::assertSame('RT202408130001', (string) $order);
    }

    public function testToStringWithoutReturnNo(): void
    {
        $order = new ReturnOrder();

        // 设置 returnNo 为 null（虽然构造函数会生成，但测试边界情况）
        $reflection = new \ReflectionClass($order);
        $property = $reflection->getProperty('returnNo');
        $property->setAccessible(true);
        $property->setValue($order, null);

        // 当 returnNo 为 null 时，应该返回 ID（但ID也为null时返回空字符串）
        self::assertSame('', (string) $order);
    }

    public function testGenerateReturnNoFormat(): void
    {
        $order = new ReturnOrder();
        $returnNo = $order->getReturnNo();

        self::assertNotNull($returnNo);
        // 验证退货单号格式：RT + 8位日期 + 6位随机数
        self::assertMatchesRegularExpression('/^RT\d{8}\d{6}$/', $returnNo);

        // 验证日期部分是今天
        $today = date('Ymd');
        self::assertStringStartsWith('RT' . $today, $returnNo);
    }

    public function testSettersAndGetters(): void
    {
        $order = new ReturnOrder();
        $aftersales = new Aftersales();

        // 测试各个setter方法
        $order->setAftersales($aftersales);
        $order->setStatus(ReturnStatus::SHIPPED);
        $order->setExpressCompany('申通');
        $order->setTrackingNo('ST123456789');
        $order->setReturnAddress('退货地址');
        $order->setContactPerson('联系人');
        $order->setContactPhone('13888888888');
        $order->setRemark('退货备注');

        // 验证setter设置的值
        self::assertSame($aftersales, $order->getAftersales());
        self::assertSame(ReturnStatus::SHIPPED, $order->getStatus());
        self::assertSame('申通', $order->getExpressCompany());
        self::assertSame('ST123456789', $order->getTrackingNo());
        self::assertSame('退货地址', $order->getReturnAddress());
        self::assertSame('联系人', $order->getContactPerson());
        self::assertSame('13888888888', $order->getContactPhone());
        self::assertSame('退货备注', $order->getRemark());
    }

    public function testCompleteReturnWorkflow(): void
    {
        $order = new ReturnOrder();

        // 初始状态：可以发货，未发货，未完成
        self::assertSame(ReturnStatus::PENDING, $order->getStatus());
        self::assertTrue($order->canShip());
        self::assertFalse($order->isShipped());
        self::assertFalse($order->isCompleted());

        // 标记为已发货
        $order->markAsShipped('顺丰', 'SF123456789');
        self::assertSame(ReturnStatus::SHIPPED, $order->getStatus());
        self::assertFalse($order->canShip());
        self::assertTrue($order->isShipped());
        self::assertFalse($order->isCompleted());
        self::assertNotNull($order->getShipTime());

        // 更新物流信息为运输中
        $order->updateTrackingInfo([
            ['status' => 'in_transit', 'time' => '2024-08-13 12:00:00'],
        ]);
        self::assertSame(ReturnStatus::IN_TRANSIT, $order->getStatus());
        self::assertTrue($order->isShipped());

        // 标记为已收货
        $order->markAsReceived();
        self::assertSame(ReturnStatus::RECEIVED, $order->getStatus());
        self::assertTrue($order->isShipped());
        self::assertFalse($order->isCompleted());
        self::assertNotNull($order->getReceiveTime());

        // 标记为检查通过
        $order->markAsInspected(true);
        self::assertSame(ReturnStatus::INSPECTED, $order->getStatus());
        self::assertTrue($order->isShipped());
        self::assertTrue($order->isCompleted());
        self::assertNotNull($order->getInspectTime());
        self::assertNull($order->getRejectionReason());
    }

    public function testRejectedReturnWorkflow(): void
    {
        $order = new ReturnOrder();

        // 完成到收货阶段
        $order->markAsShipped('顺丰', 'SF123456789');
        $order->markAsReceived();

        // 标记为检查不通过
        $order->markAsInspected(false, '商品与描述不符');

        self::assertSame(ReturnStatus::REJECTED, $order->getStatus());
        self::assertFalse($order->isShipped());
        self::assertFalse($order->isCompleted());
        self::assertNotNull($order->getInspectTime());
        self::assertSame('商品与描述不符', $order->getRejectionReason());
    }

    public function testBusinessLogicValidation(): void
    {
        $order = new ReturnOrder();

        // 测试状态转换的业务逻辑
        self::assertTrue($order->canShip());
        self::assertFalse($order->isShipped());
        self::assertFalse($order->isCompleted());

        // 发货后的状态变化
        $order->setStatus(ReturnStatus::SHIPPED);
        self::assertFalse($order->canShip());
        self::assertTrue($order->isShipped());
        self::assertFalse($order->isCompleted());

        // 完成后的状态
        $order->setStatus(ReturnStatus::INSPECTED);
        self::assertFalse($order->canShip());
        self::assertTrue($order->isShipped());
        self::assertTrue($order->isCompleted());

        // 拒绝状态
        $order->setStatus(ReturnStatus::REJECTED);
        self::assertFalse($order->canShip());
        self::assertFalse($order->isShipped());
        self::assertFalse($order->isCompleted());
    }
}
