<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use BizUserBundle\Entity\BizUser;
use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Repository\ContractRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\OrderRefundBundle\Procedure\Aftersales\ApplyAftersalesProcedure;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Service\AftersalesDataBuilder;
use Tourze\OrderRefundBundle\Service\AftersalesService;
use Tourze\OrderRefundBundle\Service\AftersalesValidator;

class ApplyAftersalesProcedureGiftTest extends TestCase
{
    private ApplyAftersalesProcedure $procedure;
    private Security|MockObject $security;
    private AftersalesService|MockObject $aftersalesService;
    private ContractRepository|MockObject $contractRepository;
    private AftersalesRepository|MockObject $aftersalesRepository;
    private AftersalesValidator|MockObject $validator;
    private AftersalesDataBuilder|MockObject $dataBuilder;
    private BizUser|MockObject $user;
    private Contract|MockObject $contract;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->aftersalesService = $this->createMock(AftersalesService::class);
        $this->contractRepository = $this->createMock(ContractRepository::class);
        $this->aftersalesRepository = $this->createMock(AftersalesRepository::class);
        $this->validator = $this->createMock(AftersalesValidator::class);
        $this->dataBuilder = $this->createMock(AftersalesDataBuilder::class);
        $this->user = $this->createMock(BizUser::class);
        $this->contract = $this->createMock(Contract::class);

        $this->procedure = new ApplyAftersalesProcedure(
            $this->security,
            $this->aftersalesService,
            $this->contractRepository,
            $this->aftersalesRepository,
            $this->validator,
            $this->dataBuilder
        );
    }

    public function testApplyAftersalesWithGiftProduct(): void
    {
        // 设置测试数据
        $this->procedure->contractId = '12345';
        $this->procedure->type = 'return';
        $this->procedure->reason = 'quality_issue';
        $this->procedure->description = '商品有质量问题';
        $this->procedure->proofImages = [];
        $this->procedure->items = [
            [
                'orderProductId' => '123',
                'quantity' => 1,
            ]
        ];

        // Mock 用户
        $this->security->method('getUser')->willReturn($this->user);

        // Mock 验证器 - 类型和原因验证通过
        $this->validator->method('validateAftersalesType')->willReturn(
            $this->createMock(\Tourze\OrderRefundBundle\Enum\AftersalesType::class)
        );
        $this->validator->method('validateRefundReason')->willReturn(
            $this->createMock(\Tourze\OrderRefundBundle\Enum\RefundReason::class)
        );

        // Mock 合同查找
        $this->contractRepository->method('find')->with('12345')->willReturn($this->contract);

        // Mock 合同验证通过
        $this->validator->method('validateContract')->willReturn();

        // Mock 查找活跃售后
        $this->aftersalesRepository->method('findActiveAftersalesByOrderProductIds')->willReturn([]);

        // Mock 商品验证 - 这里会抛出赠品异常
        $this->validator->method('validateAftersalesItem')
            ->willThrowException(new \InvalidArgumentException('赠品不允许售后，如有疑问请联系客服'));

        // 期望抛出 API 异常
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('创建商品 123 的售后单失败: 赠品不允许售后，如有疑问请联系客服');

        $this->procedure->execute();
    }

    public function testApplyAftersalesWithNormalProduct(): void
    {
        // 设置测试数据
        $this->procedure->contractId = '12345';
        $this->procedure->type = 'return';
        $this->procedure->reason = 'quality_issue';
        $this->procedure->description = '商品有质量问题';
        $this->procedure->proofImages = [];
        $this->procedure->items = [
            [
                'orderProductId' => '123',
                'quantity' => 1,
            ]
        ];

        // Mock 正常商品
        $normalProduct = $this->createMock(OrderProduct::class);
        $normalProduct->method('isGift')->willReturn(false);

        // Mock 用户
        $this->security->method('getUser')->willReturn($this->user);

        // Mock 验证器 - 类型和原因验证通过
        $aftersalesType = $this->createMock(\Tourze\OrderRefundBundle\Enum\AftersalesType::class);
        $refundReason = $this->createMock(\Tourze\OrderRefundBundle\Enum\RefundReason::class);
        $this->validator->method('validateAftersalesType')->willReturn($aftersalesType);
        $this->validator->method('validateRefundReason')->willReturn($refundReason);

        // Mock 合同查找
        $this->contractRepository->method('find')->with('12345')->willReturn($this->contract);

        // Mock 合同验证通过
        $this->validator->method('validateContract')->willReturn();

        // Mock 基础订单数据
        $baseOrderData = ['orderId' => '12345', 'userId' => 'user123'];
        $this->dataBuilder->method('buildBaseOrderData')->willReturn($baseOrderData);

        // Mock 查找活跃售后
        $this->aftersalesRepository->method('findActiveAftersalesByOrderProductIds')->willReturn([]);

        // Mock 商品验证通过
        $this->validator->method('validateAftersalesItem')->willReturn($normalProduct);

        // Mock 商品数据构建
        $productData = ['productId' => '123', 'productName' => '测试商品'];
        $this->dataBuilder->method('buildProductData')->willReturn($productData);

        // Mock 售后服务创建
        $aftersales = $this->createMock(\Tourze\OrderRefundBundle\Entity\Aftersales::class);
        $this->aftersalesService->method('createFromArray')->willReturn($aftersales);

        // Mock 响应构建
        $aftersalesResponse = ['id' => '456', 'status' => 'pending'];
        $this->dataBuilder->method('buildAftersalesResponse')->willReturn($aftersalesResponse);

        // Mock 最终结果构建
        $finalResult = [
            'success' => true,
            'aftersales' => [$aftersalesResponse],
            'errors' => []
        ];
        $this->dataBuilder->method('buildFinalResult')->willReturn($finalResult);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('aftersales', $result);
        $this->assertNotEmpty($result['aftersales']);
    }

    public function testApplyAftersalesWithMixedProducts(): void
    {
        // 测试包含正常商品和赠品的混合场景
        $this->procedure->contractId = '12345';
        $this->procedure->type = 'return';
        $this->procedure->reason = 'quality_issue';
        $this->procedure->description = '商品有质量问题';
        $this->procedure->proofImages = [];
        $this->procedure->items = [
            [
                'orderProductId' => '123', // 正常商品
                'quantity' => 1,
            ],
            [
                'orderProductId' => '456', // 赠品
                'quantity' => 1,
            ]
        ];

        // Mock 正常商品
        $normalProduct = $this->createMock(OrderProduct::class);
        $normalProduct->method('isGift')->willReturn(false);

        // Mock 用户
        $this->security->method('getUser')->willReturn($this->user);

        // Mock 验证器
        $aftersalesType = $this->createMock(\Tourze\OrderRefundBundle\Enum\AftersalesType::class);
        $refundReason = $this->createMock(\Tourze\OrderRefundBundle\Enum\RefundReason::class);
        $this->validator->method('validateAftersalesType')->willReturn($aftersalesType);
        $this->validator->method('validateRefundReason')->willReturn($refundReason);

        // Mock 合同
        $this->contractRepository->method('find')->willReturn($this->contract);
        $this->validator->method('validateContract')->willReturn();

        // Mock 基础数据
        $baseOrderData = ['orderId' => '12345'];
        $this->dataBuilder->method('buildBaseOrderData')->willReturn($baseOrderData);

        // Mock 查找活跃售后
        $this->aftersalesRepository->method('findActiveAftersalesByOrderProductIds')->willReturn([]);

        // Mock 商品验证 - 第一个通过，第二个失败
        $this->validator->method('validateAftersalesItem')
            ->willReturnCallback(function($contract, $item) use ($normalProduct) {
                if ($item['orderProductId'] === '123') {
                    return $normalProduct; // 正常商品
                } else {
                    throw new \InvalidArgumentException('赠品不允许售后，如有疑问请联系客服'); // 赠品
                }
            });

        // Mock 其他必要的服务
        $productData = ['productId' => '123'];
        $this->dataBuilder->method('buildProductData')->willReturn($productData);

        $aftersales = $this->createMock(\Tourze\OrderRefundBundle\Entity\Aftersales::class);
        $this->aftersalesService->method('createFromArray')->willReturn($aftersales);

        $aftersalesResponse = ['id' => '789', 'status' => 'pending'];
        $this->dataBuilder->method('buildAftersalesResponse')->willReturn($aftersalesResponse);

        // Mock 最终结果 - 部分成功，部分失败
        $finalResult = [
            'success' => true,
            'aftersales' => [$aftersalesResponse],
            'errors' => ['创建商品 456 的售后单失败: 赠品不允许售后，如有疑问请联系客服']
        ];
        $this->dataBuilder->method('buildFinalResult')->willReturn($finalResult);

        $result = $this->procedure->execute();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('aftersales', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('赠品不允许售后', $result['errors'][0]);
    }
}