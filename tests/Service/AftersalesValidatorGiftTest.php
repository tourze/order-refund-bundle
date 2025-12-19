<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use InvalidArgumentException;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Enum\OrderState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderRefundBundle\Service\AftersalesValidator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(AftersalesValidator::class)]
#[RunTestsInSeparateProcesses]
final class AftersalesValidatorGiftTest extends AbstractIntegrationTestCase
{
    private AftersalesValidator $validator;
    private UserInterface $user;

    protected function onSetUp(): void
    {
        $this->validator = self::getService(AftersalesValidator::class);
        $this->user = $this->createNormalUser();
    }

    /**
     * 创建测试用的Contract
     */
    private function createTestContract(): Contract
    {
        $contract = new Contract();
        $contract->setSn('TEST-CONTRACT-' . uniqid());
        $contract->setState(OrderState::INIT);
        $contract->setUser($this->user);

        return $this->persistAndFlush($contract);
    }

    /**
     * 创建测试用的OrderProduct
     */
    private function createTestOrderProduct(Contract $contract, bool $isGift = false, ?string $source = null): OrderProduct
    {
        // 创建Spu和Sku
        $spu = new Spu();
        $spu->setTitle('测试商品');
        $this->persistAndFlush($spu);

        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setTitle('测试SKU');
        $sku->setMarketPrice('100.00');
        $sku->setUnit('个');
        $this->persistAndFlush($sku);

        // 创建OrderProduct
        $orderProduct = new OrderProduct();
        $orderProduct->setContract($contract);
        $orderProduct->setSku($sku);
        $orderProduct->setSpu($spu);
        $orderProduct->setQuantity(2);
        $orderProduct->setIsGift($isGift);

        if (null !== $source) {
            $orderProduct->setSource($source);
        }

        return $this->persistAndFlush($orderProduct);
    }

    public function testValidateAftersalesType(): void
    {
        // 测试有效的售后类型
        $result = $this->validator->validateAftersalesType('refund_only');
        $this->assertNotNull($result);
    }

    public function testValidateRefundReason(): void
    {
        // 测试有效的退款原因
        $result = $this->validator->validateRefundReason('quality_issue');
        $this->assertNotNull($result);
    }

    public function testValidateAftersalesItemWithGiftProduct(): void
    {
        // 创建测试合同和赠品商品
        $contract = $this->createTestContract();
        $giftProduct = $this->createTestOrderProduct($contract, isGift: true);

        $item = [
            'orderProductId' => (string) $giftProduct->getId(),
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('赠品不允许售后，如有疑问请联系客服');

        $this->validator->validateAftersalesItem(
            $contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }

    public function testValidateAftersalesItemWithNormalProduct(): void
    {
        // 创建测试合同和正常商品
        $contract = $this->createTestContract();
        $normalProduct = $this->createTestOrderProduct($contract, isGift: false);

        $item = [
            'orderProductId' => (string) $normalProduct->getId(),
            'quantity' => 1,
        ];

        // 正常商品应该能够通过验证
        $result = $this->validator->validateAftersalesItem(
            $contract,
            $item,
            0,
            [] // 无活跃售后
        );

        $this->assertSame($normalProduct->getId(), $result->getId());
    }

    public function testValidateAftersalesItemWithCouponGiftProduct(): void
    {
        // 创建测试合同和满赠赠品商品
        $contract = $this->createTestContract();
        $couponGiftProduct = $this->createTestOrderProduct($contract, isGift: true, source: 'coupon_gift');

        $item = [
            'orderProductId' => (string) $couponGiftProduct->getId(),
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('赠品不允许售后，如有疑问请联系客服');

        $this->validator->validateAftersalesItem(
            $contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }

    public function testValidateAftersalesItemWithCouponRedeemProduct(): void
    {
        // 创建测试合同和兑换券赠品商品
        $contract = $this->createTestContract();
        $couponRedeemProduct = $this->createTestOrderProduct($contract, isGift: true, source: 'coupon_redeem');

        $item = [
            'orderProductId' => (string) $couponRedeemProduct->getId(),
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('赠品不允许售后，如有疑问请联系客服');

        $this->validator->validateAftersalesItem(
            $contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }

    public function testValidateContractWithNormalProductsOnly(): void
    {
        $contract = $this->createTestContract();
        $contractId = (string) $contract->getId();
        $items = [
            ['orderProductId' => '1', 'quantity' => 1],
            ['orderProductId' => '2', 'quantity' => 2],
        ];

        // 正常情况下不应该抛出异常
        $this->validator->validateContract($contractId, $items, $contract, $this->user);

        $this->assertTrue(true); // 通过测试
    }

    public function testValidateAftersalesItemWithNonExistentProduct(): void
    {
        $contract = $this->createTestContract();

        $item = [
            'orderProductId' => '999999',
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('商品不属于此订单: 999999');

        $this->validator->validateAftersalesItem(
            $contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }

    public function testValidateAftersalesItemWithProductFromDifferentContract(): void
    {
        // 创建两个不同的合同
        $contract = $this->createTestContract();
        $otherContract = $this->createTestContract();

        // 在另一个合同中创建商品
        $productFromOtherContract = $this->createTestOrderProduct($otherContract, isGift: false);

        $item = [
            'orderProductId' => (string) $productFromOtherContract->getId(),
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('商品不属于此订单: ' . $productFromOtherContract->getId());

        $this->validator->validateAftersalesItem(
            $contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }
}