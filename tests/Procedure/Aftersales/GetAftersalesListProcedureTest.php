<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\JsonRPC\Core\Result\ArrayResult;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Param\Aftersales\GetAftersalesListParam;
use Tourze\OrderRefundBundle\Procedure\Aftersales\GetAftersalesListProcedure;
use Tourze\PHPUnitJsonRPC\AbstractProcedureTestCase;

/**
 * @internal
 */
#[CoversClass(GetAftersalesListProcedure::class)]
#[RunTestsInSeparateProcesses]
final class GetAftersalesListProcedureTest extends AbstractProcedureTestCase
{
    private GetAftersalesListProcedure $procedure;

    private EntityManagerInterface $em;

    private UserInterface $testUser;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(GetAftersalesListProcedure::class);
        $this->em = self::getService(EntityManagerInterface::class);

        // 创建测试用户
        $this->testUser = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $this->setAuthenticatedUser($this->testUser);
    }

    public function testExecuteReturnsAftersalesList(): void
    {
        // 创建真实的售后数据
        $aftersales = $this->createAftersales(
            user: $this->testUser,
            type: AftersalesType::REFUND_ONLY,
            state: AftersalesState::PENDING_APPROVAL,
            reason: RefundReason::QUALITY_ISSUE,
            description: 'Test description',
            refundAmount: '100.00'
        );

        // 执行测试
        $param = new GetAftersalesListParam();
        $resultObject = $this->procedure->execute($param);

        // 验证结果
        $this->assertInstanceOf(ArrayResult::class, $resultObject);
        $result = $resultObject->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey('pagination', $result);

        $pagination = $result['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertEquals(1, $pagination['total']);

        $items = $result['list'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);

        // 验证返回的售后数据
        $item = $items[0];
        $this->assertEquals($aftersales->getId(), $item['id']);
        $this->assertEquals(AftersalesType::REFUND_ONLY->value, $item['type']);
        $this->assertEquals(RefundReason::QUALITY_ISSUE->value, $item['reason']);
        $this->assertEquals(AftersalesState::PENDING_APPROVAL->value, $item['state']);
        $this->assertEquals('Test description', $item['description']);
    }

    public function testExecuteWithStateFilter(): void
    {
        // 创建不同状态的售后数据
        $this->createAftersales(
            user: $this->testUser,
            type: AftersalesType::REFUND_ONLY,
            state: AftersalesState::PENDING_APPROVAL,
            reason: RefundReason::QUALITY_ISSUE,
            description: 'Pending approval',
            refundAmount: '100.00'
        );

        $this->createAftersales(
            user: $this->testUser,
            type: AftersalesType::REFUND_ONLY,
            state: AftersalesState::APPROVED,
            reason: RefundReason::QUALITY_ISSUE,
            description: 'Approved',
            refundAmount: '200.00'
        );

        // 只查询已批准的售后
        $param = new GetAftersalesListParam(state: AftersalesState::APPROVED);
        $resultObject = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $resultObject);
        $result = $resultObject->toArray();
        $this->assertIsArray($result);
        $items = $result['list'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertEquals(AftersalesState::APPROVED->value, $items[0]['state']);
    }

    public function testExecuteWithTypeFilter(): void
    {
        // 创建不同类型的售后数据
        $this->createAftersales(
            user: $this->testUser,
            type: AftersalesType::REFUND_ONLY,
            state: AftersalesState::PENDING_APPROVAL,
            reason: RefundReason::QUALITY_ISSUE,
            description: 'Refund only',
            refundAmount: '100.00'
        );

        $this->createAftersales(
            user: $this->testUser,
            type: AftersalesType::RETURN_REFUND,
            state: AftersalesState::PENDING_APPROVAL,
            reason: RefundReason::QUALITY_ISSUE,
            description: 'Return and refund',
            refundAmount: '200.00'
        );

        // 只查询仅退款类型的售后
        $param = new GetAftersalesListParam(type: AftersalesType::REFUND_ONLY);
        $resultObject = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $resultObject);
        $result = $resultObject->toArray();
        $this->assertIsArray($result);
        $items = $result['list'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertEquals(AftersalesType::REFUND_ONLY->value, $items[0]['type']);
    }

    public function testExecuteWithPagination(): void
    {
        // 创建 15 条售后数据
        for ($i = 1; $i <= 15; ++$i) {
            $this->createAftersales(
                user: $this->testUser,
                type: AftersalesType::REFUND_ONLY,
                state: AftersalesState::PENDING_APPROVAL,
                reason: RefundReason::QUALITY_ISSUE,
                description: "Test aftersales {$i}",
                refundAmount: '100.00'
            );
        }

        // 查询第 2 页，每页 5 条
        $param = new GetAftersalesListParam(page: 2, limit: 5);
        $resultObject = $this->procedure->execute($param);

        $this->assertInstanceOf(ArrayResult::class, $resultObject);
        $result = $resultObject->toArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('pagination', $result);
        $pagination = $result['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('page', $pagination);
        $this->assertArrayHasKey('limit', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('pages', $pagination);
        $this->assertEquals(2, $pagination['page']);
        $this->assertEquals(5, $pagination['limit']);
        $this->assertEquals(15, $pagination['total']);
        $this->assertEquals(3, $pagination['pages']);

        // 验证返回了 5 条数据
        $items = $result['list'];
        $this->assertCount(5, $items);
    }

    /**
     * 创建售后实体的辅助方法
     */
    private function createAftersales(
        UserInterface $user,
        AftersalesType $type,
        AftersalesState $state,
        RefundReason $reason,
        string $description,
        string $refundAmount,
    ): Aftersales {
        $aftersales = new Aftersales();
        $aftersales->setUser($user);
        $aftersales->setType($type);
        $aftersales->setState($state);
        $aftersales->setStage(AftersalesStage::APPLY);
        $aftersales->setReason($reason);
        $aftersales->setDescription($description);
        $aftersales->setReferenceNumber('REF' . uniqid('', true));
        $aftersales->setOrderProductId('OP' . uniqid('', true));
        $aftersales->setProductId('P' . uniqid('', true));
        $aftersales->setSkuId('SKU' . uniqid('', true));
        $aftersales->setProductName('Test Product');
        $aftersales->setSkuName('Test SKU');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('100.00');
        $aftersales->setRefundAmount($refundAmount);
        $aftersales->setOriginalRefundAmount($refundAmount);
        $aftersales->setActualRefundAmount($refundAmount);
        $aftersales->setProofImages([]);
        $aftersales->setProductSnapshot([
            'skuMainImage' => 'https://example.com/image.jpg',
            'paidPrice' => '100.00',
        ]);

        $this->em->persist($aftersales);
        $this->em->flush();

        return $aftersales;
    }
}
