<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Entity\Price;
use OrderCoreBundle\Enum\OrderState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Service\AftersalesDataBuilder;
use Tourze\OrderRefundBundle\Service\PriceCalculator;
use Tourze\OrderRefundBundle\Service\ProductImageExtractor;
use Tourze\ProductCoreBundle\Entity\Sku;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(AftersalesDataBuilder::class)]
final class AftersalesDataBuilderTest extends TestCase
{
    private AftersalesDataBuilder $dataBuilder;

    protected function setUp(): void
    {
        $this->dataBuilder = new AftersalesDataBuilder(
            new PriceCalculator(),
            new ProductImageExtractor()
        );
    }

    public function testBuildBaseOrderData(): void
    {
        $contract = $this->createMock(Contract::class);
        $contract->method('getSn')->willReturn('ORDER-123');
        $contract->method('getState')->willReturn(OrderState::INIT);
        $contract->method('getPrices')->willReturn(new ArrayCollection());

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('user@test.com');

        $result = $this->dataBuilder->buildBaseOrderData($contract, $user);

        $this->assertIsArray($result);
        $this->assertSame('ORDER-123', $result['orderNumber']);
        $this->assertSame('init', $result['orderStatus']);
        $this->assertSame('user@test.com', $result['userId']);
        $this->assertSame(0.0, $result['totalAmount']);
        $this->assertSame([], $result['extra']);
        $this->assertArrayHasKey('orderCreateTime', $result);
    }

    public function testBuildProductDataWithMinimalData(): void
    {
        $orderProduct = $this->createMock(OrderProduct::class);
        $orderProduct->method('getId')->willReturn(1);
        $orderProduct->method('getQuantity')->willReturn(2);
        $orderProduct->method('getSpu')->willReturn(null);
        $orderProduct->method('getSku')->willReturn(null);
        $prices = new ArrayCollection();
        $orderProduct->method('getPrices')->willReturn($prices);

        $result = $this->dataBuilder->buildProductData($orderProduct);

        $this->assertIsArray($result);
        $this->assertSame('1', $result['productId']);
        $this->assertSame('', $result['skuId']);
        $this->assertSame('未知商品', $result['productName']);
        $this->assertSame('', $result['skuName']);
        $this->assertSame(0.0, $result['originalPrice']);
        $this->assertSame(0.0, $result['paidPrice']);
        $this->assertSame(0.0, $result['unitPrice']);
        $this->assertSame(2, $result['orderQuantity']);
    }

    public function testBuildAftersalesResponse(): void
    {
        // 创建真实的 Aftersales 对象并使用 reflection 设置 ID
        $aftersales = new Aftersales();
        $reflection = new \ReflectionClass($aftersales);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($aftersales, '100');

        // 设置其他必要属性
        $aftersales->setState(AftersalesState::PENDING_APPROVAL);
        $aftersales->setStage(AftersalesStage::APPLY);

        $orderProduct = $this->createMock(OrderProduct::class);
        $spu = $this->createMock(Spu::class);
        $spu->method('getTitle')->willReturn('测试商品');
        $orderProduct->method('getSpu')->willReturn($spu);

        $item = [
            'orderProductId' => '123',
            'quantity' => 3,
        ];

        $result = $this->dataBuilder->buildAftersalesResponse($aftersales, $orderProduct, $item);

        $this->assertIsArray($result);
        $this->assertSame('100', $result['aftersalesId']);
        $this->assertSame('pending_approval', $result['state']);
        $this->assertSame('apply', $result['stage']);
        $this->assertSame('测试商品', $result['productName']);
        $this->assertSame('123', $result['orderProductId']);
        $this->assertSame(3, $result['quantity']);
    }

    public function testBuildFinalResultWithoutErrors(): void
    {
        $aftersalesList = [
            ['id' => 1, 'status' => 'success'],
            ['id' => 2, 'status' => 'success'],
        ];
        $errors = [];

        $result = $this->dataBuilder->buildFinalResult($aftersalesList, $errors);

        $this->assertSame($aftersalesList, $result['aftersalesList']);
        $this->assertSame(2, $result['totalCount']);
        $this->assertSame('售后申请提交完成', $result['message']);
        $this->assertArrayNotHasKey('errors', $result);
    }

    public function testBuildFinalResultWithErrors(): void
    {
        $aftersalesList = [
            ['id' => 1, 'status' => 'success'],
        ];
        $errors = ['商品2处理失败', '库存不足'];

        $result = $this->dataBuilder->buildFinalResult($aftersalesList, $errors);

        $this->assertSame($aftersalesList, $result['aftersalesList']);
        $this->assertSame(1, $result['totalCount']);
        $this->assertSame('售后申请部分成功，部分失败', $result['message']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertSame($errors, $result['errors']);
    }

    public function testBuildFinalResultEmptyList(): void
    {
        $aftersalesList = [];
        $errors = ['全部失败'];

        $result = $this->dataBuilder->buildFinalResult($aftersalesList, $errors);

        $this->assertSame([], $result['aftersalesList']);
        $this->assertSame(0, $result['totalCount']);
        $this->assertSame('售后申请部分成功，部分失败', $result['message']);
        $this->assertSame($errors, $result['errors']);
    }
}
