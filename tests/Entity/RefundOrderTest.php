<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\RefundOrder;
use Tourze\OrderRefundBundle\Enum\PaymentMethod;
use Tourze\OrderRefundBundle\Enum\RefundStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(RefundOrder::class)]
class RefundOrderTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
    }

    protected function createEntity(): RefundOrder
    {
        return new RefundOrder();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'refundNo' => ['refundNo', 'RF20240101001'],
            'paymentMethod' => ['paymentMethod', PaymentMethod::ALIPAY],
            'status' => ['status', RefundStatus::PENDING],
            'refundAmount' => ['refundAmount', '100.00'],
            'refundPoints' => ['refundPoints', 50],
            'refundTransactionNo' => ['refundTransactionNo', 'TP123456789'],
            'processTime' => ['processTime', new \DateTimeImmutable()],
            'failureReason' => ['failureReason', '测试失败原因'],
        ];
    }

    public function testRefundOrderCreation(): void
    {
        $order = new RefundOrder();

        self::assertNull($order->getId());
        self::assertNotNull($order->getRefundNo());
        self::assertStringStartsWith('RF', $order->getRefundNo());
        self::assertSame(RefundStatus::PENDING, $order->getStatus());
        self::assertSame(0, $order->getRefundPoints());
        self::assertSame(0, $order->getRetryCount());
    }

    public function testBasicSettersAndGetters(): void
    {
        $order = new RefundOrder();
        $aftersales = new Aftersales();

        $order->setAftersales($aftersales);
        $order->setRefundNo('RF202408130001');
        $order->setPaymentMethod(PaymentMethod::ALIPAY);
        $order->setStatus(RefundStatus::PROCESSING);
        $order->setRefundAmount('99.99');
        $order->setRefundPoints(100);
        $order->setOriginalTransactionNo('TX123456789');
        $order->setRefundTransactionNo('RX987654321');
        $order->setFailureReason('网络超时');
        $order->setGatewayResponse(['code' => 200, 'message' => 'success']);

        self::assertSame($aftersales, $order->getAftersales());
        self::assertSame('RF202408130001', $order->getRefundNo());
        self::assertSame(PaymentMethod::ALIPAY, $order->getPaymentMethod());
        self::assertSame(RefundStatus::PROCESSING, $order->getStatus());
        self::assertSame('99.99', $order->getRefundAmount());
        self::assertSame(100, $order->getRefundPoints());
        self::assertSame('TX123456789', $order->getOriginalTransactionNo());
        self::assertSame('RX987654321', $order->getRefundTransactionNo());
        self::assertSame('网络超时', $order->getFailureReason());
        self::assertSame(['code' => 200, 'message' => 'success'], $order->getGatewayResponse());
    }

    public function testTimeSettersAndGetters(): void
    {
        $order = new RefundOrder();
        $processTime = new \DateTimeImmutable('2024-08-13 10:30:00');
        $completeTime = new \DateTimeImmutable('2024-08-13 11:00:00');

        $order->setProcessTime($processTime);
        $order->setCompleteTime($completeTime);

        self::assertSame($processTime, $order->getProcessTime());
        self::assertSame($completeTime, $order->getCompleteTime());
    }

    public function testIncrementRetryCount(): void
    {
        $order = new RefundOrder();

        self::assertSame(0, $order->getRetryCount());

        $result = $order->incrementRetryCount();

        self::assertSame($order, $result);
        self::assertSame(1, $order->getRetryCount());

        $order->incrementRetryCount();
        self::assertSame(2, $order->getRetryCount());
    }

    public function testCanRetry(): void
    {
        $order = new RefundOrder();

        // 默认状态不是 FAILED，不能重试
        self::assertFalse($order->canRetry());

        // 设置为 FAILED 状态，重试次数为 0，可以重试
        $order->setStatus(RefundStatus::FAILED);
        self::assertTrue($order->canRetry());

        // 重试次数达到 3 次，不能重试
        $order->incrementRetryCount();
        $order->incrementRetryCount();
        $order->incrementRetryCount();
        self::assertFalse($order->canRetry());

        // 状态不是 FAILED，即使重试次数少于 3 也不能重试
        $order = new RefundOrder();
        $order->setStatus(RefundStatus::SUCCESS);
        self::assertFalse($order->canRetry());
    }

    public function testIsCompleted(): void
    {
        $order = new RefundOrder();

        self::assertFalse($order->isCompleted());

        $order->setStatus(RefundStatus::SUCCESS);
        self::assertTrue($order->isCompleted());

        $order->setStatus(RefundStatus::PROCESSING);
        self::assertFalse($order->isCompleted());
    }

    public function testIsFailed(): void
    {
        $order = new RefundOrder();

        self::assertFalse($order->isFailed());

        $order->setStatus(RefundStatus::FAILED);
        self::assertTrue($order->isFailed());

        $order->setStatus(RefundStatus::PENDING);
        self::assertFalse($order->isFailed());
    }

    public function testMarkAsProcessing(): void
    {
        $order = new RefundOrder();
        $beforeTime = new \DateTimeImmutable();

        $result = $order->markAsProcessing();

        $afterTime = new \DateTimeImmutable();

        self::assertSame($order, $result);
        self::assertSame(RefundStatus::PROCESSING, $order->getStatus());
        self::assertNotNull($order->getProcessTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getProcessTime());
        self::assertLessThanOrEqual($afterTime, $order->getProcessTime());
    }

    public function testMarkAsSuccess(): void
    {
        $order = new RefundOrder();
        $beforeTime = new \DateTimeImmutable();

        $result = $order->markAsSuccess('SUCCESS_TX_123');

        $afterTime = new \DateTimeImmutable();

        self::assertSame($order, $result);
        self::assertSame(RefundStatus::SUCCESS, $order->getStatus());
        self::assertSame('SUCCESS_TX_123', $order->getRefundTransactionNo());
        self::assertNotNull($order->getCompleteTime());
        self::assertGreaterThanOrEqual($beforeTime, $order->getCompleteTime());
        self::assertLessThanOrEqual($afterTime, $order->getCompleteTime());
    }

    public function testMarkAsSuccessWithoutTransactionNo(): void
    {
        $order = new RefundOrder();

        $result = $order->markAsSuccess();

        self::assertSame($order, $result);
        self::assertSame(RefundStatus::SUCCESS, $order->getStatus());
        self::assertNull($order->getRefundTransactionNo());
        self::assertNotNull($order->getCompleteTime());
    }

    public function testMarkAsFailed(): void
    {
        $order = new RefundOrder();
        $initialRetryCount = $order->getRetryCount();

        $result = $order->markAsFailed('网络连接失败');

        self::assertSame($order, $result);
        self::assertSame(RefundStatus::FAILED, $order->getStatus());
        self::assertSame('网络连接失败', $order->getFailureReason());
        self::assertSame($initialRetryCount + 1, $order->getRetryCount());
    }

    public function testGetRefundAmountFloat(): void
    {
        $order = new RefundOrder();

        $order->setRefundAmount('123.45');
        self::assertSame(123.45, $order->getRefundAmountFloat());

        $order->setRefundAmount('0.00');
        self::assertSame(0.0, $order->getRefundAmountFloat());

        $order->setRefundAmount('999.99');
        self::assertSame(999.99, $order->getRefundAmountFloat());
    }

    public function testGetTotalRefundValue(): void
    {
        $order = new RefundOrder();

        $order->setRefundAmount('100.00');
        $order->setRefundPoints(1000);

        // 默认积分汇率 0.01
        $totalValue = $order->getTotalRefundValue();
        self::assertSame(110.0, $totalValue); // 100.00 + (1000 * 0.01)

        // 自定义积分汇率
        $totalValue = $order->getTotalRefundValue(0.02);
        self::assertSame(120.0, $totalValue); // 100.00 + (1000 * 0.02)
    }

    public function testGetTotalRefundValueWithoutPoints(): void
    {
        $order = new RefundOrder();

        $order->setRefundAmount('50.00');
        $order->setRefundPoints(0);

        $totalValue = $order->getTotalRefundValue();
        self::assertSame(50.0, $totalValue);
    }

    public function testToString(): void
    {
        $order = new RefundOrder();

        // 测试使用 refundNo
        $order->setRefundNo('RF202408130001');
        self::assertSame('RF202408130001', (string) $order);
    }

    public function testToStringWithoutRefundNo(): void
    {
        $order = new RefundOrder();

        // 设置 refundNo 为 null（虽然构造函数会生成，但测试边界情况）
        $reflection = new \ReflectionClass($order);
        $property = $reflection->getProperty('refundNo');
        $property->setAccessible(true);
        $property->setValue($order, null);

        // 当 refundNo 为 null 时，应该返回 ID（但ID也为null时返回空字符串）
        self::assertSame('', (string) $order);
    }

    public function testGenerateRefundNoFormat(): void
    {
        $order = new RefundOrder();
        $refundNo = $order->getRefundNo();

        self::assertNotNull($refundNo);
        // 验证退款单号格式：RF + 8位日期 + 6位随机数
        self::assertMatchesRegularExpression('/^RF\d{8}\d{6}$/', $refundNo);

        // 验证日期部分是今天
        $today = date('Ymd');
        self::assertStringStartsWith('RF' . $today, $refundNo);
    }

    public function testMethodChaining(): void
    {
        $order = new RefundOrder();
        $aftersales = new Aftersales();

        $order->setAftersales($aftersales);
        $order->setPaymentMethod(PaymentMethod::WECHAT_PAY);
        $order->setRefundAmount('199.99');
        $order->setRefundPoints(200);
        $order->setOriginalTransactionNo('ORIG_TX_456');
        self::assertSame($aftersales, $order->getAftersales());
        self::assertSame(PaymentMethod::WECHAT_PAY, $order->getPaymentMethod());
        self::assertSame('199.99', $order->getRefundAmount());
        self::assertSame(200, $order->getRefundPoints());
        self::assertSame('ORIG_TX_456', $order->getOriginalTransactionNo());
    }

    public function testCompleteRefundWorkflow(): void
    {
        $order = new RefundOrder();

        // 初始状态
        self::assertSame(RefundStatus::PENDING, $order->getStatus());
        self::assertFalse($order->isCompleted());
        self::assertFalse($order->isFailed());

        // 标记为处理中
        $order->markAsProcessing();
        self::assertSame(RefundStatus::PROCESSING, $order->getStatus());
        self::assertNotNull($order->getProcessTime());

        // 标记为成功
        $order->markAsSuccess('TX_SUCCESS_123');
        self::assertSame(RefundStatus::SUCCESS, $order->getStatus());
        self::assertTrue($order->isCompleted());
        self::assertFalse($order->isFailed());
        self::assertSame('TX_SUCCESS_123', $order->getRefundTransactionNo());
        self::assertNotNull($order->getCompleteTime());
    }

    public function testFailedRefundWorkflow(): void
    {
        $order = new RefundOrder();

        // 初始状态
        self::assertSame(0, $order->getRetryCount());
        self::assertFalse($order->canRetry());

        // 第一次失败
        $order->markAsFailed('第一次失败');
        self::assertSame(RefundStatus::FAILED, $order->getStatus());
        self::assertTrue($order->isFailed());
        self::assertSame(1, $order->getRetryCount());
        self::assertTrue($order->canRetry());

        // 继续失败直到达到重试上限
        $order->markAsFailed('第二次失败');
        self::assertSame(2, $order->getRetryCount());
        self::assertTrue($order->canRetry());

        $order->markAsFailed('第三次失败');
        self::assertSame(3, $order->getRetryCount());
        self::assertFalse($order->canRetry());
        self::assertSame('第三次失败', $order->getFailureReason());
    }

    public function testBusinessLogicValidation(): void
    {
        $order = new RefundOrder();

        // 测试金额和积分的业务逻辑
        $order->setRefundAmount('150.50');
        $order->setRefundPoints(500);

        // 验证计算结果
        self::assertSame(150.50, $order->getRefundAmountFloat());
        self::assertSame(155.5, $order->getTotalRefundValue()); // 150.50 + (500 * 0.01)

        // 测试状态转换的业务逻辑
        $order->setStatus(RefundStatus::PENDING);
        self::assertFalse($order->canRetry());
        self::assertFalse($order->isCompleted());
        self::assertFalse($order->isFailed());

        $order->setStatus(RefundStatus::SUCCESS);
        self::assertTrue($order->isCompleted());
        self::assertFalse($order->isFailed());
        self::assertFalse($order->canRetry());
    }
}
