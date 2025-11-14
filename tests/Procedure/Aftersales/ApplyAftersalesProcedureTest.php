<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use Doctrine\Common\Collections\ArrayCollection;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Enum\OrderState;
use OrderCoreBundle\Repository\ContractRepository;
use OrderCoreBundle\Repository\OrderProductRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Procedure\Aftersales\ApplyAftersalesProcedure;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Service\AftersalesService;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(ApplyAftersalesProcedure::class)]
#[RunTestsInSeparateProcesses]
final class ApplyAftersalesProcedureTest extends AbstractProcedureTestCase
{
    private ApplyAftersalesProcedure $procedure;

    private ContractRepository&MockObject $contractRepository;

    private OrderProductRepository&MockObject $orderProductRepository;

    private AftersalesRepository&MockObject $aftersalesRepository;

    private AftersalesService&MockObject $aftersalesService;

    protected function onSetUp(): void
    {
        $this->contractRepository = $this->createMock(ContractRepository::class);
        $this->orderProductRepository = $this->createMock(OrderProductRepository::class);
        $this->aftersalesRepository = $this->createMock(AftersalesRepository::class);
        $this->aftersalesService = $this->createMock(AftersalesService::class);

        $mockUser = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($mockUser);

        // 替换服务
        self::getContainer()->set(ContractRepository::class, $this->contractRepository);
        self::getContainer()->set(OrderProductRepository::class, $this->orderProductRepository);
        self::getContainer()->set(AftersalesRepository::class, $this->aftersalesRepository);
        self::getContainer()->set(AftersalesService::class, $this->aftersalesService);

        $this->procedure = self::getService(ApplyAftersalesProcedure::class);
    }

    public function testExecuteWithValidData(): void
    {
        $this->procedure->contractId = 'contract-123';
        $this->procedure->type = 'refund_only';
        $this->procedure->reason = 'quality_issue';
        $this->procedure->description = 'Product damaged';
        $this->procedure->items = [
            ['orderProductId' => '1', 'quantity' => 1],
        ];

        // Mock contracts
        $mockContract = $this->createMock(Contract::class);
        $mockContract->method('getSn')->willReturn('ORDER-123');
        $mockContract->method('getUser')->willReturn($this->createNormalUser('test@example.com', 'password123'));
        $mockContract->method('getState')->willReturn(OrderState::PAID);
        $mockContract->method('getPrices')->willReturn(new ArrayCollection());

        // Mock order product
        $mockOrderProduct = $this->createMock(OrderProduct::class);
        $mockOrderProduct->method('getContract')->willReturn($mockContract);
        $mockOrderProduct->method('getQuantity')->willReturn(2);
        $mockOrderProduct->method('getSpu')->willReturn($this->createMockSpu());
        $mockOrderProduct->method('getSku')->willReturn(null);
        $mockOrderProduct->method('getPrices')->willReturn(new ArrayCollection());

        // 创建真实的 Aftersales 对象并使用 reflection 设置 ID
        $mockAftersales = new Aftersales();
        $reflection = new \ReflectionClass($mockAftersales);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($mockAftersales, 'aftersales-1');

        // 设置其他必要属性
        $mockAftersales->setState(AftersalesState::PENDING_APPROVAL);
        $mockAftersales->setStage(AftersalesStage::APPLY);

        $this->contractRepository->method('find')->willReturn($mockContract);
        $this->orderProductRepository->method('find')->willReturn($mockOrderProduct);
        $this->aftersalesRepository->method('findActiveAftersalesByOrderProductIds')->willReturn([]);
        $this->aftersalesService->method('createFromArray')->willReturn($mockAftersales);

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('aftersalesList', $result);
        $this->assertArrayHasKey('totalCount', $result);
        $this->assertEquals(1, $result['totalCount']);
    }

    public function testExecuteWithEmptyContractId(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('订单不存在');

        $this->procedure->contractId = '';
        $this->procedure->type = 'refund_only';
        $this->procedure->reason = 'quality_issue';
        $this->procedure->items = [
            ['orderProductId' => '1', 'quantity' => 1],
        ];

        $this->contractRepository->method('find')->willReturn(null);

        $this->procedure->execute();
    }

    public function testExecuteWithInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的售后类型');

        $this->procedure->contractId = 'contract-123';
        $this->procedure->type = 'invalid_type';
        $this->procedure->reason = 'quality_issue';
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
        $this->procedure->type = 'refund_only';
        $this->procedure->reason = 'quality_issue';
        $this->procedure->items = [
            ['orderProductId' => '1', 'quantity' => 1],
        ];

        $this->contractRepository->method('find')->willReturn(null);

        $this->procedure->execute();
    }

    public function testExecuteWithEmptyItems(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后商品列表不能为空');

        $this->procedure->contractId = 'contract-123';
        $this->procedure->type = 'refund_only';
        $this->procedure->reason = 'quality_issue';
        $this->procedure->items = [];

        $mockContract = $this->createMock(Contract::class);
        $mockContract->method('getUser')->willReturn($this->createNormalUser('test@example.com', 'password123'));

        $this->contractRepository->method('find')->willReturn($mockContract);

        $this->procedure->execute();
    }

    private function createMockSpu(): MockObject&Spu
    {
        $mockSpu = $this->createMock(Spu::class);
        $mockSpu->method('getTitle')->willReturn('Test Product');
        $mockSpu->method('getImages')->willReturn([]);

        return $mockSpu;
    }
}
