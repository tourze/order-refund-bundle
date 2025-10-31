<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Aftersales::class)]
class AftersalesTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
    }

    protected function createEntity(): Aftersales
    {
        return new Aftersales();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'type' => ['type', AftersalesType::REFUND_ONLY],
            'reason' => ['reason', RefundReason::QUALITY_ISSUE],
            'state' => ['state', AftersalesState::PENDING_APPROVAL],
            'stage' => ['stage', AftersalesStage::APPLY],
            'description' => ['description', '测试售后申请'],
            'rejectReason' => ['rejectReason', '不符合条件'],
            'proofImages' => ['proofImages', ['image1.jpg', 'image2.jpg']],
        ];
    }

    public function testAftersalesCreation(): void
    {
        $aftersales = new Aftersales();

        self::assertNull($aftersales->getId());
    }

    public function testCanModifyWhenRejected(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setState(AftersalesState::REJECTED);

        // 初始状态修改次数为0，应该可以修改
        self::assertTrue($aftersales->canModify());

        // 注意：setModificationCount() 方法不存在，无法直接设置修改次数
        // 这个测试验证了业务逻辑，但需要通过其他方式增加修改次数
    }

    public function testCanModifyWhenNotRejected(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setState(AftersalesState::PENDING_APPROVAL);

        // 非拒绝状态，无论修改次数如何都不能修改
        self::assertFalse($aftersales->canModify());
    }

    public function testIsTimeout(): void
    {
        $aftersales = new Aftersales();

        self::assertFalse($aftersales->isTimeout());

        $pastTime = new \DateTimeImmutable('-2 days');
        $aftersales->setAutoProcessTime($pastTime);

        self::assertTrue($aftersales->isTimeout());
    }

    public function testGetAvailableActionsForPendingState(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setState(AftersalesState::PENDING_APPROVAL);

        $actions = $aftersales->getAvailableActions();

        self::assertContains('approve', $actions);
        self::assertContains('reject', $actions);
    }

    public function testGetAvailableActionsForApprovedState(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setState(AftersalesState::APPROVED);
        $aftersales->setType(AftersalesType::RETURN_REFUND);

        $actions = $aftersales->getAvailableActions();

        self::assertContains('wait_return', $actions);
    }

    public function testSettersAndGetters(): void
    {
        $aftersales = new Aftersales();
        $type = AftersalesType::REFUND_ONLY;
        $state = AftersalesState::APPROVED;
        $stage = AftersalesStage::AUDIT;
        $reason = RefundReason::DONT_WANT;

        $aftersales->setType($type);
        $aftersales->setState($state);
        $aftersales->setStage($stage);
        $aftersales->setReason($reason);

        self::assertSame($type, $aftersales->getType());
        self::assertSame($state, $aftersales->getState());
        self::assertSame($stage, $aftersales->getStage());
        self::assertSame($reason, $aftersales->getReason());
    }

    public function testReferenceNumberField(): void
    {
        $aftersales = new Aftersales();
        $aftersales->setReferenceNumber('ORD001');

        $this->assertEquals('ORD001', $aftersales->getReferenceNumber());
    }

    public function testNoContractReference(): void
    {
        $reflection = new \ReflectionClass(Aftersales::class);
        $fileName = $reflection->getFileName();
        $this->assertNotFalse($fileName, 'Should be able to get class file name');

        $source = file_get_contents($fileName);
        $this->assertNotFalse($source, 'Should be able to read file contents');
        $this->assertIsString($source);

        $this->assertStringNotContainsString(
            'OrderCoreBundle\Entity\Contract',
            $source,
            'Aftersales entity should not import Contract'
        );
    }
}
