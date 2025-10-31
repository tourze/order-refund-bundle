<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesStage::class)]
class AftersalesStageTest extends AbstractEnumTestCase
{
    public function testEnumCasesValuesShouldReturnCorrectStrings(): void
    {
        $this->assertEquals('apply', AftersalesStage::APPLY->value);
        $this->assertEquals('audit', AftersalesStage::AUDIT->value);
        $this->assertEquals('return', AftersalesStage::RETURN->value);
        $this->assertEquals('receive', AftersalesStage::RECEIVE->value);
        $this->assertEquals('refund', AftersalesStage::REFUND->value);
        $this->assertEquals('exchange', AftersalesStage::EXCHANGE->value);
        $this->assertEquals('complete', AftersalesStage::COMPLETE->value);
    }

    public function testEnumLabelsReturnsCorrectValues(): void
    {
        $this->assertEquals('APPLY', AftersalesStage::APPLY->getLabel());
        $this->assertEquals('AUDIT', AftersalesStage::AUDIT->getLabel());
        $this->assertEquals('RETURN', AftersalesStage::RETURN->getLabel());
        $this->assertEquals('RECEIVE', AftersalesStage::RECEIVE->getLabel());
        $this->assertEquals('退款', AftersalesStage::REFUND->getLabel());
        $this->assertEquals('EXCHANGE', AftersalesStage::EXCHANGE->getLabel());
        $this->assertEquals('COMPLETE', AftersalesStage::COMPLETE->getLabel());
    }

    public function testCasesShouldReturnAllAvailableOptions(): void
    {
        $cases = AftersalesStage::cases();
        $this->assertCount(7, $cases);
        $this->assertContains(AftersalesStage::APPLY, $cases);
        $this->assertContains(AftersalesStage::AUDIT, $cases);
        $this->assertContains(AftersalesStage::RETURN, $cases);
        $this->assertContains(AftersalesStage::RECEIVE, $cases);
        $this->assertContains(AftersalesStage::REFUND, $cases);
        $this->assertContains(AftersalesStage::EXCHANGE, $cases);
        $this->assertContains(AftersalesStage::COMPLETE, $cases);
    }

    public function testToSelectItemShouldReturnValueLabelPairs(): void
    {
        $item = AftersalesStage::APPLY->toSelectItem();
        $this->assertIsArray($item);
        $this->assertArrayHasKey('value', $item);
        $this->assertArrayHasKey('label', $item);
        $this->assertEquals('apply', $item['value']);
        $this->assertEquals('APPLY', $item['label']);
    }

    public function testGenOptionsShouldReturnSelectOptions(): void
    {
        $options = AftersalesStage::genOptions();
        $this->assertIsArray($options);
        $this->assertCount(7, $options);

        foreach ($options as $option) {
            $this->assertIsArray($option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }

        $values = array_column($options, 'value');
        $this->assertContains('apply', $values);
        $this->assertContains('audit', $values);
        $this->assertContains('return', $values);
        $this->assertContains('receive', $values);
        $this->assertContains('refund', $values);
        $this->assertContains('exchange', $values);
        $this->assertContains('complete', $values);
    }

    public function testEnumValueUniquenessShouldEnsureAllValuesAreDistinct(): void
    {
        $values = array_map(fn ($case) => $case->value, AftersalesStage::cases());
        $this->assertSame(count($values), count(array_unique($values)), '所有枚举的 value 必须是唯一的。');
    }

    public function testEnumLabelUniquenessShouldEnsureAllLabelsAreDistinct(): void
    {
        $labels = array_map(fn ($case) => $case->getLabel(), AftersalesStage::cases());
        $this->assertSame(count($labels), count(array_unique($labels)), '所有枚举的 label 必须是唯一的。');
    }

    public function testRefundStageReturnsChineseLabel(): void
    {
        $this->assertEquals('退款', AftersalesStage::REFUND->getLabel());
        $this->assertNotEquals(AftersalesStage::REFUND->value, AftersalesStage::REFUND->getLabel());
    }

    public function testToArrayShouldReturnValueLabelPairs(): void
    {
        $array = AftersalesStage::APPLY->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('apply', $array['value']);
        $this->assertEquals('APPLY', $array['label']);
    }
}
