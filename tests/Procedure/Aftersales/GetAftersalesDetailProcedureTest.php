<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Param\Aftersales\GetAftersalesDetailParam;
use Tourze\OrderRefundBundle\Procedure\Aftersales\GetAftersalesDetailProcedure;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(GetAftersalesDetailProcedure::class)]
#[RunTestsInSeparateProcesses]
final class GetAftersalesDetailProcedureTest extends AbstractProcedureTestCase
{
    private GetAftersalesDetailProcedure $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(GetAftersalesDetailProcedure::class);
    }

    /**
     * 创建测试用售后数据
     */
    private function createTestAftersales(UserInterface $user): Aftersales
    {
        $aftersales = new Aftersales();
        $aftersales->setType(AftersalesType::REFUND_ONLY);
        $aftersales->setReferenceNumber('TEST-REF-001');
        $aftersales->setUser($user);
        $aftersales->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales->setDescription('Test aftersales');
        $aftersales->setProofImages([]);
        $aftersales->setState(AftersalesState::PENDING_APPROVAL);
        $aftersales->setStage(AftersalesStage::APPLY);
        $aftersales->setOrderProductId('order-product-1');
        $aftersales->setProductId('product-1');
        $aftersales->setProductName('Test Product');
        $aftersales->setSkuId('sku-1');
        $aftersales->setSkuName('Test SKU');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('90.00');
        $aftersales->setRefundAmount('90.00');
        $aftersales->setOriginalRefundAmount('90.00');
        $aftersales->setActualRefundAmount('90.00');
        $aftersales->setRefundAmountModified(false);
        $aftersales->setProductSnapshot([
            'productMainImage' => 'https://example.com/image.jpg',
            'skuMainImage' => 'https://example.com/sku-image.jpg',
            'originalPrice' => '100.00',
            'paidPrice' => '90.00',
        ]);

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        return $aftersales;
    }

    public function testExecuteSuccess(): void
    {
        // 创建并设置认证用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($user);

        // 创建真实售后数据
        $aftersales = $this->createTestAftersales($user);

        // 设置参数并执行
        $param = new GetAftersalesDetailParam(id: (string) $aftersales->getId());
        $result = $this->procedure->execute($param);

        // 验证返回结果
        $resultArray = $result->toArray();
        $this->assertIsArray($resultArray);
        $this->assertArrayHasKey('id', $resultArray);
        $this->assertEquals((string) $aftersales->getId(), $resultArray['id']);
        $this->assertEquals('TEST-REF-001', $resultArray['referenceNumber']);
        $this->assertEquals('Test Product', $resultArray['productName']);
        $this->assertEquals('pending_approval', $resultArray['state']);
    }

    public function testExecuteWithUserNotLoggedIn(): void
    {
        // 不设置认证用户，模拟未登录状态
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('用户未登录或类型错误');

        // 设置参数并执行，应该抛出异常
        $param = new GetAftersalesDetailParam(id: '999');
        $this->procedure->execute($param);
    }

    public function testExecuteWithAftersalesNotFound(): void
    {
        // 创建并设置认证用户
        $user = $this->createNormalUser('test@example.com', 'password123');
        $this->setAuthenticatedUser($user);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('售后单不存在');

        // 使用不存在的售后单ID
        $param = new GetAftersalesDetailParam(id: '999999');
        $this->procedure->execute($param);
    }

    public function testExecuteWithUnauthorizedAccess(): void
    {
        // 创建两个用户
        $user1 = $this->createNormalUser('user1@example.com', 'password123');
        $user2 = $this->createNormalUser('user2@example.com', 'password123');

        // 创建属于用户1的售后单
        $aftersales = $this->createTestAftersales($user1);

        // 使用用户2登录
        $this->setAuthenticatedUser($user2);

        // 尝试访问用户1的售后单，应该抛出异常
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无权限访问此售后单');

        $param = new GetAftersalesDetailParam(id: (string) $aftersales->getId());
        $this->procedure->execute($param);
    }
}
