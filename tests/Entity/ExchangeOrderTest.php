<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ExchangeOrder;
use Tourze\OrderRefundBundle\Enum\ExchangeStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ExchangeOrder::class)]
class ExchangeOrderTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
    }

    protected function createEntity(): ExchangeOrder
    {
        return new ExchangeOrder();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'exchangeNo' => ['exchangeNo', 'EX20240101001'],
            'status' => ['status', ExchangeStatus::PENDING],
            'returnTrackingNo' => ['returnTrackingNo', 'SF1234567890'],
            'returnExpressCompany' => ['returnExpressCompany', '顺丰速运'],
            'returnShipTime' => ['returnShipTime', new \DateTimeImmutable()],
            'returnReceiveTime' => ['returnReceiveTime', new \DateTimeImmutable('+3 days')],
            'exchangeShipTime' => ['exchangeShipTime', new \DateTimeImmutable('+4 days')],
            'sendTrackingNo' => ['sendTrackingNo', 'SF9876543210'],
            'sendExpressCompany' => ['sendExpressCompany', '顺丰速运'],
            'completeTime' => ['completeTime', new \DateTimeImmutable()],
            'exchangeReason' => ['exchangeReason', '商品质量问题'],
            'priceDifference' => ['priceDifference', '10.00'],
            'rejectionReason' => ['rejectionReason', '不符合换货条件'],
        ];
    }

    public function testExchangeOrderCreation(): void
    {
        $order = new ExchangeOrder();

        self::assertNull($order->getId());
        self::assertNotNull($order->getExchangeNo());
        self::assertStringStartsWith('EX', $order->getExchangeNo());
        self::assertSame(ExchangeStatus::PENDING, $order->getStatus());
        self::assertSame('0.00', $order->getPriceDifference());
    }

    public function testBasicSettersAndGetters(): void
    {
        $order = new ExchangeOrder();
        $aftersales = new Aftersales();

        $order->setAftersales($aftersales);
        $order->setExchangeNo('EX202408130001');
        $order->setStatus(ExchangeStatus::APPROVED);
        $order->setExchangeReason('商品有瑕疵需要换货');
        $order->setOriginalItems([['id' => 1, 'name' => '原商品']]);
        $order->setExchangeItems([['id' => 2, 'name' => '换货商品']]);
        $order->setPriceDifference('50.00');
        $order->setReturnExpressCompany('顺丰');
        $order->setReturnTrackingNo('SF123456789');
        $order->setSendExpressCompany('申通');
        $order->setSendTrackingNo('ST987654321');
        $order->setDeliveryAddress('上海市浦东新区测试路123号');
        $order->setRecipientName('张三');
        $order->setRecipientPhone('13800138000');
        $order->setRejectionReason(null);
        $order->setRemark('测试换货');

        self::assertSame($aftersales, $order->getAftersales());
        self::assertSame('EX202408130001', $order->getExchangeNo());
        self::assertSame(ExchangeStatus::APPROVED, $order->getStatus());
        self::assertSame('商品有瑕疵需要换货', $order->getExchangeReason());
        self::assertSame([['id' => 1, 'name' => '原商品']], $order->getOriginalItems());
        self::assertSame([['id' => 2, 'name' => '换货商品']], $order->getExchangeItems());
        self::assertSame('50.00', $order->getPriceDifference());
        self::assertSame('顺丰', $order->getReturnExpressCompany());
        self::assertSame('SF123456789', $order->getReturnTrackingNo());
        self::assertSame('申通', $order->getSendExpressCompany());
        self::assertSame('ST987654321', $order->getSendTrackingNo());
        self::assertSame('上海市浦东新区测试路123号', $order->getDeliveryAddress());
        self::assertSame('张三', $order->getRecipientName());
        self::assertSame('13800138000', $order->getRecipientPhone());
        self::assertNull($order->getRejectionReason());
        self::assertSame('测试换货', $order->getRemark());
    }

    public function testTimeSettersAndGetters(): void
    {
        $order = new ExchangeOrder();
        $returnShipTime = new \DateTimeImmutable('2024-08-13 10:00:00');
        $returnReceiveTime = new \DateTimeImmutable('2024-08-14 15:30:00');
        $exchangeShipTime = new \DateTimeImmutable('2024-08-15 09:00:00');
        $completeTime = new \DateTimeImmutable('2024-08-16 16:45:00');

        $order->setReturnShipTime($returnShipTime);
        $order->setReturnReceiveTime($returnReceiveTime);
        $order->setExchangeShipTime($exchangeShipTime);
        $order->setCompleteTime($completeTime);

        self::assertSame($returnShipTime, $order->getReturnShipTime());
        self::assertSame($returnReceiveTime, $order->getReturnReceiveTime());
        self::assertSame($exchangeShipTime, $order->getExchangeShipTime());
        self::assertSame($completeTime, $order->getCompleteTime());
    }

    public function testGetPriceDifferenceFloat(): void
    {
        $order = new ExchangeOrder();

        $order->setPriceDifference('123.45');
        self::assertSame(123.45, $order->getPriceDifferenceFloat());

        $order->setPriceDifference('0.00');
        self::assertSame(0.0, $order->getPriceDifferenceFloat());

        $order->setPriceDifference('-50.25');
        self::assertSame(-50.25, $order->getPriceDifferenceFloat());
    }

    public function testNeedsAdditionalPayment(): void
    {
        $order = new ExchangeOrder();

        $order->setPriceDifference('50.00');
        self::assertTrue($order->needsAdditionalPayment());

        $order->setPriceDifference('0.00');
        self::assertFalse($order->needsAdditionalPayment());

        $order->setPriceDifference('-30.00');
        self::assertFalse($order->needsAdditionalPayment());
    }

    public function testNeedsRefund(): void
    {
        $order = new ExchangeOrder();

        $order->setPriceDifference('-50.00');
        self::assertTrue($order->needsRefund());

        $order->setPriceDifference('0.00');
        self::assertFalse($order->needsRefund());

        $order->setPriceDifference('30.00');
        self::assertFalse($order->needsRefund());
    }

    public function testIsCompleted(): void
    {
        $order = new ExchangeOrder();

        self::assertFalse($order->isCompleted());

        $order->setStatus(ExchangeStatus::COMPLETED);
        self::assertTrue($order->isCompleted());

        $order->setStatus(ExchangeStatus::PENDING);
        self::assertFalse($order->isCompleted());
    }

    public function testNeedsUserAction(): void
    {
        $order = new ExchangeOrder();

        $order->setStatus(ExchangeStatus::PENDING);
        self::assertTrue($order->needsUserAction());

        $order->setStatus(ExchangeStatus::APPROVED);
        self::assertTrue($order->needsUserAction());

        $order->setStatus(ExchangeStatus::RETURN_RECEIVED);
        self::assertFalse($order->needsUserAction());

        $order->setStatus(ExchangeStatus::COMPLETED);
        self::assertFalse($order->needsUserAction());
    }

    public function testNeedsMerchantAction(): void
    {
        $order = new ExchangeOrder();

        $order->setStatus(ExchangeStatus::RETURN_RECEIVED);
        self::assertTrue($order->needsMerchantAction());

        $order->setStatus(ExchangeStatus::PENDING);
        self::assertFalse($order->needsMerchantAction());

        $order->setStatus(ExchangeStatus::COMPLETED);
        self::assertFalse($order->needsMerchantAction());
    }

    public function testMarkReturnAsShipped(): void
    {
        $order = new ExchangeOrder();
        $beforeTime = new \DateTimeImmutable();

        $order->markReturnAsShipped('顺丰', 'SF123456789');

        $afterTime = new \DateTimeImmutable();
        self::assertSame(ExchangeStatus::RETURN_SHIPPED, $order->getStatus());
        self::assertSame('顺丰', $order->getReturnExpressCompany());
        self::assertSame('SF123456789', $order->getReturnTrackingNo());
        self::assertNotNull($order->getReturnShipTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getReturnShipTime());
        self::assertLessThanOrEqual($afterTime, $order->getReturnShipTime());
    }

    public function testMarkReturnAsReceived(): void
    {
        $order = new ExchangeOrder();
        $beforeTime = new \DateTimeImmutable();

        $order->markReturnAsReceived();

        $afterTime = new \DateTimeImmutable();
        self::assertSame(ExchangeStatus::RETURN_RECEIVED, $order->getStatus());
        self::assertNotNull($order->getReturnReceiveTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getReturnReceiveTime());
        self::assertLessThanOrEqual($afterTime, $order->getReturnReceiveTime());
    }

    public function testMarkExchangeAsShipped(): void
    {
        $order = new ExchangeOrder();
        $beforeTime = new \DateTimeImmutable();

        $order->markExchangeAsShipped('申通', 'ST987654321');

        $afterTime = new \DateTimeImmutable();
        self::assertSame(ExchangeStatus::EXCHANGE_SHIPPED, $order->getStatus());
        self::assertSame('申通', $order->getSendExpressCompany());
        self::assertSame('ST987654321', $order->getSendTrackingNo());
        self::assertNotNull($order->getExchangeShipTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getExchangeShipTime());
        self::assertLessThanOrEqual($afterTime, $order->getExchangeShipTime());
    }

    public function testMarkAsCompleted(): void
    {
        $order = new ExchangeOrder();
        $beforeTime = new \DateTimeImmutable();

        $order->markAsCompleted();

        $afterTime = new \DateTimeImmutable();
        self::assertSame(ExchangeStatus::COMPLETED, $order->getStatus());
        self::assertNotNull($order->getCompleteTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getCompleteTime());
        self::assertLessThanOrEqual($afterTime, $order->getCompleteTime());
    }

    public function testMarkAsRejected(): void
    {
        $order = new ExchangeOrder();

        $order->markAsRejected('商品质量不符合换货要求');
        self::assertSame(ExchangeStatus::REJECTED, $order->getStatus());
        self::assertSame('商品质量不符合换货要求', $order->getRejectionReason());
    }

    public function testToString(): void
    {
        $order = new ExchangeOrder();

        // 测试使用 exchangeNo
        $order->setExchangeNo('EX202408130001');
        self::assertSame('EX202408130001', (string) $order);
    }

    public function testToStringWithoutExchangeNo(): void
    {
        $order = new ExchangeOrder();

        // 设置 exchangeNo 为 null（虽然构造函数会生成，但测试边界情况）
        $reflection = new \ReflectionClass($order);
        $property = $reflection->getProperty('exchangeNo');
        $property->setAccessible(true);
        $property->setValue($order, null);

        // 当 exchangeNo 为 null 时，应该返回 ID（但ID也为null时返回空字符串）
        self::assertSame('', (string) $order);
    }

    public function testSettersAndGetters(): void
    {
        $order = new ExchangeOrder();
        $aftersales = new Aftersales();

        // 使用独立的setter调用，因为setter现在返回void
        $order->setAftersales($aftersales);
        $order->setStatus(ExchangeStatus::APPROVED);
        $order->setExchangeReason('换货测试');
        $order->setPriceDifference('25.50');
        $order->setDeliveryAddress('测试地址');
        $order->setRecipientName('测试用户');
        $order->setRecipientPhone('13888888888');

        // 验证所有设置的值
        self::assertSame($aftersales, $order->getAftersales());
        self::assertSame(ExchangeStatus::APPROVED, $order->getStatus());
        self::assertSame('换货测试', $order->getExchangeReason());
        self::assertSame('25.50', $order->getPriceDifference());
        self::assertSame('测试地址', $order->getDeliveryAddress());
        self::assertSame('测试用户', $order->getRecipientName());
        self::assertSame('13888888888', $order->getRecipientPhone());
    }

    public function testGenerateExchangeNoFormat(): void
    {
        $order = new ExchangeOrder();
        $exchangeNo = $order->getExchangeNo();

        self::assertNotNull($exchangeNo);
        // 验证换货单号格式：EX + 8位日期 + 6位随机数
        self::assertMatchesRegularExpression('/^EX\d{8}\d{6}$/', $exchangeNo);

        // 验证日期部分是今天
        $today = date('Ymd');
        self::assertStringStartsWith('EX' . $today, $exchangeNo);
    }

    public function testBusinessLogicCombination(): void
    {
        $order = new ExchangeOrder();

        // 测试业务逻辑组合：正差价 + 需要用户操作
        $order->setPriceDifference('30.00');
        $order->setStatus(ExchangeStatus::PENDING);

        self::assertTrue($order->needsAdditionalPayment());
        self::assertFalse($order->needsRefund());
        self::assertTrue($order->needsUserAction());
        self::assertFalse($order->needsMerchantAction());
        self::assertFalse($order->isCompleted());

        // 测试业务逻辑组合：负差价 + 商家处理
        $order->setPriceDifference('-20.00');
        $order->setStatus(ExchangeStatus::RETURN_RECEIVED);

        self::assertFalse($order->needsAdditionalPayment());
        self::assertTrue($order->needsRefund());
        self::assertFalse($order->needsUserAction());
        self::assertTrue($order->needsMerchantAction());
        self::assertFalse($order->isCompleted());
    }
}
