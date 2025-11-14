<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;
use Tourze\OrderRefundBundle\Repository\AftersalesLogRepository;

/**
 * 售后操作日志实体
 */
#[ORM\Entity(repositoryClass: AftersalesLogRepository::class)]
#[ORM\Table(name: 'order_aftersales_logs', options: ['comment' => '售后操作日志表'])]
#[ORM\Index(columns: ['operator_type', 'operator_id'], name: 'order_aftersales_logs_log_operator')]
class AftersalesLog implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;
    use AftersalesLogExtensions;
    use IpTraceableAware;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[ORM\ManyToOne(targetEntity: Aftersales::class)]
    #[ORM\JoinColumn(name: 'aftersales_id', nullable: false)]
    private ?Aftersales $aftersales = null;

    #[IndexColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AftersalesLogAction::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'action', length: 30, enumType: AftersalesLogAction::class, options: ['comment' => '操作动作'])]
    private ?AftersalesLogAction $action = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[ORM\Column(type: Types::STRING, name: 'operator_type', length: 20, options: ['comment' => '操作者类型'])]
    private ?string $operatorType = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 64)]
    #[ORM\Column(type: Types::STRING, name: 'operator_id', length: 64, nullable: true, options: ['comment' => '操作者ID'])]
    private ?string $operatorId = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, name: 'operator_name', length: 100, nullable: true, options: ['comment' => '操作者名称'])]
    private ?string $operatorName = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'previous_state', length: 50, nullable: true, options: ['comment' => '变更前状态'])]
    private ?string $previousState = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'current_state', length: 50, nullable: true, options: ['comment' => '变更后状态'])]
    private ?string $currentState = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'content', length: 500, options: ['comment' => '操作内容描述'])]
    private ?string $content = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, name: 'context_data', nullable: true, options: ['comment' => '上下文数据'])]
    private ?array $contextData = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'remark', length: 500, nullable: true, options: ['comment' => '备注信息'])]
    private ?string $remark = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?UserInterface $user = null;

    public function __toString(): string
    {
        $actionValue = null !== $this->action ? $this->action->value : 'UNKNOWN';

        return sprintf('[%s] %s', $actionValue, $this->content ?? '');
    }

    public function getAftersales(): ?Aftersales
    {
        return $this->aftersales;
    }

    public function setAftersales(?Aftersales $aftersales): void
    {
        $this->aftersales = $aftersales;
    }

    public function getAction(): ?AftersalesLogAction
    {
        return $this->action;
    }

    public function setAction(AftersalesLogAction $action): void
    {
        $this->action = $action;
    }

    public function getOperatorType(): ?string
    {
        return $this->operatorType;
    }

    public function setOperatorType(string $operatorType): void
    {
        $this->operatorType = $operatorType;
    }

    public function getOperatorId(): ?string
    {
        return $this->operatorId;
    }

    public function setOperatorId(?string $operatorId): void
    {
        $this->operatorId = $operatorId;
    }

    public function getOperatorName(): ?string
    {
        return $this->operatorName;
    }

    public function setOperatorName(?string $operatorName): void
    {
        $this->operatorName = $operatorName;
    }

    public function getPreviousState(): ?string
    {
        return $this->previousState;
    }

    public function setPreviousState(?string $previousState): void
    {
        $this->previousState = $previousState;
    }

    public function getCurrentState(): ?string
    {
        return $this->currentState;
    }

    public function setCurrentState(?string $currentState): void
    {
        $this->currentState = $currentState;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContextData(): ?array
    {
        return $this->contextData;
    }

    /**
     * @param array<string, mixed>|null $contextData
     */
    public function setContextData(?array $contextData): void
    {
        $this->contextData = $contextData;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * 设置系统操作者
     */
    public function setSystemOperator(string $systemName = 'SYSTEM'): void
    {
        $this->operatorType = 'SYSTEM';
        $this->operatorName = $systemName;
    }

    /**
     * 设置用户操作者
     */
    public function setUserOperator(UserInterface $user): void
    {
        $this->operatorType = 'USER';
        $this->operatorId = $user->getUserIdentifier();
        $this->operatorName = $user->getUserIdentifier();
        $this->user = $user;
    }

    /**
     * 设置管理员操作者
     */
    public function setAdminOperator(string $adminId, string $adminName): void
    {
        $this->operatorType = 'ADMIN';
        $this->operatorId = $adminId;
        $this->operatorName = $adminName;
    }

    /**
     * 设置状态变更信息
     */
    public function setStateChange(?string $previousState, ?string $currentState): void
    {
        $this->previousState = $previousState;
        $this->currentState = $currentState;
    }

    /**
     * 添加上下文数据
     */
    public function addContextData(string $key, mixed $value): self
    {
        if (null === $this->contextData) {
            $this->contextData = [];
        }

        $this->contextData[$key] = $value;

        return $this;
    }

    /**
     * 检查是否为系统操作
     */
    public function isSystemOperation(): bool
    {
        return 'SYSTEM' === $this->operatorType;
    }

    /**
     * 检查是否为用户操作
     */
    public function isUserOperation(): bool
    {
        return 'USER' === $this->operatorType;
    }

    /**
     * 检查是否为管理员操作
     */
    public function isAdminOperation(): bool
    {
        return 'ADMIN' === $this->operatorType;
    }

    /**
     * 获取状态变更摘要（EasyAdmin 字段使用）
     */
    public function getStateChange(): string
    {
        if (null !== $this->previousState && null !== $this->currentState) {
            return sprintf('%s → %s', $this->previousState, $this->currentState);
        }

        return $this->currentState ?? '-';
    }

    /**
     * 获取内容摘要（EasyAdmin 字段使用）
     */
    public function getContentSummary(): string
    {
        if (null === $this->content) {
            return '';
        }

        return mb_strlen($this->content) > 50 ? mb_substr($this->content, 0, 50) . '...' : $this->content;
    }

    /**
     * 获取备注摘要（EasyAdmin 字段使用）
     */
    public function getRemarkSummary(): string
    {
        if (null === $this->remark) {
            return '';
        }

        return mb_strlen($this->remark) > 30 ? mb_substr($this->remark, 0, 30) . '...' : $this->remark;
    }
}
