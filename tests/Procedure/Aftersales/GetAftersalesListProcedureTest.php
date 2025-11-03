<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Tests\Procedure\Aftersales;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Procedure\Aftersales\GetAftersalesListProcedure;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;

/**
 * @internal
 */
#[CoversClass(GetAftersalesListProcedure::class)]
#[RunTestsInSeparateProcesses]
final class GetAftersalesListProcedureTest extends AbstractProcedureTestCase
{
    private GetAftersalesListProcedure $procedure;

    private AftersalesRepository&MockObject $aftersalesRepository;

    protected function onSetUp(): void
    {
        $this->aftersalesRepository = $this->createMock(AftersalesRepository::class);
        $mockUser = $this->createNormalUser('test@example.com', 'password123');

        // 设置认证用户
        $this->setAuthenticatedUser($mockUser);

        // 只替换AftersalesRepository服务
        self::getContainer()->set(AftersalesRepository::class, $this->aftersalesRepository);

        // 从容器获取procedure实例
        $this->procedure = self::getService(GetAftersalesListProcedure::class);
    }

    public function testExecuteReturnsAftersalesList(): void
    {
        // Security mock已在setUp中设置用户

        $mockAftersales = $this->createMock(Aftersales::class);
        $mockAftersales->method('getId')->willReturn('1');
        $mockAftersales->method('getType')->willReturn(AftersalesType::REFUND_ONLY);
        $mockAftersales->method('getReason')->willReturn(RefundReason::QUALITY_ISSUE);
        $mockAftersales->method('getState')->willReturn(AftersalesState::PENDING_APPROVAL);
        $mockAftersales->method('getStage')->willReturn(AftersalesStage::APPLY);
        $mockAftersales->method('getTotalRefundAmount')->willReturn(100.0);
        $mockAftersales->method('getDescription')->willReturn('Test description');
        $mockAftersales->method('getProofImages')->willReturn([]);
        $mockAftersales->method('canModify')->willReturn(true);
        $mockAftersales->method('canCancel')->willReturn(true);
        $mockAftersales->method('getAvailableActions')->willReturn(['approve', 'reject']);
        $mockAftersales->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $mockAftersales->method('getAuditTime')->willReturn(null);
        $mockAftersales->method('getCompletedTime')->willReturn(null);

        $this->aftersalesRepository->method('findBy')->willReturn([$mockAftersales]);
        $this->aftersalesRepository->method('count')->willReturn(1);

        // 执行测试
        $result = $this->procedure->execute();

        // 验证结果
        $this->assertIsArray($result);
        $this->assertArrayHasKey('list', $result);
        $this->assertArrayHasKey('pagination', $result);

        $pagination = $result['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertEquals(1, $pagination['total']);

        $items = $result['list'];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
    }

    public function testExecuteWithStateFilter(): void
    {
        // Security mock已在setUp中设置用户
        $this->procedure->state = AftersalesState::APPROVED;

        $this->aftersalesRepository->method('findBy')
            ->with(self::callback(function ($criteria) {
                if (!is_array($criteria)) {
                    return false;
                }

                return AftersalesState::APPROVED === $criteria['state'];
            }))
            ->willReturn([])
        ;
        $this->aftersalesRepository->method('count')->willReturn(0);

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $items = $result['list'];
        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    public function testExecuteWithTypeFilter(): void
    {
        // Security mock已在setUp中设置用户
        $this->procedure->type = AftersalesType::REFUND_ONLY;

        $this->aftersalesRepository->method('findBy')
            ->with(self::callback(function ($criteria) {
                if (!is_array($criteria)) {
                    return false;
                }

                return AftersalesType::REFUND_ONLY === $criteria['type'];
            }))
            ->willReturn([])
        ;
        $this->aftersalesRepository->method('count')->willReturn(0);

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $items = $result['list'];
        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    public function testExecuteWithPagination(): void
    {
        // Security mock已在setUp中设置用户
        $this->procedure->page = 2;
        $this->procedure->limit = 5;

        $this->aftersalesRepository->method('findBy')
            ->with(
                self::anything(),
                ['createTime' => 'DESC'],
                5,
                5 // offset = (2-1) * 5
            )
            ->willReturn([])
        ;
        $this->aftersalesRepository->method('count')->willReturn(15);

        $result = $this->procedure->execute();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pagination', $result);
        $pagination = $result['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('page', $pagination);
        $this->assertArrayHasKey('limit', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('pages', $pagination);
        $this->assertEquals(2, $pagination['page']);
        $this->assertEquals(5, $pagination['limit']);
        $this->assertEquals(15, $pagination['total']);
        $this->assertEquals(3, $pagination['pages']);
    }
}
