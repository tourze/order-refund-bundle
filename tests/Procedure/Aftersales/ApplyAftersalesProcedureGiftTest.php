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
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\OrderRefundBundle\Param\Aftersales\ApplyAftersalesParam;
use Tourze\OrderRefundBundle\Procedure\Aftersales\ApplyAftersalesProcedure;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;
use Tourze\ProductCoreBundle\Enum\PriceType;
use Tourze\ProductCoreBundle\Enum\SpuState;

/**
 * ApplyAftersalesProcedure 赠品场景集成测试
 *
 * 测试赠品不允许售后的业务规则
 *
 * @internal
 */
#[CoversClass(ApplyAftersalesProcedure::class)]
#[RunTestsInSeparateProcesses]
final class ApplyAftersalesProcedureGiftTest extends AbstractProcedureTestCase
{
    private ApplyAftersalesProcedure $procedure;

    private EntityManagerInterface $em;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(ApplyAftersalesProcedure::class);
        $this->em = self::getEntityManager();
    }

    public function testExecute(): void
    {
        // 测试基本的 execute 方法是否可用
        $user = $this->createNormalUser('execute-test-user', 'password123');
        $this->setAuthenticatedUser($user);

        $contract = $this->createContract($user, 'CONTRACT-EXECUTE-001');
        $normalProduct = $this->createNormalProduct($contract, 'EXECUTE-SKU-001');

        $param = new ApplyAftersalesParam(
            contractId: (string) $contract->getId(),
            type: 'return_refund',
            reason: 'quality_issue',
            description: '商品有质量问题',
            proofImages: [],
            items: [
                [
                    'orderProductId' => (string) $normalProduct->getId(),
                    'quantity' => 1,
                ]
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $this->assertArrayHasKey('aftersalesList', $result->data);
        $this->assertArrayHasKey('totalCount', $result->data);
    }

    public function testApplyAftersalesWithGiftProduct(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('gift-test-user-1', 'password123');
        $this->setAuthenticatedUser($user);

        // 创建测试合同
        $contract = $this->createContract($user, 'CONTRACT-GIFT-001');

        // 创建赠品商品
        $giftProduct = $this->createGiftProduct($contract, 'GIFT-SKU-001');

        $param = new ApplyAftersalesParam(
            contractId: (string) $contract->getId(),
            type: 'return_refund',
            reason: 'quality_issue',
            description: '商品有质量问题',
            proofImages: [],
            items: [
                [
                    'orderProductId' => (string) $giftProduct->getId(),
                    'quantity' => 1,
                ]
            ],
        );

        // 期望抛出 API 异常
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('创建商品 ' . $giftProduct->getId() . ' 的售后单失败: 赠品不允许售后，如有疑问请联系客服');

        $this->procedure->execute($param);
    }

    public function testApplyAftersalesWithNormalProduct(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('normal-test-user-1', 'password123');
        $this->setAuthenticatedUser($user);

        // 创建测试合同
        $contract = $this->createContract($user, 'CONTRACT-NORMAL-001');

        // 创建正常商品
        $normalProduct = $this->createNormalProduct($contract, 'NORMAL-SKU-001');

        $param = new ApplyAftersalesParam(
            contractId: (string) $contract->getId(),
            type: 'return_refund',
            reason: 'quality_issue',
            description: '商品有质量问题',
            proofImages: [],
            items: [
                [
                    'orderProductId' => (string) $normalProduct->getId(),
                    'quantity' => 1,
                ]
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $this->assertArrayHasKey('aftersalesList', $result->data);
        $this->assertArrayHasKey('totalCount', $result->data);
        $this->assertNotEmpty($result['aftersalesList']);
        $this->assertEquals(1, $result['totalCount']);
    }

    public function testApplyAftersalesWithMixedProducts(): void
    {
        // 创建测试用户
        $user = $this->createNormalUser('mixed-test-user-1', 'password123');
        $this->setAuthenticatedUser($user);

        // 创建测试合同
        $contract = $this->createContract($user, 'CONTRACT-MIXED-001');

        // 创建正常商品和赠品
        $normalProduct = $this->createNormalProduct($contract, 'MIXED-NORMAL-SKU-001');
        $giftProduct = $this->createGiftProduct($contract, 'MIXED-GIFT-SKU-001');

        $param = new ApplyAftersalesParam(
            contractId: (string) $contract->getId(),
            type: 'return_refund',
            reason: 'quality_issue',
            description: '商品有质量问题',
            proofImages: [],
            items: [
                [
                    'orderProductId' => (string) $normalProduct->getId(),
                    'quantity' => 1,
                ],
                [
                    'orderProductId' => (string) $giftProduct->getId(),
                    'quantity' => 1,
                ]
            ],
        );

        $result = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $result);
        $this->assertArrayHasKey('aftersalesList', $result->data);
        $this->assertArrayHasKey('totalCount', $result->data);
        $this->assertArrayHasKey('errors', $result->data);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('赠品不允许售后', $result['errors'][0]);
    }

    /**
     * 创建测试合同
     */
    private function createContract(UserInterface $user, string $sn): Contract
    {
        $contract = new Contract();
        $contract->setSn($sn);
        $contract->setUser($user);
        $contract->setState(OrderState::PAID);
        $contract->setType('default');

        $this->em->persist($contract);
        $this->em->flush();

        return $contract;
    }

    /**
     * 创建赠品商品
     */
    private function createGiftProduct(Contract $contract, string $skuCode): OrderProduct
    {
        $spu = $this->createSpu('赠品商品');
        $sku = $this->createSku($spu, $skuCode);

        $product = new OrderProduct();
        $product->setContract($contract);
        $product->setSpu($spu);
        $product->setSku($sku);
        $product->setIsGift(true); // 设置为赠品
        $product->setQuantity(1);
        $product->setValid(true);

        // 使用 OrderPrice 来设置价格
        $this->addPriceToProduct($product, '0.00', 'CNY', '赠品价格');

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * 创建正常商品
     */
    private function createNormalProduct(Contract $contract, string $skuCode): OrderProduct
    {
        $spu = $this->createSpu('正常商品');
        $sku = $this->createSku($spu, $skuCode);

        $product = new OrderProduct();
        $product->setContract($contract);
        $product->setSpu($spu);
        $product->setSku($sku);
        $product->setIsGift(false); // 设置为非赠品
        $product->setQuantity(1);
        $product->setValid(true);

        // 使用 OrderPrice 来设置价格
        $this->addPriceToProduct($product, '100.00', 'CNY', '商品价格');

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    /**
     * 添加价格到商品
     */
    private function addPriceToProduct(OrderProduct $product, string $money, string $currency, string $name): void
    {
        $price = new OrderPrice();
        $price->setProduct($product);
        $price->setContract($product->getContract());
        $price->setMoney($money);
        $price->setCurrency($currency);
        $price->setName($name);
        $price->setType(PriceType::SALE);

        $product->addPrice($price);
        $this->em->persist($price);
    }

    /**
     * 创建测试 SPU
     */
    private function createSpu(string $title): Spu
    {
        $spu = new Spu();
        $spu->setTitle($title);
        $spu->setState(SpuState::ONLINE);

        $this->em->persist($spu);
        $this->em->flush();

        return $spu;
    }

    /**
     * 创建测试 SKU
     */
    private function createSku(Spu $spu, string $mpn): Sku
    {
        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setMpn($mpn);
        $sku->setTitle($spu->getTitle());
        $sku->setUnit('件'); // 设置必填的单位字段

        $this->em->persist($sku);
        $this->em->flush();

        return $sku;
    }
}
