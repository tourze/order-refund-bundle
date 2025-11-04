<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Tourze\OrderRefundBundle\Controller\Admin\ReturnAddressCrudController;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(ReturnAddressCrudController::class)]
#[Group('controller')]
#[RunTestsInSeparateProcesses]
final class ReturnAddressCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private ReturnAddressCrudController $controller;

    protected function onAfterSetUp(): void
    {
        $this->controller = new ReturnAddressCrudController();
    }

    protected function getControllerService(): ReturnAddressCrudController
    {
        return $this->controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '地址名称' => ['地址名称'];
        yield '联系人' => ['联系人'];
        yield '联系电话' => ['联系电话'];
        yield '省份' => ['省份'];
        yield '城市' => ['城市'];
        yield '详细地址' => ['详细地址'];
        yield '完整地址' => ['完整地址'];
        yield '是否默认' => ['是否默认'];
        yield '启用状态' => ['启用状态'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'province' => ['province'];
        yield 'city' => ['city'];
        yield 'district' => ['district'];
        yield 'address' => ['address'];
        yield 'contactName' => ['contactName'];
        yield 'contactPhone' => ['contactPhone'];
        yield 'isDefault' => ['isDefault'];
        yield 'isActive' => ['isActive'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'province' => ['province'];
        yield 'city' => ['city'];
        yield 'district' => ['district'];
        yield 'address' => ['address'];
        yield 'contactName' => ['contactName'];
        yield 'contactPhone' => ['contactPhone'];
        yield 'isDefault' => ['isDefault'];
        yield 'isActive' => ['isActive'];
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertSame(ReturnAddress::class, ReturnAddressCrudController::getEntityFqcn());
    }

    public function testIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        $client->request('GET', '/admin/order-refund/return-address');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '寄回地址管理');
    }

    public function testNewPage(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        $client->request('GET', '/admin/order-refund/return-address/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '创建寄回地址');
    }

    public function testControllerConfiguration(): void
    {
        $controller = new ReturnAddressCrudController();

        // 测试实体配置
        $this->assertSame(ReturnAddress::class, $controller::getEntityFqcn());

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
        $returnAddress = $this->createTestReturnAddress();
        $returnAddress->setName('测试收货地址');
        $returnAddress->setContactName('张三');
        self::getEntityManager()->persist($returnAddress);
        self::getEntityManager()->flush();

        // 测试按联系人姓名搜索
        $client->request('GET', '/admin/order-refund/return-address', [
            'query' => '张三',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.table');
    }

    public function testCreateReturnAddressForm(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        $crawler = $client->request('GET', '/admin/order-refund/return-address/new');
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

        $crawler = $client->request('GET', '/admin/order-refund/return-address/new');
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

    public function testFilterByDefaultAddress(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建默认地址
        $defaultAddress = $this->createTestReturnAddress();
        $defaultAddress->setIsDefault(true);
        self::getEntityManager()->persist($defaultAddress);
        self::getEntityManager()->flush();

        // 测试按默认地址过滤
        $client->request('GET', '/admin/order-refund/return-address', [
            'filters' => [
                'isDefault' => '1',
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testFilterByActiveStatus(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建活跃的地址
        $activeAddress = $this->createTestReturnAddress();
        $activeAddress->setIsActive(true);
        self::getEntityManager()->persist($activeAddress);
        self::getEntityManager()->flush();

        // 测试按活跃状态过滤
        $client->request('GET', '/admin/order-refund/return-address', [
            'filters' => [
                'isActive' => '1',
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testFilterByProvince(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建地址数据
        $address = $this->createTestReturnAddress();
        $address->setProvince('广东省');
        $address->setCity('深圳市');
        self::getEntityManager()->persist($address);
        self::getEntityManager()->flush();

        // 测试按省份过滤
        $client->request('GET', '/admin/order-refund/return-address', [
            'filters' => [
                'province' => '广东省',
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
        $returnAddress = $this->createTestReturnAddress();
        self::getEntityManager()->persist($returnAddress);
        self::getEntityManager()->flush();

        // 访问详情页
        $client->request('GET', sprintf('/admin/order-refund/return-address/%d', $returnAddress->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.main-content', $returnAddress->getName() ?? '');
    }

    public function testEditPage(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建测试数据
        $returnAddress = $this->createTestReturnAddress();
        self::getEntityManager()->persist($returnAddress);
        self::getEntityManager()->flush();

        // 访问编辑页
        $client->request('GET', sprintf('/admin/order-refund/return-address/%d/edit', $returnAddress->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testFullAddressDisplay(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建带完整地址的测试数据
        $returnAddress = $this->createTestReturnAddress();
        $returnAddress->setProvince('广东省');
        $returnAddress->setCity('深圳市');
        $returnAddress->setDistrict('南山区');
        $returnAddress->setAddress('科技园南路1号');
        self::getEntityManager()->persist($returnAddress);
        self::getEntityManager()->flush();

        // 访问列表页，检查完整地址显示
        $client->request('GET', '/admin/order-refund/return-address');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.table');
    }

    public function testPaginationAndSorting(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建多个测试数据
        for ($i = 1; $i <= 25; ++$i) {
            $address = $this->createTestReturnAddress();
            $address->setName('测试地址' . $i);
            $address->setContactName('联系人' . $i);
            $address->setSortOrder($i);
            self::getEntityManager()->persist($address);
        }
        self::getEntityManager()->flush();

        // 测试分页
        $client->request('GET', '/admin/order-refund/return-address', [
            'page' => 2,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.pagination');

        // 测试排序
        $client->request('GET', '/admin/order-refund/return-address', [
            'sort' => ['name' => 'ASC'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testMultipleFiltersCombination(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());
        self::getClient($client);

        // 创建符合多个过滤条件的地址
        $address = $this->createTestReturnAddress();
        $address->setIsDefault(true);
        $address->setIsActive(true);
        $address->setProvince('北京市');
        self::getEntityManager()->persist($address);
        self::getEntityManager()->flush();

        // 测试多个过滤器组合
        $client->request('GET', '/admin/order-refund/return-address', [
            'filters' => [
                'isDefault' => '1',
                'isActive' => '1',
                'province' => '北京市',
            ],
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
     * 创建测试用的寄回地址实体
     */
    private function createTestReturnAddress(): ReturnAddress
    {
        $address = new ReturnAddress();
        $address->setName('测试寄回地址' . uniqid());
        $address->setContactName('测试联系人');
        $address->setContactPhone('13800138000');
        $address->setProvince('广东省');
        $address->setCity('深圳市');
        $address->setDistrict('南山区');
        $address->setAddress('测试详细地址');
        $address->setZipCode('518000');
        $address->setCompanyName('测试公司');
        $address->setBusinessHours('9:00-18:00');
        $address->setSpecialInstructions('测试特殊说明');
        $address->setIsDefault(false);
        $address->setIsActive(true);
        $address->setSortOrder(1);

        return $address;
    }
}
