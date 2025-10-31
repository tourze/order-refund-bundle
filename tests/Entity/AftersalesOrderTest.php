<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesOrder::class)]
class AftersalesOrderTest extends AbstractEntityTestCase
{
    public function testOrderSnapshot(): void
    {
        $order = new AftersalesOrder();
        $order->setOrderNumber('ORD001');
        $order->setOrderStatus('paid');
        $order->setTotalAmount('100.00');

        $this->assertEquals('ORD001', $order->getOrderNumber());
        $this->assertEquals('paid', $order->getOrderStatus());
        $this->assertEquals('100.00', $order->getTotalAmount());
    }

    public function testHasContractId(): void
    {
        $order = new AftersalesOrder();
        $order->setContractId('contract123');

        $this->assertEquals('contract123', $order->getContractId());
    }

    public function testCompleteDataSnapshot(): void
    {
        $order = new AftersalesOrder();

        // 验证所有必要字段都存在
        $reflection = new \ReflectionClass($order);
        $this->assertTrue($reflection->hasProperty('orderNumber'));
        $this->assertTrue($reflection->hasProperty('orderStatus'));
        $this->assertTrue($reflection->hasProperty('orderCreateTime'));
        $this->assertTrue($reflection->hasProperty('userId'));
        $this->assertTrue($reflection->hasProperty('totalAmount'));
        $this->assertTrue($reflection->hasProperty('extra'));
    }

    protected function createEntity(): object
    {
        return new AftersalesOrder();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'orderNumber' => ['orderNumber', 'ORD001'];
        yield 'orderStatus' => ['orderStatus', 'paid'];
        yield 'orderCreateTime' => ['orderCreateTime', new \DateTimeImmutable()];
        yield 'userId' => ['userId', 'user123'];
        yield 'contractId' => ['contractId', 'contract123'];
        yield 'totalAmount' => ['totalAmount', '100.00'];
        yield 'extra' => ['extra', ['key' => 'value']];
    }
}
