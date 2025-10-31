<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Service\AftersalesService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesService::class)]
#[RunTestsInSeparateProcesses]
class AftersalesServiceTest extends AbstractIntegrationTestCase
{
    private AftersalesService $aftersalesService;

    protected function onSetUp(): void
    {
        $this->aftersalesService = self::getService(AftersalesService::class);
    }

    public function testFindByReferenceNumber(): void
    {
        $aftersales = $this->createValidAftersales();
        $aftersales->setReferenceNumber('TEST-REF-001');

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $results = $this->aftersalesService->findByReferenceNumber('TEST-REF-001');

        $this->assertNotEmpty($results);
        $this->assertContainsOnlyInstancesOf(Aftersales::class, $results);
    }

    public function testGetAftersalesWithSnapshots(): void
    {
        $aftersales = $this->createValidAftersales();
        $aftersales->setReferenceNumber('TEST-REF-002');

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $aftersalesId = $aftersales->getId();
        $this->assertNotNull($aftersalesId);
        $result = $this->aftersalesService->getAftersalesWithSnapshots($aftersalesId);

        $this->assertInstanceOf(Aftersales::class, $result);
        $this->assertSame($aftersales->getId(), $result->getId());
    }

    public function testGetAftersalesWithSnapshotsNotFound(): void
    {
        $result = $this->aftersalesService->getAftersalesWithSnapshots('non-existent-id');

        $this->assertNull($result);
    }

    public function testBatchProcess(): void
    {
        $aftersales1 = $this->createValidAftersales();
        $aftersales1->setReferenceNumber('TEST-BATCH-001');

        $aftersales2 = $this->createValidAftersales();
        $aftersales2->setReferenceNumber('TEST-BATCH-002');

        self::getEntityManager()->persist($aftersales1);
        self::getEntityManager()->persist($aftersales2);
        self::getEntityManager()->flush();

        $id1 = $aftersales1->getId();
        $id2 = $aftersales2->getId();

        $this->assertNotNull($id1);
        $this->assertNotNull($id2);

        $results = $this->aftersalesService->batchProcess(
            [$id1, $id2],
            'approve'
        );

        $this->assertCount(2, $results);
        $this->assertTrue($results[$aftersales1->getId()]['success']);
        $this->assertTrue($results[$aftersales2->getId()]['success']);
    }

    public function testCalculateTotalRefundAmount(): void
    {
        $aftersales = $this->createValidAftersales();
        $aftersales->setReferenceNumber('TEST-CALC-001');

        $amount = $this->aftersalesService->calculateTotalRefundAmount($aftersales);

        $this->assertIsFloat($amount);
        $this->assertGreaterThanOrEqual(0, $amount);
    }

    public function testCanCreateAftersales(): void
    {
        $orderData = new OrderDataDTO(
            orderNumber: 'TEST-ORDER-001',
            orderStatus: 'paid',
            orderCreateTime: new \DateTime('-5 days'),
            userId: 'user_001',
            totalAmount: 100.0
        );

        $canCreate = $this->aftersalesService->canCreateAftersales($orderData);

        $this->assertIsBool($canCreate);
    }

    public function testCreate(): void
    {
        $orderData = new OrderDataDTO(
            orderNumber: 'TEST-CREATE-001',
            orderStatus: 'paid',
            orderCreateTime: new \DateTime('-5 days'),
            userId: 'user_001',
            totalAmount: 100.0
        );

        $productData = new ProductDataDTO(
            productId: 'PROD-001',
            skuId: 'SKU-001',
            productName: 'Test Product',
            skuName: 'Test SKU',
            originalPrice: 60.0,
            paidPrice: 50.0,
            unitPrice: 25.0,
            discountAmount: 10.0,
            orderQuantity: 2
        );

        $result = $this->aftersalesService->create(
            $orderData,
            $productData,
            'ORDER-PRODUCT-001', // orderProductId
            1, // quantity
            AftersalesType::REFUND_ONLY,
            RefundReason::QUALITY_ISSUE,
            'Test description'
        );

        $this->assertInstanceOf(Aftersales::class, $result);
    }

    public function testCreateFromArray(): void
    {
        $orderData = [
            'orderNumber' => 'TEST-CREATE-ARRAY-001',
            'orderStatus' => 'paid',
            'orderCreateTime' => (new \DateTime('-5 days'))->format('Y-m-d H:i:s'),
            'userId' => 'user_001',
            'totalAmount' => 100.0,
        ];

        $productData = [
            'productId' => 'PROD-001',
            'skuId' => 'SKU-001',
            'productName' => 'Test Product',
            'skuName' => 'Test SKU',
            'originalPrice' => 60.0,
            'paidPrice' => 50.0,
            'discountAmount' => 10.0,
            'orderQuantity' => 2,
        ];

        $result = $this->aftersalesService->createFromArray(
            $orderData,
            $productData,
            'ORDER-PRODUCT-001', // orderProductId
            1, // quantity
            AftersalesType::REFUND_ONLY,
            RefundReason::QUALITY_ISSUE,
            'Test description'
        );

        $this->assertInstanceOf(Aftersales::class, $result);
    }

    public function testModifyRefundAmount(): void
    {
        $aftersales = $this->createValidAftersales();
        $aftersales->setReferenceNumber('TEST-MODIFY-001');
        // 设置为 PENDING_APPROVAL 状态才能修改退款金额
        $aftersales->setState(AftersalesState::PENDING_APPROVAL);

        self::getEntityManager()->persist($aftersales);
        self::getEntityManager()->flush();

        $aftersalesId = $aftersales->getId();
        $this->assertNotNull($aftersalesId);

        $newAmount = '80.00';
        $reason = 'Test modification reason';

        $modifiedAftersales = $this->aftersalesService->modifyRefundAmount($aftersalesId, $newAmount, $reason);

        $this->assertInstanceOf(Aftersales::class, $modifiedAftersales);
        $this->assertSame($newAmount, $modifiedAftersales->getActualRefundAmount());
    }

    private function createValidAftersales(): Aftersales
    {
        $aftersales = new Aftersales();
        $aftersales->setType(AftersalesType::REFUND_ONLY);
        $aftersales->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales->setReferenceNumber('TEST-REF-' . uniqid());
        $aftersales->setOrderProductId('order_product_' . uniqid());
        $aftersales->setProductId('product_' . uniqid());
        $aftersales->setSkuId('sku_' . uniqid());
        $aftersales->setProductName('Test Product Name');
        $aftersales->setSkuName('Test SKU Name');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('90.00');
        $aftersales->setRefundAmount('90.00');
        $aftersales->setOriginalRefundAmount('90.00');
        $aftersales->setActualRefundAmount('90.00');

        return $aftersales;
    }
}
