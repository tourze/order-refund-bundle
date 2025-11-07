<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesLog;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AftersalesLog::class)]
class AftersalesLogTest extends AbstractEntityTestCase
{
    protected function setUp(): void
    {
    }

    protected function createEntity(): AftersalesLog
    {
        return new AftersalesLog();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        return [
            'action' => ['action', AftersalesLogAction::CREATE],
            'operatorType' => ['operatorType', 'USER'],
            'operatorId' => ['operatorId', '12345'],
            'operatorName' => ['operatorName', '测试用户'],
            'previousState' => ['previousState', 'DRAFT'],
            'currentState' => ['currentState', 'PENDING'],
            'content' => ['content', '创建售后申请'],
            'remark' => ['remark', '测试备注'],
        ];
    }

    public function testAftersalesLogCreation(): void
    {
        $log = new AftersalesLog();

        self::assertNull($log->getId());
        self::assertNull($log->getAftersales());
        self::assertNull($log->getAction());
        self::assertNull($log->getOperatorType());
        self::assertNull($log->getOperatorId());
        self::assertNull($log->getOperatorName());
        self::assertNull($log->getPreviousState());
        self::assertNull($log->getCurrentState());
        self::assertNull($log->getContent());
        self::assertNull($log->getContextData());
        self::assertNull($log->getRemark());
        self::assertNull($log->getUser());
    }

    public function testBasicSettersAndGetters(): void
    {
        $log = new AftersalesLog();
        $aftersales = new Aftersales();
        $action = AftersalesLogAction::CREATE;
        $user = $this->createMock(UserInterface::class);

        $log->setAftersales($aftersales);
        $log->setAction($action);
        $log->setOperatorType('USER');
        $log->setOperatorId('12345');
        $log->setOperatorName('测试用户');
        $log->setPreviousState('DRAFT');
        $log->setCurrentState('PENDING');
        $log->setContent('创建售后申请');
        $log->setContextData(['key' => 'value']);
        $log->setRemark('测试备注');
        $log->setUser($user);

        self::assertSame($aftersales, $log->getAftersales());
        self::assertSame($action, $log->getAction());
        self::assertSame('USER', $log->getOperatorType());
        self::assertSame('12345', $log->getOperatorId());
        self::assertSame('测试用户', $log->getOperatorName());
        self::assertSame('DRAFT', $log->getPreviousState());
        self::assertSame('PENDING', $log->getCurrentState());
        self::assertSame('创建售后申请', $log->getContent());
        self::assertSame(['key' => 'value'], $log->getContextData());
        self::assertSame('测试备注', $log->getRemark());
        self::assertSame($user, $log->getUser());
    }

    public function testSetSystemOperator(): void
    {
        $log = new AftersalesLog();

        $log->setSystemOperator('AUTO_SYSTEM');

        self::assertSame('SYSTEM', $log->getOperatorType());
        self::assertSame('AUTO_SYSTEM', $log->getOperatorName());
    }

    public function testSetSystemOperatorWithDefaultName(): void
    {
        $log = new AftersalesLog();

        $log->setSystemOperator();

        self::assertSame('SYSTEM', $log->getOperatorType());
        self::assertSame('SYSTEM', $log->getOperatorName());
    }

    public function testSetUserOperator(): void
    {
        $log = new AftersalesLog();
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('testuser');

        $log->setUserOperator($user);

        self::assertSame('USER', $log->getOperatorType());
        self::assertSame('testuser', $log->getOperatorName());
        self::assertSame($user, $log->getUser());
    }

    public function testSetAdminOperator(): void
    {
        $log = new AftersalesLog();

        $log->setAdminOperator('admin123', '管理员');

        self::assertSame('ADMIN', $log->getOperatorType());
        self::assertSame('admin123', $log->getOperatorId());
        self::assertSame('管理员', $log->getOperatorName());
    }

    public function testSetStateChange(): void
    {
        $log = new AftersalesLog();

        $log->setStateChange('PENDING', 'APPROVED');

        self::assertSame('PENDING', $log->getPreviousState());
        self::assertSame('APPROVED', $log->getCurrentState());
    }

    public function testSetStateChangeWithNullValues(): void
    {
        $log = new AftersalesLog();

        $log->setStateChange(null, 'APPROVED');

        self::assertNull($log->getPreviousState());
        self::assertSame('APPROVED', $log->getCurrentState());
    }

    public function testAddContextData(): void
    {
        $log = new AftersalesLog();

        $log->addContextData('userId', 123);
        $log->addContextData('reason', 'timeout');

        $contextData = $log->getContextData();
        self::assertIsArray($contextData);
        self::assertSame(123, $contextData['userId']);
        self::assertSame('timeout', $contextData['reason']);
    }

    public function testAddContextDataToEmptyContext(): void
    {
        $log = new AftersalesLog();

        self::assertNull($log->getContextData());

        $log->addContextData('test', 'value');

        self::assertSame(['test' => 'value'], $log->getContextData());
    }

    public function testIsSystemOperation(): void
    {
        $log = new AftersalesLog();

        self::assertFalse($log->isSystemOperation());

        $log->setSystemOperator();

        self::assertTrue($log->isSystemOperation());
    }

    public function testIsUserOperation(): void
    {
        $log = new AftersalesLog();

        self::assertFalse($log->isUserOperation());

        $log->setOperatorType('USER');

        self::assertTrue($log->isUserOperation());
    }

    public function testIsAdminOperation(): void
    {
        $log = new AftersalesLog();

        self::assertFalse($log->isAdminOperation());

        $log->setAdminOperator('admin1', 'Admin User');

        self::assertTrue($log->isAdminOperation());
    }

    public function testToStringWithAction(): void
    {
        $log = new AftersalesLog();
        $log->setAction(AftersalesLogAction::CREATE);
        $log->setContent('创建售后申请');

        $result = (string) $log;

        self::assertSame('[CREATE] 创建售后申请', $result);
    }

    public function testToStringWithoutAction(): void
    {
        $log = new AftersalesLog();
        $log->setContent('测试内容');

        $result = (string) $log;

        self::assertSame('[UNKNOWN] 测试内容', $result);
    }

    public function testToStringWithoutContent(): void
    {
        $log = new AftersalesLog();
        $log->setAction(AftersalesLogAction::APPROVE);

        $result = (string) $log;

        self::assertSame('[APPROVE] ', $result);
    }

    public function testOperatorTypeDetectionAccuracy(): void
    {
        $log = new AftersalesLog();

        // 测试只有一种操作类型被识别为真
        $log->setOperatorType('USER');
        self::assertTrue($log->isUserOperation());
        self::assertFalse($log->isSystemOperation());
        self::assertFalse($log->isAdminOperation());

        $log->setOperatorType('SYSTEM');
        self::assertFalse($log->isUserOperation());
        self::assertTrue($log->isSystemOperation());
        self::assertFalse($log->isAdminOperation());

        $log->setOperatorType('ADMIN');
        self::assertFalse($log->isUserOperation());
        self::assertFalse($log->isSystemOperation());
        self::assertTrue($log->isAdminOperation());
    }

    public function testSettersCombination(): void
    {
        $log = new AftersalesLog();
        $aftersales = new Aftersales();

        // 使用独立的setter调用而不是链式调用
        $log->setAftersales($aftersales);
        $log->setAction(AftersalesLogAction::APPROVE);
        $log->setContent('审批通过');
        $log->setSystemOperator();
        $log->setStateChange('PENDING', 'APPROVED');
        $log->addContextData('reviewerId', 'admin123');

        // 验证所有设置都正确应用
        self::assertSame($aftersales, $log->getAftersales());
        self::assertSame(AftersalesLogAction::APPROVE, $log->getAction());
        self::assertSame('审批通过', $log->getContent());
        self::assertTrue($log->isSystemOperation());
        self::assertSame('PENDING', $log->getPreviousState());
        self::assertSame('APPROVED', $log->getCurrentState());
        $contextData = $log->getContextData();
        self::assertIsArray($contextData);
        self::assertSame('admin123', $contextData['reviewerId']);
    }
}
