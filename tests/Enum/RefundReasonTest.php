<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(RefundReason::class)]
class RefundReasonTest extends AbstractEnumTestCase
{
    public function testGetLabel(): void
    {
        $this->assertSame('没用/少用优惠', RefundReason::UNUSED_DISCOUNT->getLabel());
        $this->assertSame('商品质量问题', RefundReason::QUALITY_ISSUE->getLabel());
        $this->assertSame('商品价格问题', RefundReason::PRICE_ISSUE->getLabel());
        $this->assertSame('不想要了', RefundReason::DONT_WANT->getLabel());
        $this->assertSame('商品缺货', RefundReason::OUT_OF_STOCK->getLabel());
        $this->assertSame('少件/漏发', RefundReason::MISSING_ITEM->getLabel());
        $this->assertSame('订单配送超时', RefundReason::DELIVERY_TIMEOUT->getLabel());
        $this->assertSame('其他', RefundReason::OTHER->getLabel());
    }

    public function testValues(): void
    {
        $this->assertSame('unused_discount', RefundReason::UNUSED_DISCOUNT->value);
        $this->assertSame('quality_issue', RefundReason::QUALITY_ISSUE->value);
        $this->assertSame('price_issue', RefundReason::PRICE_ISSUE->value);
        $this->assertSame('dont_want', RefundReason::DONT_WANT->value);
        $this->assertSame('out_of_stock', RefundReason::OUT_OF_STOCK->value);
        $this->assertSame('missing_item', RefundReason::MISSING_ITEM->value);
        $this->assertSame('delivery_timeout', RefundReason::DELIVERY_TIMEOUT->value);
        $this->assertSame('other', RefundReason::OTHER->value);
    }

    public function testCases(): void
    {
        $cases = RefundReason::cases();
        $this->assertCount(8, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $this->assertContains('unused_discount', $values);
        $this->assertContains('quality_issue', $values);
        $this->assertContains('price_issue', $values);
        $this->assertContains('dont_want', $values);
        $this->assertContains('out_of_stock', $values);
        $this->assertContains('missing_item', $values);
        $this->assertContains('delivery_timeout', $values);
        $this->assertContains('other', $values);
    }

    public function testSupportsAutoApproval(): void
    {
        $this->assertTrue(RefundReason::DONT_WANT->supportsAutoApproval());
        $this->assertTrue(RefundReason::UNUSED_DISCOUNT->supportsAutoApproval());
        $this->assertFalse(RefundReason::QUALITY_ISSUE->supportsAutoApproval());
        $this->assertFalse(RefundReason::PRICE_ISSUE->supportsAutoApproval());
        $this->assertFalse(RefundReason::OUT_OF_STOCK->supportsAutoApproval());
        $this->assertFalse(RefundReason::MISSING_ITEM->supportsAutoApproval());
        $this->assertFalse(RefundReason::DELIVERY_TIMEOUT->supportsAutoApproval());
        $this->assertFalse(RefundReason::OTHER->supportsAutoApproval());
    }

    public function testIsMerchantResponsibility(): void
    {
        $this->assertFalse(RefundReason::DONT_WANT->isMerchantResponsibility());
        $this->assertFalse(RefundReason::UNUSED_DISCOUNT->isMerchantResponsibility());
        $this->assertFalse(RefundReason::PRICE_ISSUE->isMerchantResponsibility());
        $this->assertTrue(RefundReason::QUALITY_ISSUE->isMerchantResponsibility());
        $this->assertTrue(RefundReason::OUT_OF_STOCK->isMerchantResponsibility());
        $this->assertTrue(RefundReason::MISSING_ITEM->isMerchantResponsibility());
        $this->assertTrue(RefundReason::DELIVERY_TIMEOUT->isMerchantResponsibility());
        $this->assertFalse(RefundReason::OTHER->isMerchantResponsibility());
    }

    public function testToArray(): void
    {
        $array = RefundReason::DONT_WANT->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('dont_want', $array['value']);
        $this->assertEquals('不想要了', $array['label']);
    }
}
