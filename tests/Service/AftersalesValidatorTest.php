<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use BizUserBundle\Entity\BizUser;
use InvalidArgumentException;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Repository\OrderProductRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Service\AftersalesValidator;

/**
 * @internal
 */
#[CoversClass(AftersalesValidator::class)]
final class AftersalesValidatorTest extends TestCase
{
    private OrderProductRepository&MockObject $orderProductRepository;

    private AftersalesValidator $validator;

    protected function setUp(): void
    {
        $this->orderProductRepository = $this->createMock(OrderProductRepository::class);
        $this->validator = new AftersalesValidator($this->orderProductRepository);
    }

    public function testValidateAftersalesTypeSuccess(): void
    {
        $result = $this->validator->validateAftersalesType('refund_only');
        $this->assertSame(AftersalesType::REFUND_ONLY, $result);

        $result = $this->validator->validateAftersalesType('return_refund');
        $this->assertSame(AftersalesType::RETURN_REFUND, $result);

        $result = $this->validator->validateAftersalesType('exchange');
        $this->assertSame(AftersalesType::EXCHANGE, $result);
    }

    public function testValidateAftersalesTypeNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('售后类型不能为空');

        $this->validator->validateAftersalesType(null);
    }

    public function testValidateAftersalesTypeInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的售后类型');

        $this->validator->validateAftersalesType('invalid');
    }

    public function testValidateRefundReasonSuccess(): void
    {
        $result = $this->validator->validateRefundReason('quality_issue');
        $this->assertSame(RefundReason::QUALITY_ISSUE, $result);

        $result = $this->validator->validateRefundReason('dont_want');
        $this->assertSame(RefundReason::DONT_WANT, $result);
    }

    public function testValidateRefundReasonNull(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('退款原因不能为空');

        $this->validator->validateRefundReason(null);
    }

    public function testValidateRefundReasonInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无效的退款原因');

        $this->validator->validateRefundReason('invalid_reason');
    }

    public function testValidateContractSuccess(): void
    {
        $contract = $this->createMock(Contract::class);
        $user = $this->createMock(BizUser::class);
        $contract->method('getUser')->willReturn($user);

        $contractId = 'CONTRACT123';
        $items = [['orderProductId' => '1', 'quantity' => 2]];

        // Should not throw exception
        $this->validator->validateContract($contractId, $items, $contract, $user);
        $this->assertTrue(true);
    }

    public function testValidateContractEmptyId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('订单ID不能为空');

        $contract = $this->createMock(Contract::class);
        $user = $this->createMock(BizUser::class);

        $this->validator->validateContract('', [['orderProductId' => '1', 'quantity' => 2]], $contract, $user);
    }

    public function testValidateContractEmptyItems(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('售后商品列表不能为空');

        $contract = $this->createMock(Contract::class);
        $user = $this->createMock(BizUser::class);

        $this->validator->validateContract('CONTRACT123', [], $contract, $user);
    }

    public function testValidateContractUserMismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无权操作此订单');

        $contractUser = $this->createMock(BizUser::class);
        $requestUser = $this->createMock(BizUser::class);

        $contract = $this->createMock(Contract::class);
        $contract->method('getUser')->willReturn($contractUser);

        $this->validator->validateContract('CONTRACT123', [['orderProductId' => '1', 'quantity' => 2]], $contract, $requestUser);
    }

    public function testValidateAftersalesItemSuccess(): void
    {
        $contract = $this->createMock(Contract::class);
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->method('getQuantity')->willReturn(10);
        $orderProduct->method('getContract')->willReturn($contract);

        $item = ['orderProductId' => '1', 'quantity' => 5];

        $this->orderProductRepository
            ->method('find')
            ->with('1')
            ->willReturn($orderProduct)
        ;

        $result = $this->validator->validateAftersalesItem($contract, $item, 0, []);

        $this->assertSame($orderProduct, $result);
    }

    public function testValidateAftersalesItemMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('第 1 个商品项目格式不正确，缺少必要字段');

        $contract = $this->createMock(Contract::class);
        $item = ['orderProductId' => '1']; // Missing quantity

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateAftersalesItemActiveAftersales(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品 1 已存在售后申请（状态：pending, processing），无法重复申请');

        $contract = $this->createMock(Contract::class);
        $item = ['orderProductId' => '1', 'quantity' => 2];

        /** @var array<string, array<string>> $activeAftersales */
        $activeAftersales = [];
        $activeAftersales['1'] = ['pending', 'processing'];

        /** @phpstan-ignore argument.type */
        $this->validator->validateAftersalesItem($contract, $item, 0, $activeAftersales);
    }

    public function testValidateAftersalesItemZeroQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品 1 的数量必须大于0');

        $contract = $this->createMock(Contract::class);
        $item = ['orderProductId' => '1', 'quantity' => 0];

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateAftersalesItemProductNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品不属于此订单: 1');

        $contract = $this->createMock(Contract::class);
        $item = ['orderProductId' => '1', 'quantity' => 2];

        $this->orderProductRepository
            ->method('find')
            ->with('1')
            ->willReturn(null)
        ;

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateAftersalesItemQuantityExceeded(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品 1 申请数量(10)超过订单数量(5)');

        $contract = $this->createMock(Contract::class);
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->method('getQuantity')->willReturn(5);
        $orderProduct->method('getContract')->willReturn($contract);

        $item = ['orderProductId' => '1', 'quantity' => 10];

        $this->orderProductRepository
            ->method('find')
            ->with('1')
            ->willReturn($orderProduct)
        ;

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateAftersalesItemInvalidQuantityFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品数量格式错误');

        $contract = $this->createMock(Contract::class);
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->method('getQuantity')->willReturn(5);
        $orderProduct->method('getContract')->willReturn($contract);

        $item = ['orderProductId' => '1', 'quantity' => 'invalid'];

        $this->orderProductRepository
            ->method('find')
            ->with('1')
            ->willReturn($orderProduct)
        ;

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateQuantityWithStringNumber(): void
    {
        $contract = $this->createMock(Contract::class);
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->method('getQuantity')->willReturn(10);
        $orderProduct->method('getContract')->willReturn($contract);

        $this->orderProductRepository
            ->method('find')
            ->with('1')
            ->willReturn($orderProduct)
        ;

        // Test string number is accepted
        $item = ['orderProductId' => '1', 'quantity' => '5'];

        $result = $this->validator->validateAftersalesItem($contract, $item, 0, []);

        $this->assertSame($orderProduct, $result);
    }
}
