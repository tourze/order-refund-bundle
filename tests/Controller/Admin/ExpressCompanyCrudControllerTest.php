<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Tourze\OrderRefundBundle\Controller\Admin\ExpressCompanyCrudController;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(ExpressCompanyCrudController::class)]
#[Group('controller')]
#[RunTestsInSeparateProcesses]
final class ExpressCompanyCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private ExpressCompanyCrudController $controller;

    protected function onAfterSetUp(): void
    {
        $this->controller = new ExpressCompanyCrudController();
    }

    protected function getControllerService(): ExpressCompanyCrudController
    {
        return $this->controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        // Only include columns actually shown on index page
        yield 'ID' => ['ID'];
        yield '快递公司代码' => ['快递公司代码'];
        yield '快递公司名称' => ['快递公司名称'];
        yield '启用状态' => ['启用状态'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'code' => ['code'];
        yield 'name' => ['name'];
        yield 'isActive' => ['isActive'];
        // apiUrl is hideOnForm, so not included
        yield 'sortOrder' => ['sortOrder'];
        yield 'description' => ['description'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'code' => ['code'];
        yield 'name' => ['name'];
        yield 'isActive' => ['isActive'];
        // apiUrl is hideOnForm, so not included
        yield 'sortOrder' => ['sortOrder'];
        yield 'description' => ['description'];
        yield 'trackingUrlTemplate' => ['trackingUrlTemplate'];
        // supportedServices field does not exist in controller configuration
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(ExpressCompany::class, ExpressCompanyCrudController::getEntityFqcn());
    }

    public function testIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        $client->request('GET', '/admin/order-refund/express-company');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '快递公司管理');
    }

    public function testNewPage(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        $client->request('GET', '/admin/order-refund/express-company/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '添加快递公司');
    }

    public function testControllerConfiguration(): void
    {
        $controller = new ExpressCompanyCrudController();

        // 测试实体配置
        $this->assertSame(ExpressCompany::class, $controller::getEntityFqcn());

        // 测试字段配置
        $fields = iterator_to_array($controller->configureFields('index'));
        $this->assertNotEmpty($fields);
    }

    public function testSearchFunctionality(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建测试数据
        $expressCompany = $this->createTestExpressCompany();
        $expressCompany->setCode('SF-TEST');
        $expressCompany->setName('顺丰速运测试');
        self::getEntityManager()->persist($expressCompany);
        self::getEntityManager()->flush();

        // 测试按代码搜索
        $client->request('GET', '/admin/order-refund/express-company', [
            'query' => 'SF-TEST',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.table');
    }

    public function testCreateExpressCompanyForm(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        $crawler = $client->request('GET', '/admin/order-refund/express-company/new');
        $this->assertResponseIsSuccessful();

        // 检查表单基本结构
        $form = $this->findSubmitForm($crawler);
        $this->assertNotNull($form, 'Form should be available');

        // 验证表单字段
        $inputs = $form->all();
        $this->assertNotEmpty($inputs, 'Form should have input fields');
    }

    public function testRequiredFieldsValidation(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        $crawler = $client->request('GET', '/admin/order-refund/express-company/new');
        $this->assertResponseIsSuccessful();

        $form = $this->findSubmitForm($crawler);

        // 提交空表单来触发验证
        $crawler = $client->submit($form, []);

        // 验证响应（可能是422或重定向回表单）
        $response = $client->getResponse();
        $this->assertTrue(
            422 === $response->getStatusCode() || $response->isRedirect(),
            'Empty form submission should trigger validation'
        );
    }

    public function testFilterByActiveStatus(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建活跃的快递公司
        $activeCompany = $this->createTestExpressCompany();
        $activeCompany->setIsActive(true);
        self::getEntityManager()->persist($activeCompany);
        self::getEntityManager()->flush();

        // 测试按活跃状态过滤
        $client->request('GET', '/admin/order-refund/express-company', [
            'filters' => [
                'isActive' => '1',
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testDetailPage(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建测试数据
        $expressCompany = $this->createTestExpressCompany();
        self::getEntityManager()->persist($expressCompany);
        self::getEntityManager()->flush();

        // 访问详情页
        $client->request('GET', sprintf('/admin/order-refund/express-company/%d', $expressCompany->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.main-content', $expressCompany->getName() ?? '');
    }

    public function testEditPage(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建测试数据
        $expressCompany = $this->createTestExpressCompany();
        self::getEntityManager()->persist($expressCompany);
        self::getEntityManager()->flush();

        // 访问编辑页
        $client->request('GET', sprintf('/admin/order-refund/express-company/%d/edit', $expressCompany->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testPaginationAndSorting(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建多个测试数据
        for ($i = 1; $i <= 25; ++$i) {
            $company = $this->createTestExpressCompany();
            $company->setCode('TEST-' . $i);
            $company->setName('测试快递公司' . $i);
            $company->setSortOrder($i);
            self::getEntityManager()->persist($company);
        }
        self::getEntityManager()->flush();

        // 测试分页
        $client->request('GET', '/admin/order-refund/express-company', [
            'page' => 2,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.pagination');

        // 测试排序
        $client->request('GET', '/admin/order-refund/express-company', [
            'sort' => ['name' => 'ASC'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    /**
     * 查找提交按钮对应的表单
     */
    private function findSubmitForm(Crawler $crawler): Form
    {
        $buttonTexts = $this->extractButtonTexts($crawler);
        $form = $this->tryFindFormByButtons($crawler, ['Save', 'Save changes', '保存', 'Create', 'Submit']);

        if (null === $form) {
            self::fail('Could not find submit button. Available buttons: ' . implode(', ', $buttonTexts));
        }

        return $form;
    }

    /**
     * 提取所有按钮文本
     *
     * @return string[]
     */
    private function extractButtonTexts(Crawler $crawler): array
    {
        $buttonTexts = [];

        foreach ($crawler->filter('button') as $button) {
            $buttonTexts[] = trim($button->textContent);
        }

        foreach ($crawler->filter('input[type="submit"]') as $submit) {
            if ($submit instanceof \DOMElement) {
                $buttonTexts[] = $submit->getAttribute('value') ?? '';
            }
        }

        return array_filter($buttonTexts, static fn ($text) => '' !== $text);
    }

    /**
     * 尝试通过按钮文本查找表单
     *
     * @param string[] $buttonTexts
     */
    private function tryFindFormByButtons(Crawler $crawler, array $buttonTexts): ?Form
    {
        foreach ($buttonTexts as $buttonText) {
            try {
                return $crawler->selectButton($buttonText)->form();
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * 创建测试用的快递公司实体
     */
    private function createTestExpressCompany(): ExpressCompany
    {
        $company = new ExpressCompany();
        $company->setCode('TEST-' . uniqid());
        $company->setName('测试快递公司');
        $company->setDescription('测试用快递公司描述');
        $company->setTrackingUrlTemplate('https://example.com/track?no={trackingNumber}');
        $company->setIsActive(true);
        $company->setSortOrder(1);

        return $company;
    }
}
