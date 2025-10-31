<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Procedure\Aftersales\CreateAftersalesFromOms;

/**
 * @internal
 */
#[CoversClass(CreateAftersalesFromOms::class)]
#[RunTestsInSeparateProcesses]
class CreateAftersalesFromOmsTest extends AbstractProcedureTestCase
{
    private CreateAftersalesFromOms $procedure;

    protected function onSetUp(): void
    {
        $this->procedure = self::getService(CreateAftersalesFromOms::class);
    }

    public function testServiceExists(): void
    {
        $this->assertInstanceOf(CreateAftersalesFromOms::class, $this->procedure);
    }

    public function testExecuteWithoutRequiredParameters(): void
    {
        $this->expectException(\Error::class);
        $this->procedure->execute();
    }

    public function testGetMockResult(): void
    {
        $mockResult = CreateAftersalesFromOms::getMockResult();

        $this->assertIsArray($mockResult);
        $this->assertTrue($mockResult['success']);
        $this->assertEquals('售后单创建成功', $mockResult['message']);
        $this->assertArrayHasKey('aftersalesId', $mockResult);
        $this->assertArrayHasKey('aftersalesNo', $mockResult);
    }

    public function testValidateImageUrls(): void
    {
        // 测试空图片URL
        $this->procedure->aftersalesNo = 'AS2024001';
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER001';
        $this->procedure->reason = '质量问题';
        $this->procedure->proofImages = [''];
        $this->procedure->refundAmount = 10000;
        $this->procedure->applicantName = '张三';
        $this->procedure->applicantPhone = '13800138000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
            ['productCode' => 'P001', 'productName' => 'Product 1', 'quantity' => 1, 'amount' => 10000],
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个图片URL不能为空');
        $this->procedure->execute();
    }

    public function testValidateImageUrlsInvalidFormat(): void
    {
        $this->procedure->aftersalesNo = 'AS2024001';
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER001';
        $this->procedure->reason = '质量问题';
        $this->procedure->proofImages = ['invalid-url'];
        $this->procedure->refundAmount = 10000;
        $this->procedure->applicantName = '张三';
        $this->procedure->applicantPhone = '13800138000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
            ['productCode' => 'P001', 'productName' => 'Product 1', 'quantity' => 1, 'amount' => 10000],
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个图片URL格式无效');
        $this->procedure->execute();
    }

    public function testValidateImageUrlsInvalidExtension(): void
    {
        $this->procedure->aftersalesNo = 'AS2024001';
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER001';
        $this->procedure->reason = '质量问题';
        $this->procedure->proofImages = ['https://example.com/file.txt'];
        $this->procedure->refundAmount = 10000;
        $this->procedure->applicantName = '张三';
        $this->procedure->applicantPhone = '13800138000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
            ['productCode' => 'P001', 'productName' => 'Product 1', 'quantity' => 1, 'amount' => 10000],
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('第1个URL不是有效的图片格式');
        $this->procedure->execute();
    }

    public function testValidateImageUrlsTooManyImages(): void
    {
        $this->procedure->aftersalesNo = 'AS2024001';
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER001';
        $this->procedure->reason = '质量问题';
        $this->procedure->proofImages = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
            'https://example.com/image3.jpg',
            'https://example.com/image4.jpg',
            'https://example.com/image5.jpg',
            'https://example.com/image6.jpg',
            'https://example.com/image7.jpg',
            'https://example.com/image8.jpg',
            'https://example.com/image9.jpg',
            'https://example.com/image10.jpg', // 第10张，超过限制
        ];
        $this->procedure->refundAmount = 10000;
        $this->procedure->applicantName = '张三';
        $this->procedure->applicantPhone = '13800138000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
            ['productCode' => 'P001', 'productName' => 'Product 1', 'quantity' => 1, 'amount' => 10000],
        ];

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('凭证图片最多只能上传9张');
        $this->procedure->execute();
    }

    public function testValidateImageUrlsValidImages(): void
    {
        // 这个测试验证有效的图片URL不会抛出图片验证异常
        $this->procedure->aftersalesNo = 'AS2024001';
        $this->procedure->aftersalesType = 'refund';
        $this->procedure->orderNo = 'ORDER001';
        $this->procedure->reason = '质量问题';
        $this->procedure->proofImages = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.png',
            'https://example.com/image3.gif',
        ];
        $this->procedure->refundAmount = 10000;
        $this->procedure->applicantName = '张三';
        $this->procedure->applicantPhone = '13800138000';
        $this->procedure->applyTime = '2024-01-01 10:00:00';
        $this->procedure->products = [
            ['productCode' => 'P001', 'productName' => 'Product 1', 'quantity' => 1, 'amount' => 10000],
        ];

        // 使用反射调用私有的validateInput方法来只测试验证逻辑
        $reflection = new \ReflectionClass($this->procedure);
        $validateMethod = $reflection->getMethod('validateInput');
        $validateMethod->setAccessible(true);

        // 有效的图片URL不应该抛出任何异常
        $this->expectNotToPerformAssertions();
        $validateMethod->invoke($this->procedure);
    }
}
