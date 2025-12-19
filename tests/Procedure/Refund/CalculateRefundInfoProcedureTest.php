<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Refund;

use Doctrine\ORM\EntityManagerInterface;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderPrice;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Enum\OrderState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\OrderRefundBundle\Param\Refund\CalculateRefundInfoParam;
use Tourze\OrderRefundBundle\Procedure\Refund\CalculateRefundInfoProcedure;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\ProductCoreBundle\Enum\PriceType;

/**
 * @internal
 */
#[CoversClass(CalculateRefundInfoProcedure::class)]
#[RunTestsInSeparateProcesses]
final class CalculateRefundInfoProcedureTest extends AbstractProcedureTestCase
{
    private CalculateRefundInfoProcedure $procedure;

    private EntityManagerInterface $em;

    private Contract $testContract;

    private OrderProduct $testOrderProduct;

    protected function onSetUp(): void
    {
        $mockUser = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($mockUser);

        $this->em = self::getService(EntityManagerInterface::class);

        // 创建真实的测试数据
        $this->createTestData($mockUser);

        $this->procedure = self::getService(CalculateRefundInfoProcedure::class);
    }

    /**
     * @param object $user
     */
    private function createTestData($user): void
    {
        // 创建 SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $this->em->persist($spu);

        // 创建 SKU
        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setGtin('TEST-SKU-001');
        $sku->setUnit('个');
        $this->em->persist($sku);

        // 创建订单
        $contract = new Contract();
        $contract->setUser($user);
        $contract->setState(OrderState::PAID);
        $contract->setPayTime(new \DateTimeImmutable());
        $this->em->persist($contract);

        // 创建订单商品
        $orderProduct = new OrderProduct();
        $orderProduct->setContract($contract);
        $orderProduct->setSpu($spu);
        $orderProduct->setSku($sku);
        $orderProduct->setQuantity(2);
        $orderProduct->setValid(true);
        $this->em->persist($orderProduct);

        // 创建价格信息
        $price = new OrderPrice();
        $price->setContract($contract);
        $price->setProduct($orderProduct);
        $price->setName('商品价格');
        $price->setCurrency('CNY');
        $price->setMoney('100.00');
        $price->setType(PriceType::SALE);
        $this->em->persist($price);

        $this->em->flush();

        $this->testContract = $contract;
        $this->testOrderProduct = $orderProduct;
    }

    public function testExecuteWithValidData(): void
    {
        $param = new CalculateRefundInfoParam(
            contractId: (string) $this->testContract->getId(),
            items: [
                ['orderProductId' => (string) $this->testOrderProduct->getId(), 'quantity' => 1],
            ]
        );

        $result = $this->procedure->execute($param);
        $resultArray = $result->toArray();

        $this->assertIsArray($resultArray);
        $this->assertArrayHasKey('contractId', $resultArray);
        $this->assertArrayHasKey('orderNumber', $resultArray);
        $this->assertArrayHasKey('items', $resultArray);
        $this->assertArrayHasKey('canRefund', $resultArray);
        $this->assertEquals((string) $this->testContract->getId(), $resultArray['contractId']);
        $this->assertEquals($this->testContract->getSn(), $resultArray['orderNumber']);
    }

    public function testExecuteWithEmptyContractId(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('订单ID不能为空');

        $param = new CalculateRefundInfoParam(
            contractId: '',
            items: [
                ['orderProductId' => '1', 'quantity' => 1],
            ]
        );

        $this->procedure->execute($param);
    }

    public function testExecuteWithNonExistentContract(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('订单不存在');

        $param = new CalculateRefundInfoParam(
            contractId: '999999',
            items: [
                ['orderProductId' => '1', 'quantity' => 1],
            ]
        );

        $this->procedure->execute($param);
    }

    public function testExecuteWithEmptyItems(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('商品退款申请列表不能为空');

        $param = new CalculateRefundInfoParam(
            contractId: (string) $this->testContract->getId(),
            items: []
        );

        $this->procedure->execute($param);
    }

    public function testExecuteWithInvalidItemFormat(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第 1 个商品项目格式不正确，缺少必要字段');

        // 强制设置不符合类型的数据来测试验证逻辑
        $param = new CalculateRefundInfoParam(
            contractId: (string) $this->testContract->getId(),
            items: [
                ['orderProductId' => '1'], // 缺少 quantity
            ]
        );

        $this->procedure->execute($param);
    }

    public function testExecuteWithInvalidQuantity(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第 1 个商品的quantity必须是大于0的整数');

        $param = new CalculateRefundInfoParam(
            contractId: (string) $this->testContract->getId(),
            items: [
                ['orderProductId' => '1', 'quantity' => 0],
            ]
        );

        $this->procedure->execute($param);
    }

    public function testExecuteWithUnauthorizedUser(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无权操作此订单');

        // 创建另一个用户的订单
        $otherUser = $this->createNormalUser('other@example.com', 'password456');

        $otherContract = new Contract();
        $otherContract->setUser($otherUser);
        $otherContract->setState(OrderState::PAID);
        $this->em->persist($otherContract);
        $this->em->flush();

        $param = new CalculateRefundInfoParam(
            contractId: (string) $otherContract->getId(),
            items: [
                ['orderProductId' => '1', 'quantity' => 1],
            ]
        );

        $this->procedure->execute($param);
    }
}
