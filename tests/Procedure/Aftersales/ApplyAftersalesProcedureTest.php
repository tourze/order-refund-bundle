<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use Doctrine\ORM\EntityManagerInterface;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderPrice;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Enum\OrderState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\OrderRefundBundle\Param\Aftersales\ApplyAftersalesParam;
use Tourze\OrderRefundBundle\Procedure\Aftersales\ApplyAftersalesProcedure;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\ProductCoreBundle\Enum\PriceType;

/**
 * @internal
 */
#[CoversClass(ApplyAftersalesProcedure::class)]
#[RunTestsInSeparateProcesses]
final class ApplyAftersalesProcedureTest extends AbstractProcedureTestCase
{
    private ApplyAftersalesProcedure $procedure;

    private EntityManagerInterface $em;

    private UserInterface $testUser;

    private Contract $testContract;

    private OrderProduct $testOrderProduct;

    protected function onSetUp(): void
    {
        $this->em = self::getService(EntityManagerInterface::class);

        // 创建测试用户
        $this->testUser = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($this->testUser);

        // 创建真实的测试数据
        $this->createTestData();

        $this->procedure = self::getService(ApplyAftersalesProcedure::class);
    }

    private function createTestData(): void
    {
        // 创建 SPU
        $spu = new Spu();
        $spu->setTitle('Test Product');
        $this->em->persist($spu);

        // 创建 SKU
        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setTitle('Test SKU');
        $sku->setUnit('个');
        $this->em->persist($sku);

        // 创建 Contract
        $this->testContract = new Contract();
        $this->testContract->setUser($this->testUser);
        $this->testContract->setState(OrderState::PAID);
        $this->testContract->setType('normal');
        $this->em->persist($this->testContract);

        // 创建 OrderProduct
        $this->testOrderProduct = new OrderProduct();
        $this->testOrderProduct->setContract($this->testContract);
        $this->testOrderProduct->setSpu($spu);
        $this->testOrderProduct->setSku($sku);
        $this->testOrderProduct->setQuantity(2);
        $this->testOrderProduct->setValid(true);
        $this->em->persist($this->testOrderProduct);

        // 创建 OrderPrice - 为 OrderProduct 设置价格信息
        $orderPrice = new OrderPrice();
        $orderPrice->setContract($this->testContract);
        $orderPrice->setProduct($this->testOrderProduct);
        $orderPrice->setName('商品价格');
        $orderPrice->setCurrency('CNY');
        $orderPrice->setMoney('200.00'); // 总价：100.00 * 2
        $orderPrice->setUnitPrice('100.00');
        $orderPrice->setType(PriceType::SALE);
        $orderPrice->setPaid(true);
        $this->em->persist($orderPrice);

        $this->em->flush();

        // 保存 ID 用于后续重新加载
        $contractId = $this->testContract->getId();
        $orderProductId = $this->testOrderProduct->getId();

        // 清除 EntityManager 缓存，确保后续查询能获取到最新数据
        $this->em->clear();

        // 重新加载测试数据和用户
        $this->testContract = $this->em->find(Contract::class, $contractId);
        $this->testOrderProduct = $this->em->find(OrderProduct::class, $orderProductId);

        // 重新加载用户并设置认证（em->clear() 后原用户对象已分离）
        $this->testUser = $this->testContract->getUser();
        $this->setAuthenticatedUser($this->testUser);
    }

    public function testExecuteWithValidData(): void
    {
        $param = new ApplyAftersalesParam(
            contractId: (string) $this->testContract->getId(),
            type: 'refund_only',
            reason: 'quality_issue',
            description: 'Product damaged',
            items: [
                ['orderProductId' => (string) $this->testOrderProduct->getId(), 'quantity' => 1],
            ],
        );

        $result = $this->procedure->execute($param);

        $resultArray = $result->toArray();
        $this->assertIsArray($resultArray);
        $this->assertArrayHasKey('aftersalesList', $resultArray);
        $this->assertArrayHasKey('totalCount', $resultArray);
        $this->assertEquals(1, $resultArray['totalCount']);
        $this->assertCount(1, $resultArray['aftersalesList']);

        // 验证售后单数据
        $aftersalesData = $resultArray['aftersalesList'][0];
        $this->assertArrayHasKey('aftersalesId', $aftersalesData);
        $this->assertArrayHasKey('state', $aftersalesData);
        $this->assertEquals('pending_approval', $aftersalesData['state']);
    }

    public function testExecuteWithEmptyContractId(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('订单不存在');

        $param = new ApplyAftersalesParam(
            contractId: '',
            type: 'refund_only',
            reason: 'quality_issue',
            items: [
                ['orderProductId' => '1', 'quantity' => 1],
            ],
        );

        $this->procedure->execute($param);
    }

    public function testExecuteWithInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的售后类型');

        $param = new ApplyAftersalesParam(
            contractId: 'contract-123',
            type: 'invalid_type',
            reason: 'quality_issue',
            items: [
                ['orderProductId' => '1', 'quantity' => 1],
            ],
        );

        $this->procedure->execute($param);
    }

    public function testExecuteWithNonExistentContract(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('订单不存在');

        $param = new ApplyAftersalesParam(
            contractId: '999999',
            type: 'refund_only',
            reason: 'quality_issue',
            items: [
                ['orderProductId' => '1', 'quantity' => 1],
            ],
        );

        $this->procedure->execute($param);
    }

    public function testExecuteWithEmptyItems(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后商品列表不能为空');

        $param = new ApplyAftersalesParam(
            contractId: (string) $this->testContract->getId(),
            type: 'refund_only',
            reason: 'quality_issue',
            items: [],
        );

        $this->procedure->execute($param);
    }
}
