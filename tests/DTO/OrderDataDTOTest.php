<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;

/**
 * @internal
 */
#[CoversClass(OrderDataDTO::class)]
class OrderDataDTOTest extends TestCase
{
    public function testCreateFromArray(): void
    {
        $data = [
            'orderNumber' => 'ORD001',
            'orderStatus' => 'paid',
            'orderCreateTime' => '2024-01-01',
            'userId' => 'user_001',
            'totalAmount' => 100.00,
        ];

        $dto = OrderDataDTO::fromArray($data);

        $this->assertEquals('ORD001', $dto->orderNumber);
        $this->assertEquals('paid', $dto->orderStatus);
        $this->assertInstanceOf(\DateTimeInterface::class, $dto->orderCreateTime);
        $this->assertEquals('user_001', $dto->userId);
        $this->assertEquals(100.00, $dto->totalAmount);
    }

    public function testValidation(): void
    {
        $dto = new OrderDataDTO('', 'paid', new \DateTime(), 'user', 100);
        $errors = $dto->validate();

        $this->assertContains('订单编号不能为空', $errors);
    }

    public function testNoContractDependency(): void
    {
        $reflection = new \ReflectionClass(OrderDataDTO::class);
        $fileName = $reflection->getFileName();
        $this->assertNotFalse($fileName, 'Should be able to get class file name');

        $source = file_get_contents($fileName);
        $this->assertNotFalse($source, 'Should be able to read file contents');
        $this->assertIsString($source);

        $this->assertStringNotContainsString('OrderCoreBundle\Entity\Contract', $source);
        $this->assertStringNotContainsString('Contract', $source);
    }
}
