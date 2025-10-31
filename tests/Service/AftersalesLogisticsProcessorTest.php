<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Service\AftersalesLogisticsProcessor;

/**
 * 售后物流处理器测试
 * @internal
 */
#[CoversClass(AftersalesLogisticsProcessor::class)]
class AftersalesLogisticsProcessorTest extends TestCase
{
    private AftersalesLogisticsProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new AftersalesLogisticsProcessor();
    }

    public function testProcessReturnLogistics(): void
    {
        $aftersales = new Aftersales();
        $logistics = [
            'company' => '顺丰快递',
            'trackingNumber' => 'SF123456789',
            'returnTime' => '2023-01-01 10:00:00',
        ];

        $this->processor->processReturnLogistics($aftersales, $logistics);

        $this->assertSame('顺丰快递', $aftersales->getReturnExpressCompany());
        $this->assertSame('SF123456789', $aftersales->getReturnExpressNumber());
        $this->assertInstanceOf(\DateTimeImmutable::class, $aftersales->getReturnShippingTime());
    }

    public function testProcessReturnLogisticsWithInvalidData(): void
    {
        $aftersales = new Aftersales();
        $logistics = 'invalid_data';

        $this->processor->processReturnLogistics($aftersales, $logistics);

        $this->assertNull($aftersales->getReturnExpressCompany());
        $this->assertNull($aftersales->getReturnExpressNumber());
        $this->assertNull($aftersales->getReturnShippingTime());
    }

    public function testProcessReturnLogisticsWithEmptyData(): void
    {
        $aftersales = new Aftersales();
        $logistics = [];

        $this->processor->processReturnLogistics($aftersales, $logistics);

        $this->assertNull($aftersales->getReturnExpressCompany());
        $this->assertNull($aftersales->getReturnExpressNumber());
        $this->assertNull($aftersales->getReturnShippingTime());
    }

    public function testProcessReturnLogisticsWithMissingRequiredFields(): void
    {
        $aftersales = new Aftersales();
        $logistics = [
            'company' => '',
            'trackingNumber' => '',
            'returnTime' => '2023-01-01 10:00:00',
        ];

        $this->processor->processReturnLogistics($aftersales, $logistics);

        $this->assertNull($aftersales->getReturnExpressCompany());
        $this->assertNull($aftersales->getReturnExpressNumber());
        $this->assertNull($aftersales->getReturnShippingTime());
    }

    public function testProcessExchangeAddress(): void
    {
        $aftersales = new Aftersales();
        $address = [
            'name' => '张三',
            'phone' => '13800138000',
            'province' => '广东省',
            'city' => '深圳市',
            'district' => '南山区',
            'address' => '科技园南区',
            'zipCode' => '518000',
        ];

        $this->processor->processExchangeAddress($aftersales, $address);

        $exchangeAddress = $aftersales->getExchangeAddress();
        $this->assertIsArray($exchangeAddress);
        $this->assertSame('张三', $exchangeAddress['name']);
        $this->assertSame('13800138000', $exchangeAddress['phone']);
        $this->assertSame('广东省', $exchangeAddress['province']);
        $this->assertSame('深圳市', $exchangeAddress['city']);
        $this->assertSame('南山区', $exchangeAddress['district']);
        $this->assertSame('科技园南区', $exchangeAddress['address']);
        $this->assertSame('518000', $exchangeAddress['zipCode']);
    }

    public function testProcessExchangeAddressWithInvalidData(): void
    {
        $aftersales = new Aftersales();
        $address = 'invalid_data';

        $this->processor->processExchangeAddress($aftersales, $address);

        $this->assertNull($aftersales->getExchangeAddress());
    }

    public function testProcessExchangeAddressWithPartialData(): void
    {
        $aftersales = new Aftersales();
        $address = [
            'name' => '张三',
            'phone' => '13800138000',
        ];

        $this->processor->processExchangeAddress($aftersales, $address);

        $exchangeAddress = $aftersales->getExchangeAddress();
        $this->assertIsArray($exchangeAddress);
        $this->assertSame('张三', $exchangeAddress['name']);
        $this->assertSame('13800138000', $exchangeAddress['phone']);
        $this->assertSame('', $exchangeAddress['province']);
        $this->assertSame('', $exchangeAddress['city']);
        $this->assertSame('', $exchangeAddress['district']);
        $this->assertSame('', $exchangeAddress['address']);
        $this->assertSame('', $exchangeAddress['zipCode']);
    }

    public function testProcessReturnLogisticsWithNumericValues(): void
    {
        $aftersales = new Aftersales();
        $logistics = [
            'company' => 123, // 会被转换为字符串
            'trackingNumber' => 456, // 会被转换为字符串
            'returnTime' => '',
        ];

        $this->processor->processReturnLogistics($aftersales, $logistics);

        // 由于数字会被转换为默认值，所以不会设置
        $this->assertNull($aftersales->getReturnExpressCompany());
        $this->assertNull($aftersales->getReturnExpressNumber());
        $this->assertNull($aftersales->getReturnShippingTime());
    }
}
