<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesLogAction::class)]
class AftersalesLogActionTest extends AbstractEnumTestCase
{
    public function testEnumCasesValuesShouldReturnCorrectStrings(): void
    {
        $this->assertEquals('CREATE', AftersalesLogAction::CREATE->value);
        $this->assertEquals('SUBMIT', AftersalesLogAction::SUBMIT->value);
        $this->assertEquals('MODIFY', AftersalesLogAction::MODIFY->value);
        $this->assertEquals('CANCEL', AftersalesLogAction::CANCEL->value);
        $this->assertEquals('APPROVE', AftersalesLogAction::APPROVE->value);
        $this->assertEquals('REJECT', AftersalesLogAction::REJECT->value);
        $this->assertEquals('AUTO_APPROVE', AftersalesLogAction::AUTO_APPROVE->value);
        $this->assertEquals('AUTO_REJECT', AftersalesLogAction::AUTO_REJECT->value);
        $this->assertEquals('SHIP_RETURN', AftersalesLogAction::SHIP_RETURN->value);
        $this->assertEquals('RECEIVE_RETURN', AftersalesLogAction::RECEIVE_RETURN->value);
        $this->assertEquals('INSPECT_RETURN', AftersalesLogAction::INSPECT_RETURN->value);
        $this->assertEquals('SHIP_EXCHANGE', AftersalesLogAction::SHIP_EXCHANGE->value);
        $this->assertEquals('RECEIVE_EXCHANGE', AftersalesLogAction::RECEIVE_EXCHANGE->value);
        $this->assertEquals('REQUEST_REFUND', AftersalesLogAction::REQUEST_REFUND->value);
        $this->assertEquals('PROCESS_REFUND', AftersalesLogAction::PROCESS_REFUND->value);
        $this->assertEquals('COMPLETE_REFUND', AftersalesLogAction::COMPLETE_REFUND->value);
        $this->assertEquals('FAIL_REFUND', AftersalesLogAction::FAIL_REFUND->value);
        $this->assertEquals('TIMEOUT_PROCESS', AftersalesLogAction::TIMEOUT_PROCESS->value);
        $this->assertEquals('STATE_CHANGE', AftersalesLogAction::STATE_CHANGE->value);
        $this->assertEquals('SYSTEM_UPDATE', AftersalesLogAction::SYSTEM_UPDATE->value);
        $this->assertEquals('SYSTEM_SYNC', AftersalesLogAction::SYSTEM_SYNC->value);
        $this->assertEquals('STATUS_CHANGE', AftersalesLogAction::STATUS_CHANGE->value);
        $this->assertEquals('ADD_REMARK', AftersalesLogAction::ADD_REMARK->value);
        $this->assertEquals('COMPLETE', AftersalesLogAction::COMPLETE->value);
        $this->assertEquals('MODIFY_INFO', AftersalesLogAction::MODIFY_INFO->value);
        $this->assertEquals('MODIFY_REFUND_AMOUNT', AftersalesLogAction::MODIFY_REFUND_AMOUNT->value);
    }

    public function testEnumLabelsReturnsCorrectValues(): void
    {
        $this->assertEquals('CREATE', AftersalesLogAction::CREATE->getLabel());
        $this->assertEquals('SUBMIT', AftersalesLogAction::SUBMIT->getLabel());
        $this->assertEquals('MODIFY', AftersalesLogAction::MODIFY->getLabel());
        $this->assertEquals('CANCEL', AftersalesLogAction::CANCEL->getLabel());
        $this->assertEquals('APPROVE', AftersalesLogAction::APPROVE->getLabel());
        $this->assertEquals('REJECT', AftersalesLogAction::REJECT->getLabel());
        $this->assertEquals('AUTO_APPROVE', AftersalesLogAction::AUTO_APPROVE->getLabel());
        $this->assertEquals('AUTO_REJECT', AftersalesLogAction::AUTO_REJECT->getLabel());
        $this->assertEquals('SHIP_RETURN', AftersalesLogAction::SHIP_RETURN->getLabel());
        $this->assertEquals('RECEIVE_RETURN', AftersalesLogAction::RECEIVE_RETURN->getLabel());
        $this->assertEquals('INSPECT_RETURN', AftersalesLogAction::INSPECT_RETURN->getLabel());
        $this->assertEquals('SHIP_EXCHANGE', AftersalesLogAction::SHIP_EXCHANGE->getLabel());
        $this->assertEquals('RECEIVE_EXCHANGE', AftersalesLogAction::RECEIVE_EXCHANGE->getLabel());
        $this->assertEquals('REQUEST_REFUND', AftersalesLogAction::REQUEST_REFUND->getLabel());
        $this->assertEquals('PROCESS_REFUND', AftersalesLogAction::PROCESS_REFUND->getLabel());
        $this->assertEquals('COMPLETE_REFUND', AftersalesLogAction::COMPLETE_REFUND->getLabel());
        $this->assertEquals('FAIL_REFUND', AftersalesLogAction::FAIL_REFUND->getLabel());
        $this->assertEquals('TIMEOUT_PROCESS', AftersalesLogAction::TIMEOUT_PROCESS->getLabel());
        $this->assertEquals('STATE_CHANGE', AftersalesLogAction::STATE_CHANGE->getLabel());
        $this->assertEquals('SYSTEM_UPDATE', AftersalesLogAction::SYSTEM_UPDATE->getLabel());
        $this->assertEquals('SYSTEM_SYNC', AftersalesLogAction::SYSTEM_SYNC->getLabel());
        $this->assertEquals('STATUS_CHANGE', AftersalesLogAction::STATUS_CHANGE->getLabel());
        $this->assertEquals('ADD_REMARK', AftersalesLogAction::ADD_REMARK->getLabel());
        $this->assertEquals('COMPLETE', AftersalesLogAction::COMPLETE->getLabel());
        $this->assertEquals('MODIFY_INFO', AftersalesLogAction::MODIFY_INFO->getLabel());
        $this->assertEquals('MODIFY_REFUND_AMOUNT', AftersalesLogAction::MODIFY_REFUND_AMOUNT->getLabel());
    }

    public function testCasesShouldReturnAllAvailableOptions(): void
    {
        $cases = AftersalesLogAction::cases();
        $this->assertCount(26, $cases);
        $this->assertContains(AftersalesLogAction::CREATE, $cases);
        $this->assertContains(AftersalesLogAction::SUBMIT, $cases);
        $this->assertContains(AftersalesLogAction::MODIFY, $cases);
        $this->assertContains(AftersalesLogAction::CANCEL, $cases);
        $this->assertContains(AftersalesLogAction::APPROVE, $cases);
        $this->assertContains(AftersalesLogAction::REJECT, $cases);
        $this->assertContains(AftersalesLogAction::AUTO_APPROVE, $cases);
        $this->assertContains(AftersalesLogAction::AUTO_REJECT, $cases);
        $this->assertContains(AftersalesLogAction::SHIP_RETURN, $cases);
        $this->assertContains(AftersalesLogAction::RECEIVE_RETURN, $cases);
        $this->assertContains(AftersalesLogAction::INSPECT_RETURN, $cases);
        $this->assertContains(AftersalesLogAction::SHIP_EXCHANGE, $cases);
        $this->assertContains(AftersalesLogAction::RECEIVE_EXCHANGE, $cases);
        $this->assertContains(AftersalesLogAction::REQUEST_REFUND, $cases);
        $this->assertContains(AftersalesLogAction::PROCESS_REFUND, $cases);
        $this->assertContains(AftersalesLogAction::COMPLETE_REFUND, $cases);
        $this->assertContains(AftersalesLogAction::FAIL_REFUND, $cases);
        $this->assertContains(AftersalesLogAction::TIMEOUT_PROCESS, $cases);
        $this->assertContains(AftersalesLogAction::STATE_CHANGE, $cases);
        $this->assertContains(AftersalesLogAction::SYSTEM_UPDATE, $cases);
        $this->assertContains(AftersalesLogAction::SYSTEM_SYNC, $cases);
        $this->assertContains(AftersalesLogAction::STATUS_CHANGE, $cases);
        $this->assertContains(AftersalesLogAction::ADD_REMARK, $cases);
        $this->assertContains(AftersalesLogAction::COMPLETE, $cases);
        $this->assertContains(AftersalesLogAction::MODIFY_INFO, $cases);
        $this->assertContains(AftersalesLogAction::MODIFY_REFUND_AMOUNT, $cases);
    }

    public function testToSelectItemShouldReturnValueLabelPairs(): void
    {
        $item = AftersalesLogAction::CREATE->toSelectItem();
        $this->assertIsArray($item);
        $this->assertArrayHasKey('value', $item);
        $this->assertArrayHasKey('label', $item);
        $this->assertEquals('CREATE', $item['value']);
        $this->assertEquals('CREATE', $item['label']);
    }

    public function testGenOptionsShouldReturnSelectOptions(): void
    {
        $options = AftersalesLogAction::genOptions();
        $this->assertIsArray($options);
        $this->assertCount(26, $options);

        foreach ($options as $option) {
            $this->assertIsArray($option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
        }

        $values = array_column($options, 'value');
        $this->assertContains('CREATE', $values);
        $this->assertContains('SUBMIT', $values);
        $this->assertContains('APPROVE', $values);
        $this->assertContains('REJECT', $values);
        $this->assertContains('COMPLETE', $values);
    }

    public function testEnumValueUniquenessShouldEnsureAllValuesAreDistinct(): void
    {
        $values = array_map(fn ($case) => $case->value, AftersalesLogAction::cases());
        $this->assertSame(count($values), count(array_unique($values)), '所有枚举的 value 必须是唯一的。');
    }

    public function testEnumLabelUniquenessShouldEnsureAllLabelsAreDistinct(): void
    {
        $labels = array_map(fn ($case) => $case->getLabel(), AftersalesLogAction::cases());
        $this->assertSame(count($labels), count(array_unique($labels)), '所有枚举的 label 必须是唯一的。');
    }

    public function testToArrayShouldReturnValueLabelPairs(): void
    {
        $array = AftersalesLogAction::CREATE->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertEquals('CREATE', $array['value']);
        $this->assertEquals('CREATE', $array['label']);
    }
}
