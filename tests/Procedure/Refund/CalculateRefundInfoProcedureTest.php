<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Refund;

use Doctrine\Common\Collections\ArrayCollection;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderPrice;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Enum\OrderState;
use OrderCoreBundle\Repository\ContractRepository;
use OrderCoreBundle\Repository\OrderProductRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Procedure\Refund\CalculateRefundInfoProcedure;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(CalculateRefundInfoProcedure::class)]
#[RunTestsInSeparateProcesses]
final class CalculateRefundInfoProcedureTest extends AbstractProcedureTestCase
{
    private CalculateRefundInfoProcedure $procedure;

    private ContractRepository&MockObject $contractRepository;

    private OrderProductRepository&MockObject $orderProductRepository;

    private AftersalesRepository&MockObject $aftersalesRepository;

    protected function onSetUp(): void
    {
        $this->contractRepository = $this->createMock(ContractRepository::class);
        $this->orderProductRepository = $this->createMock(OrderProductRepository::class);
        $this->aftersalesRepository = $this->createMock(AftersalesRepository::class);

        $mockUser = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($mockUser);

        // 替换服务
        self::getContainer()->set(ContractRepository::class, $this->contractRepository);
        self::getContainer()->set(OrderProductRepository::class, $this->orderProductRepository);
        self::getContainer()->set(AftersalesRepository::class, $this->aftersalesRepository);

        $this->procedure = self::getService(CalculateRefundInfoProcedure::class);
    }

    public function testExecuteWithValidData(): void
    {
        $this->procedure->contractId = 'contract-123';
        $this->procedure->items = [
            ['orderProductId' => '1', 'quantity' => 1],
        ];

        // Mock contract
        $mockContract = $this->createMock(Contract::class);
        $mockContract->method('getSn')->willReturn('ORDER-123');
        $mockContract->method('getUser')->willReturn($this->createNormalUser('test@example.com', 'password123'));
        $mockContract->method('getState')->willReturn(OrderState::PAID);

        // Mock order product
        $mockOrderProduct = $this->createMock(OrderProduct::class);
        $mockOrderProduct->method('getContract')->willReturn($mockContract);
        $mockOrderProduct->method('getQuantity')->willReturn(2);
        $mockOrderProduct->method('getSpu')->willReturn($this->createMockSpu());
        $mockOrderProduct->method('getSku')->willReturn($this->createMockSku());
        $mockOrderProduct->method('getPrices')->willReturn(new ArrayCollection([$this->createMockPrice()]));
        $mockOrderProduct->method('isValid')->willReturn(true);

        $this->contractRepository->method('find')->willReturn($mockContract);
        $this->orderProductRepository->method('findBy')->willReturn([$mockOrderProduct]);
        $this->aftersalesRepository->method('findRefundHistoryBatch')->willReturn([]);

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('contractId', $result);
        $this->assertArrayHasKey('orderNumber', $result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('canRefund', $result);
        $this->assertEquals('contract-123', $result['contractId']);
        $this->assertEquals('ORDER-123', $result['orderNumber']);
    }

    public function testExecuteWithEmptyContractId(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('订单ID不能为空');

        $this->procedure->contractId = '';
        $this->procedure->items = [
            ['orderProductId' => '1', 'quantity' => 1],
        ];

        $this->procedure->execute();
    }

    public function testExecuteWithNonExistentContract(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('订单不存在');

        $this->procedure->contractId = 'non-existent';
        $this->procedure->items = [
            ['orderProductId' => '1', 'quantity' => 1],
        ];

        $this->contractRepository->method('find')->willReturn(null);

        $this->procedure->execute();
    }

    public function testExecuteWithEmptyItems(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('商品退款申请列表不能为空');

        $this->procedure->contractId = 'contract-123';
        $this->procedure->items = [];

        // Mock contract to pass validation
        $mockContract = $this->createMock(Contract::class);
        $mockContract->method('getUser')->willReturn($this->createNormalUser('test@example.com', 'password123'));

        $this->contractRepository->method('find')->willReturn($mockContract);

        $this->procedure->execute();
    }

    public function testExecuteWithInvalidItemFormat(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第 1 个商品项目格式不正确，缺少必要字段');

        $this->procedure->contractId = 'contract-123';
        // 强制设置不符合类型的数据来测试验证逻辑
        $reflection = new \ReflectionProperty($this->procedure, 'items');
        $reflection->setValue($this->procedure, [
            ['orderProductId' => '1'], // 缺少 quantity
        ]);

        // Mock contract to pass validation
        $mockContract = $this->createMock(Contract::class);
        $mockContract->method('getUser')->willReturn($this->createNormalUser('test@example.com', 'password123'));

        $this->contractRepository->method('find')->willReturn($mockContract);

        $this->procedure->execute();
    }

    public function testExecuteWithInvalidQuantity(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第 1 个商品的quantity必须是大于0的整数');

        $this->procedure->contractId = 'contract-123';
        $this->procedure->items = [
            ['orderProductId' => '1', 'quantity' => 0],
        ];

        // Mock contract to pass validation
        $mockContract = $this->createMock(Contract::class);
        $mockContract->method('getUser')->willReturn($this->createNormalUser('test@example.com', 'password123'));

        $this->contractRepository->method('find')->willReturn($mockContract);

        $this->procedure->execute();
    }

    public function testExecuteWithUnauthorizedUser(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无权操作此订单');

        $this->procedure->contractId = 'contract-123';
        $this->procedure->items = [
            ['orderProductId' => '1', 'quantity' => 1],
        ];

        // Mock contract with different user
        $mockContract = $this->createMock(Contract::class);
        $mockContract->method('getUser')->willReturn($this->createNormalUser('other@example.com', 'password123'));

        $this->contractRepository->method('find')->willReturn($mockContract);

        $this->procedure->execute();
    }

    private function createMockSpu(): MockObject&Spu
    {
        $mockSpu = $this->createMock(Spu::class);
        $mockSpu->method('getTitle')->willReturn('Test Product');

        return $mockSpu;
    }

    private function createMockSku(): MockObject&Sku
    {
        $mockSku = $this->createMock(Sku::class);
        $mockSku->method('getGtin')->willReturn('TEST-SKU-001');
        $mockSku->method('getMainThumb')->willReturn('https://example.com/thumb.jpg');

        return $mockSku;
    }

    private function createMockPrice(): MockObject&OrderPrice
    {
        $mockPrice = $this->createMock(OrderPrice::class);
        $mockPrice->method('getCurrency')->willReturn('CNY');
        $mockPrice->method('getMoney')->willReturn('100.00');

        return $mockPrice;
    }
}
