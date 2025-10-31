<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Service\OmsAftersalesSyncService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(OmsAftersalesSyncService::class)]
#[RunTestsInSeparateProcesses]
class OmsAftersalesSyncServiceTest extends AbstractIntegrationTestCase
{
    private OmsAftersalesSyncService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(OmsAftersalesSyncService::class);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(OmsAftersalesSyncService::class, $this->service);
    }

    public function testSyncFromOmsWithValidData(): void
    {
        $data = [
            'aftersalesNo' => 'AS' . uniqid(),
            'aftersalesType' => 'refund',
            'reason' => 'quality',
            'description' => 'Product defect',
            'refundAmount' => 10000,
            'applicantName' => 'John Doe',
            'applicantPhone' => '13800138000',
            'orderNo' => 'ORD' . uniqid(),
            'status' => 'pending',
            'products' => [
                [
                    'orderProductId' => 'ORDER_PROD_' . uniqid(),
                    'productCode' => 'PROD001',
                    'productName' => 'Test Product',
                    'quantity' => 1,
                    'amount' => 10000,
                    'originalPrice' => '120.00',
                    'paidPrice' => '100.00',
                    'refundAmount' => '100.00',
                ],
            ],
        ];

        $result = $this->service->syncFromOms($data);

        $this->assertInstanceOf(Aftersales::class, $result);
        $this->assertSame($data['aftersalesNo'], $result->getReferenceNumber());
        $this->assertSame(AftersalesType::REFUND_ONLY, $result->getType());
        $this->assertSame(RefundReason::QUALITY_ISSUE, $result->getReason());
    }

    public function testCreateFromOmsWithValidData(): void
    {
        $data = [
            'aftersalesNo' => 'AS' . uniqid(),
            'aftersalesType' => 'refund',
            'reason' => 'quality',
            'description' => 'Product defect',
            'refundAmount' => 10000,
            'applicantName' => 'John Doe',
            'applicantPhone' => '13800138000',
            'orderNo' => 'ORD' . uniqid(),
            'status' => 'pending',
            'products' => [
                [
                    'orderProductId' => 'ORDER_PROD_' . uniqid(),
                    'productCode' => 'PROD001',
                    'productName' => 'Test Product',
                    'quantity' => 1,
                    'amount' => 10000,
                    'originalPrice' => '120.00',
                    'paidPrice' => '100.00',
                    'refundAmount' => '100.00',
                ],
            ],
        ];

        $result = $this->service->createFromOms($data);

        $this->assertInstanceOf(Aftersales::class, $result);
        $this->assertSame($data['aftersalesNo'], $result->getReferenceNumber());
        $this->assertSame(AftersalesType::REFUND_ONLY, $result->getType());
        $this->assertSame(RefundReason::QUALITY_ISSUE, $result->getReason());
        $this->assertSame(AftersalesState::PENDING_APPROVAL, $result->getState());
    }

    public function testUpdateInfoFromOmsWithValidData(): void
    {
        // 先创建一个售后单
        $createData = [
            'aftersalesNo' => 'AS' . uniqid(),
            'aftersalesType' => 'refund',
            'reason' => 'quality',
            'description' => 'Original description',
            'refundAmount' => 10000,
            'applicantName' => 'John Doe',
            'applicantPhone' => '13800138000',
            'orderNo' => 'ORD' . uniqid(),
            'status' => 'pending',
            'products' => [
                [
                    'orderProductId' => 'ORDER_PROD_' . uniqid(),
                    'productCode' => 'PROD001',
                    'productName' => 'Test Product',
                    'quantity' => 1,
                    'amount' => 10000,
                    'originalPrice' => '120.00',
                    'paidPrice' => '100.00',
                    'refundAmount' => '100.00',
                ],
            ],
        ];

        $aftersales = $this->service->createFromOms($createData);

        // 更新售后单信息
        $updateData = [
            'aftersalesNo' => $createData['aftersalesNo'],
            'modifyReason' => 'Customer request',
            'description' => 'Updated description',
            'refundAmount' => 15000,
        ];

        $result = $this->service->updateInfoFromOms($updateData);

        $this->assertInstanceOf(Aftersales::class, $result);
        $this->assertSame($aftersales->getId(), $result->getId());
        $this->assertSame('Updated description', $result->getDescription());
        $this->assertGreaterThan(0, $result->getModificationCount());
    }

    public function testUpdateStatusFromOmsWithValidData(): void
    {
        // 先创建一个售后单
        $createData = [
            'aftersalesNo' => 'AS' . uniqid(),
            'aftersalesType' => 'refund',
            'reason' => 'quality',
            'description' => 'Test description',
            'refundAmount' => 10000,
            'applicantName' => 'John Doe',
            'applicantPhone' => '13800138000',
            'orderNo' => 'ORD' . uniqid(),
            'status' => 'pending',
            'products' => [
                [
                    'orderProductId' => 'ORDER_PROD_' . uniqid(),
                    'productCode' => 'PROD001',
                    'productName' => 'Test Product',
                    'quantity' => 1,
                    'amount' => 10000,
                    'originalPrice' => '120.00',
                    'paidPrice' => '100.00',
                    'refundAmount' => '100.00',
                ],
            ],
        ];

        $aftersales = $this->service->createFromOms($createData);
        $originalState = $aftersales->getState();

        // 更新售后单状态
        $updateData = [
            'aftersalesNo' => $createData['aftersalesNo'],
            'status' => 'approved',
            'auditor' => 'admin',
            'auditTime' => '2024-01-01 12:00:00',
            'auditRemark' => 'Approved for processing',
        ];

        $result = $this->service->updateStatusFromOms($updateData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('aftersales', $result);
        $this->assertArrayHasKey('oldStatus', $result);
        $this->assertInstanceOf(Aftersales::class, $result['aftersales']);
        $this->assertSame($aftersales->getId(), $result['aftersales']->getId());
        $this->assertSame($originalState->value, $result['oldStatus']);
        $this->assertSame(AftersalesState::APPROVED, $result['aftersales']->getState());
    }
}
