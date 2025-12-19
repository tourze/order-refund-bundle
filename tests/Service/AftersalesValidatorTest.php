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
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Service\AftersalesValidator;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(AftersalesValidator::class)]
#[RunTestsInSeparateProcesses]
final class AftersalesValidatorTest extends AbstractIntegrationTestCase
{
    private AftersalesValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = self::getService(AftersalesValidator::class);
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
        $user = $this->createNormalUser();

        $contract = new Contract();
        $contract->setSn('TEST-CONTRACT-123');
        $contract->setState(OrderState::INIT);
        $contract->setUser($user);
        $contract = $this->persistAndFlush($contract);

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

        $user = $this->createNormalUser();
        $contract = new Contract();
        $contract->setSn('TEST-CONTRACT-124');
        $contract->setState(OrderState::INIT);
        $contract->setUser($user);
        $contract = $this->persistAndFlush($contract);

        $this->validator->validateContract('', [['orderProductId' => '1', 'quantity' => 2]], $contract, $user);
    }

    public function testValidateContractEmptyItems(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('售后商品列表不能为空');

        $user = $this->createNormalUser();
        $contract = new Contract();
        $contract->setSn('TEST-CONTRACT-125');
        $contract->setState(OrderState::INIT);
        $contract->setUser($user);
        $contract = $this->persistAndFlush($contract);

        $this->validator->validateContract('CONTRACT123', [], $contract, $user);
    }

    public function testValidateContractUserMismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('无权操作此订单');

        $contractUser = $this->createNormalUser('contract-user');
        $requestUser = $this->createNormalUser('request-user');

        $contract = new Contract();
        $contract->setSn('TEST-CONTRACT-126');
        $contract->setState(OrderState::INIT);
        $contract->setUser($contractUser);
        $contract = $this->persistAndFlush($contract);

        $this->validator->validateContract('CONTRACT123', [['orderProductId' => '1', 'quantity' => 2]], $contract, $requestUser);
    }

    public function testValidateAftersalesItemSuccess(): void
    {
        $user = $this->createNormalUser();
        $contract = $this->createTestContract($user);
        $orderProduct = $this->createTestOrderProduct($contract, 10);

        $item = ['orderProductId' => (string) $orderProduct->getId(), 'quantity' => 5];

        $result = $this->validator->validateAftersalesItem($contract, $item, 0, []);

        $this->assertSame($orderProduct->getId(), $result->getId());
    }

    public function testValidateAftersalesItemMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('第 1 个商品项目格式不正确，缺少必要字段');

        $user = $this->createNormalUser();
        $contract = $this->createTestContract($user);
        $item = ['orderProductId' => '1']; // Missing quantity

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateAftersalesItemActiveAftersales(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品 1 已存在售后申请（状态：pending, processing），无法重复申请');

        $user = $this->createNormalUser();
        $contract = $this->createTestContract($user);
        $item = ['orderProductId' => '1', 'quantity' => 2];

        $activeAftersales = [];
        $activeAftersales['1'] = ['pending', 'processing'];

        $this->validator->validateAftersalesItem($contract, $item, 0, $activeAftersales);
    }

    public function testValidateAftersalesItemZeroQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品 1 的数量必须大于0');

        $user = $this->createNormalUser();
        $contract = $this->createTestContract($user);
        $item = ['orderProductId' => '1', 'quantity' => 0];

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateAftersalesItemProductNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品不属于此订单: 999999');

        $user = $this->createNormalUser();
        $contract = $this->createTestContract($user);
        $item = ['orderProductId' => '999999', 'quantity' => 2];

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateAftersalesItemQuantityExceeded(): void
    {
        $user = $this->createNormalUser();
        $contract = $this->createTestContract($user);
        $orderProduct = $this->createTestOrderProduct($contract, 5);

        $item = ['orderProductId' => (string) $orderProduct->getId(), 'quantity' => 10];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品 ' . $orderProduct->getId() . ' 申请数量(10)超过订单数量(5)');

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateAftersalesItemInvalidQuantityFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('商品数量格式错误');

        $user = $this->createNormalUser();
        $contract = $this->createTestContract($user);
        $orderProduct = $this->createTestOrderProduct($contract, 5);

        $item = ['orderProductId' => (string) $orderProduct->getId(), 'quantity' => 'invalid'];

        $this->validator->validateAftersalesItem($contract, $item, 0, []);
    }

    public function testValidateQuantityWithStringNumber(): void
    {
        $user = $this->createNormalUser();
        $contract = $this->createTestContract($user);
        $orderProduct = $this->createTestOrderProduct($contract, 10);

        // Test string number is accepted
        $item = ['orderProductId' => (string) $orderProduct->getId(), 'quantity' => '5'];

        $result = $this->validator->validateAftersalesItem($contract, $item, 0, []);

        $this->assertSame($orderProduct->getId(), $result->getId());
    }

    private function createTestContract(UserInterface $user): Contract
    {
        $contract = new Contract();
        $contract->setSn('TEST-CONTRACT-' . uniqid());
        $contract->setState(OrderState::INIT);
        $contract->setUser($user);

        return $this->persistAndFlush($contract);
    }

    private function createTestOrderProduct(Contract $contract, int $quantity): OrderProduct
    {
        $spu = new Spu();
        $spu->setTitle('测试商品');
        $spu->setValid(true);
        $spu = $this->persistAndFlush($spu);

        $sku = new Sku();
        $sku->setSpu($spu);
        $sku->setTitle('测试SKU');
        $sku->setValid(true);
        $sku->setUnit('件');
        $sku = $this->persistAndFlush($sku);

        $orderProduct = new OrderProduct();
        $orderProduct->setContract($contract);
        $orderProduct->setSku($sku);
        $orderProduct->setValid(true);
        $orderProduct->setQuantity($quantity);

        return $this->persistAndFlush($orderProduct);
    }
}
