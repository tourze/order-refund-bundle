<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;

/**
 * 售后日志扩展 - 添加OMS同步相关字段和方法
 */
trait AftersalesLogExtensions
{
    /** @var string|null 描述 */
    #[ORM\Column(type: Types::STRING, name: 'description', length: 500, nullable: true, options: ['comment' => '描述'])]
    #[Assert\Length(max: 500)]
    private ?string $description = null;

    /** @var string|null 操作人 */
    #[ORM\Column(type: Types::STRING, name: 'operator', length: 100, nullable: true, options: ['comment' => '操作人'])]
    #[Assert\Length(max: 100)]
    private ?string $operator = null;

    /** @var \DateTimeImmutable|null 操作时间 */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'operation_time', nullable: true, options: ['comment' => '操作时间'])]
    private ?\DateTimeImmutable $operationTime = null;

    /** @var array<string, mixed>|null 详细信息 */
    #[ORM\Column(type: Types::JSON, name: 'details', nullable: true, options: ['comment' => '详细信息'])]
    private ?array $details = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function setOperator(?string $operator): void
    {
        $this->operator = $operator;
    }

    public function getOperationTime(): ?\DateTimeImmutable
    {
        return $this->operationTime;
    }

    public function setOperationTime(?\DateTimeImmutable $operationTime): void
    {
        $this->operationTime = $operationTime;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public function setDetails(?array $details): void
    {
        $this->details = $details;
    }

    public function setAftersales(?Aftersales $aftersales): void
    {
        if (property_exists($this, 'aftersales')) {
            $this->aftersales = $aftersales;
        }
    }

    public function setAction(AftersalesLogAction $action): void
    {
        if (property_exists($this, 'action')) {
            $this->action = $action;
        }
    }
}
