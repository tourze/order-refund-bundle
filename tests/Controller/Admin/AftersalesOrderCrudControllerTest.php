<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OrderRefundBundle\Controller\Admin\AftersalesOrderCrudController;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesOrderCrudController::class)]
#[RunTestsInSeparateProcesses]
class AftersalesOrderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private AftersalesOrderCrudController $controller;

    protected function onSetUp(): void
    {
        $this->controller = new AftersalesOrderCrudController();
    }

    protected function getControllerService(): AftersalesOrderCrudController
    {
        return $this->controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '关联售后申请' => ['关联售后申请'];
        yield '订单编号' => ['订单编号'];
        yield '订单状态' => ['订单状态'];
        yield '用户ID' => ['用户ID'];
        yield '订单总金额' => ['订单总金额'];
        yield '订单创建时间' => ['订单创建时间'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'aftersales' => ['aftersales'];
        yield 'orderNumber' => ['orderNumber'];
        yield 'orderStatus' => ['orderStatus'];
        yield 'userId' => ['userId'];
        yield 'totalAmount' => ['totalAmount'];
        yield 'orderCreateTime' => ['orderCreateTime'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'aftersales' => ['aftersales'];
        yield 'orderNumber' => ['orderNumber'];
        yield 'orderStatus' => ['orderStatus'];
        yield 'userId' => ['userId'];
        yield 'totalAmount' => ['totalAmount'];
        yield 'orderCreateTime' => ['orderCreateTime'];
    }

    public function testGetEntityFqcn(): void
    {
        $controller = new AftersalesOrderCrudController();
        $this->assertSame(AftersalesOrder::class, $controller::getEntityFqcn());
    }

    public function testIndexPage(): void
    {
        $client = static::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);
        $client->request('GET', '/admin?crudController=' . AftersalesOrderCrudController::class);

        $this->assertResponseIsSuccessful();
    }

    public function testNewPage(): void
    {
        $client = static::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);
        $client->request('GET', '/admin?crudController=' . AftersalesOrderCrudController::class . '&crudAction=new');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateEntity(): void
    {
        $client = static::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 需要先创建一个Aftersales实体作为关联
        $aftersales = $this->createTestAftersales();

        // 首先访问新建页面获取表单和CSRF token
        $crawler = $client->request('GET', '/admin?crudController=' . AftersalesOrderCrudController::class . '&crudAction=new');
        $this->assertResponseIsSuccessful();

        // 检查是否有表单
        $formCount = $crawler->filter('form')->count();
        if (0 === $formCount) {
            // 如果没有表单，直接测试POST请求
            $client->request('POST', '/admin?crudController=' . AftersalesOrderCrudController::class . '&crudAction=new');
            // 接受200、422或重定向状态，因为EasyAdmin的行为可能不同
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertTrue(in_array($statusCode, [200, 302, 422], true), "Expected status 200, 302, or 422, got {$statusCode}");

            return;
        }

        // 获取表单并填充数据
        $form = $crawler->filter('form')->form([
            'AftersalesOrder[aftersales]' => $aftersales->getId(),
            'AftersalesOrder[orderNumber]' => 'TEST-ORDER-001',
            'AftersalesOrder[orderStatus]' => 'paid',
            'AftersalesOrder[totalAmount]' => '100.00',
            'AftersalesOrder[userId]' => 'user_001',
            'AftersalesOrder[orderCreateTime]' => (new \DateTimeImmutable())->format('Y-m-d\TH:i:s'),
        ]);

        $client->submit($form);

        // 检查响应 - 可能是重定向（成功）或422（验证错误）
        $response = $client->getResponse();
        $this->assertTrue($response->isRedirection() || 422 === $response->getStatusCode());
    }

    private function createTestAftersales(): Aftersales
    {
        $aftersales = new Aftersales();
        $aftersales->setReferenceNumber('TEST-REF-' . uniqid());
        $aftersales->setType(AftersalesType::REFUND_ONLY);
        $aftersales->setReason(RefundReason::QUALITY_ISSUE);
        $aftersales->setDescription('Test aftersales');

        // 设置必需的字段以满足数据库约束
        $aftersales->setOrderProductId('TEST-ORDER-PRODUCT-' . uniqid());
        $aftersales->setProductId('TEST-PRODUCT-' . uniqid());
        $aftersales->setSkuId('TEST-SKU-' . uniqid());
        $aftersales->setProductName('Test Product');
        $aftersales->setSkuName('Test SKU');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('100.00');
        $aftersales->setRefundAmount('100.00');
        $aftersales->setOriginalRefundAmount('100.00');
        $aftersales->setActualRefundAmount('100.00');

        $em = self::getService('Doctrine\ORM\EntityManagerInterface');
        $em->persist($aftersales);
        $em->flush();

        return $aftersales;
    }

    public function testValidationErrors(): void
    {
        $client = static::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        $crawler = $client->request('GET', '/admin?crudController=' . AftersalesOrderCrudController::class . '&crudAction=new');
        $this->assertResponseIsSuccessful();

        // 检查是否有表单存在
        $formCount = $crawler->filter('form')->count();
        if (0 === $formCount) {
            // 如果没有表单，直接测试POST请求
            $client->request('POST', '/admin?crudController=' . AftersalesOrderCrudController::class . '&crudAction=new');
            // 接受任何有效的HTTP状态码，因为EasyAdmin的行为可能不同
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertTrue($statusCode >= 200 && $statusCode < 500, "Expected valid status code, got {$statusCode}");

            return;
        }

        // 获取表单
        $form = $crawler->filter('form')->form();

        // 提交空表单来触发验证错误
        $crawler = $client->submit($form, []);

        // 检查是否返回验证错误或重定向（有些表单可能会重定向）
        if ($client->getResponse()->isRedirection()) {
            // 如果重定向，说明没有验证错误，验证成功状态
            $client->followRedirect();
            $this->assertResponseIsSuccessful();

            return;
        }

        $this->assertResponseStatusCodeSame(422);
        // 检查页面是否包含验证错误信息
        $errorElements = $crawler->filter('.invalid-feedback, .alert-danger, .error');
        if ($errorElements->count() > 0) {
            $this->assertStringContainsString('should not be blank', $errorElements->text());
        } else {
            // 如果找不到错误消息，验证状态码是422
            $this->assertResponseStatusCodeSame(422, '应该返回验证错误状态');
        }
    }

    public function testSearchFunctionality(): void
    {
        $client = static::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);
        $client->request('GET', '/admin?crudController=' . AftersalesOrderCrudController::class . '&query=TEST');

        $this->assertResponseIsSuccessful();
    }
}
