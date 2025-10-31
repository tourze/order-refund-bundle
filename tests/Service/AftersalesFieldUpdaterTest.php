<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Service\AftersalesFieldUpdater;

/**
 * 售后字段更新器测试
 * @internal
 */
#[CoversClass(AftersalesFieldUpdater::class)]
class AftersalesFieldUpdaterTest extends TestCase
{
    private AftersalesFieldUpdater $updater;

    protected function setUp(): void
    {
        $this->updater = new AftersalesFieldUpdater();
    }

    public function testUpdateBasicFields(): void
    {
        $aftersales = new Aftersales();
        $data = [
            'description' => '测试描述',
            'applicantName' => '张三',
            'applicantPhone' => '13800138000',
            'refundAmount' => 10000,
        ];

        $modifiedFields = $this->updater->updateBasicFields($aftersales, $data);

        $this->assertSame('测试描述', $aftersales->getDescription());
        $this->assertSame('张三', $aftersales->getApplicantName());
        $this->assertSame('13800138000', $aftersales->getApplicantPhone());
        $this->assertSame(10000, $aftersales->getRequestedAmount());

        $this->assertContains('description', $modifiedFields);
        $this->assertContains('applicantName', $modifiedFields);
        $this->assertContains('applicantPhone', $modifiedFields);
        $this->assertContains('refundAmount', $modifiedFields);
    }

    public function testUpdateBasicFieldsWithEmptyData(): void
    {
        $aftersales = new Aftersales();
        $data = [];

        $modifiedFields = $this->updater->updateBasicFields($aftersales, $data);

        $this->assertEmpty($modifiedFields);
    }

    public function testUpdateAdditionalFieldsWithProducts(): void
    {
        $aftersales = new Aftersales();
        $data = [
            'products' => [
                ['id' => 1, 'name' => '商品1'],
                ['id' => 2, 'name' => '商品2'],
            ],
        ];

        $modifiedFields = $this->updater->updateAdditionalFields($aftersales, $data);

        $this->assertContains('products', $modifiedFields);
    }

    public function testUpdateAdditionalFieldsWithoutProducts(): void
    {
        $aftersales = new Aftersales();
        $data = ['otherField' => 'value'];

        $modifiedFields = $this->updater->updateAdditionalFields($aftersales, $data);

        $this->assertEmpty($modifiedFields);
    }

    public function testSyncBasicAftersalesInfo(): void
    {
        $aftersales = new Aftersales();
        $data = [
            'auditRemark' => '审核备注',
            'description' => '售后描述',
        ];

        $this->updater->syncBasicAftersalesInfo($aftersales, $data);

        $this->assertSame('审核备注', $aftersales->getRejectReason());
        $this->assertSame('售后描述', $aftersales->getDescription());
    }

    public function testUpdateAftersalesMetadata(): void
    {
        $aftersales = new Aftersales();
        $data = [
            'auditor' => '审核员',
            'auditRemark' => '审核意见',
            'approvedAmount' => 5000,
        ];

        $this->updater->updateAftersalesMetadata($aftersales, $data);

        $this->assertSame('审核员', $aftersales->getProcessor());
        $this->assertSame('审核意见', $aftersales->getRejectReason());
        $this->assertSame(5000, $aftersales->getApprovedAmount());
    }

    public function testUpdateAftersalesTimestamps(): void
    {
        $aftersales = new Aftersales();
        $data = [
            'auditTime' => '2023-01-01 10:00:00',
            'processTime' => '2023-01-01 11:00:00',
            'completedTime' => '2023-01-01 12:00:00',
        ];

        $this->updater->updateAftersalesTimestamps($aftersales, $data);

        $this->assertInstanceOf(\DateTimeImmutable::class, $aftersales->getProcessedTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $aftersales->getCompletedTime());
    }

    public function testUpdateBasicFieldsWithProofImages(): void
    {
        $aftersales = new Aftersales();
        $data = [
            'proofImages' => ['image1.jpg', 'image2.png', 123, true, null],
        ];

        $modifiedFields = $this->updater->updateBasicFields($aftersales, $data);

        $this->assertSame(['image1.jpg', 'image2.png'], $aftersales->getProofImages());
        $this->assertContains('proofImages', $modifiedFields);
    }

    public function testUpdateBasicFieldsWithInvalidProofImages(): void
    {
        $aftersales = new Aftersales();
        $data = [
            'proofImages' => 'not_an_array',
        ];

        $modifiedFields = $this->updater->updateBasicFields($aftersales, $data);

        $this->assertSame([], $aftersales->getProofImages());
        $this->assertContains('proofImages', $modifiedFields);
    }
}
