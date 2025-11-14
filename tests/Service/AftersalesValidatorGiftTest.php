<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use InvalidArgumentException;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Repository\OrderProductRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderRefundBundle\Service\AftersalesValidator;

#[CoversClass(AftersalesValidator::class)]
class AftersalesValidatorGiftTest extends TestCase
{
    private AftersalesValidator $validator;
    private OrderProductRepository|MockObject $orderProductRepository;
    private Contract|MockObject $contract;
    private UserInterface|MockObject $user;

    protected function setUp(): void
    {
        $this->orderProductRepository = $this->createMock(OrderProductRepository::class);
        $this->validator = new AftersalesValidator($this->orderProductRepository);
        $this->contract = $this->createMock(Contract::class);
        $this->user = $this->createMock(UserInterface::class);
    }

    public function testValidateAftersalesItemWithGiftProduct(): void
    {
        // 创建一个赠品商品
        $giftProduct = $this->createMock(OrderProduct::class);
        $giftProduct->method('getContract')->willReturn($this->contract);
        $giftProduct->method('isGift')->willReturn(true); // 这是赠品
        $giftProduct->method('getQuantity')->willReturn(2);

        $this->orderProductRepository
            ->method('find')
            ->with('123')
            ->willReturn($giftProduct);

        $item = [
            'orderProductId' => '123',
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('赠品不允许售后，如有疑问请联系客服');

        $this->validator->validateAftersalesItem(
            $this->contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }

    public function testValidateAftersalesItemWithNormalProduct(): void
    {
        // 创建一个正常商品
        $normalProduct = $this->createMock(OrderProduct::class);
        $normalProduct->method('getContract')->willReturn($this->contract);
        $normalProduct->method('isGift')->willReturn(false); // 这不是赠品
        $normalProduct->method('getQuantity')->willReturn(2);

        $this->orderProductRepository
            ->method('find')
            ->with('123')
            ->willReturn($normalProduct);

        $item = [
            'orderProductId' => '123',
            'quantity' => 1,
        ];

        // 正常商品应该能够通过验证
        $result = $this->validator->validateAftersalesItem(
            $this->contract,
            $item,
            0,
            [] // 无活跃售后
        );

        $this->assertSame($normalProduct, $result);
    }

    public function testValidateAftersalesItemWithCouponGiftProduct(): void
    {
        // 创建一个满赠赠品商品
        $couponGiftProduct = $this->createMock(OrderProduct::class);
        $couponGiftProduct->method('getContract')->willReturn($this->contract);
        $couponGiftProduct->method('isGift')->willReturn(true); // 这是赠品
        $couponGiftProduct->method('isCouponGift')->willReturn(true); // 满赠赠品
        $couponGiftProduct->method('getQuantity')->willReturn(1);

        $this->orderProductRepository
            ->method('find')
            ->with('456')
            ->willReturn($couponGiftProduct);

        $item = [
            'orderProductId' => '456',
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('赠品不允许售后，如有疑问请联系客服');

        $this->validator->validateAftersalesItem(
            $this->contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }

    public function testValidateAftersalesItemWithCouponRedeemProduct(): void
    {
        // 创建一个兑换券赠品商品
        $couponRedeemProduct = $this->createMock(OrderProduct::class);
        $couponRedeemProduct->method('getContract')->willReturn($this->contract);
        $couponRedeemProduct->method('isGift')->willReturn(true); // 这是赠品
        $couponRedeemProduct->method('isCouponRedeem')->willReturn(true); // 兑换券赠品
        $couponRedeemProduct->method('getQuantity')->willReturn(1);

        $this->orderProductRepository
            ->method('find')
            ->with('789')
            ->willReturn($couponRedeemProduct);

        $item = [
            'orderProductId' => '789',
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('赠品不允许售后，如有疑问请联系客服');

        $this->validator->validateAftersalesItem(
            $this->contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }

    public function testValidateContractWithNormalProductsOnly(): void
    {
        $contractId = '12345';
        $items = [
            ['orderProductId' => '1', 'quantity' => 1],
            ['orderProductId' => '2', 'quantity' => 2],
        ];

        $this->contract->method('getUser')->willReturn($this->user);

        // 正常情况下不应该抛出异常
        $this->validator->validateContract($contractId, $items, $this->contract, $this->user);

        $this->assertTrue(true); // 通过测试
    }

    public function testValidateAftersalesItemWithNonExistentProduct(): void
    {
        $this->orderProductRepository
            ->method('find')
            ->with('999')
            ->willReturn(null);

        $item = [
            'orderProductId' => '999',
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('商品不属于此订单: 999');

        $this->validator->validateAftersalesItem(
            $this->contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }

    public function testValidateAftersalesItemWithProductFromDifferentContract(): void
    {
        $otherContract = $this->createMock(Contract::class);
        $productFromOtherContract = $this->createMock(OrderProduct::class);
        $productFromOtherContract->method('getContract')->willReturn($otherContract);

        $this->orderProductRepository
            ->method('find')
            ->with('888')
            ->willReturn($productFromOtherContract);

        $item = [
            'orderProductId' => '888',
            'quantity' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('商品不属于此订单: 888');

        $this->validator->validateAftersalesItem(
            $this->contract,
            $item,
            0,
            [] // 无活跃售后
        );
    }
}