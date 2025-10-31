<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\OrderRefundBundle\Repository\AftersalesOrderRepository;

/**
 * 售后订单快照实体
 */
#[ORM\Entity(repositoryClass: AftersalesOrderRepository::class)]
#[ORM\Table(name: 'order_aftersales_order', options: ['comment' => '售后订单快照表'])]
class AftersalesOrder implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\ManyToOne(targetEntity: Aftersales::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Aftersales $aftersales = null;

    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '订单编号'])]
    private ?string $orderNumber = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '订单状态'])]
    private ?string $orderStatus = null;

    #[Assert\NotNull]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '订单创建时间'])]
    private ?\DateTimeImmutable $orderCreateTime = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '用户ID'])]
    private ?string $userId = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '合同ID'])]
    private ?string $contractId = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '订单总金额'])]
    private ?string $totalAmount = null;

    /** @var array<string, mixed>|null */
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展信息'])]
    private ?array $extra = null;

    public function __toString(): string
    {
        return sprintf('订单快照 #%s (%s)', $this->getId(), $this->orderNumber ?? 'N/A');
    }

    public function getAftersales(): ?Aftersales
    {
        return $this->aftersales;
    }

    public function setAftersales(?Aftersales $aftersales): void
    {
        $this->aftersales = $aftersales;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getOrderStatus(): ?string
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(string $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    public function getOrderCreateTime(): ?\DateTimeImmutable
    {
        return $this->orderCreateTime;
    }

    public function setOrderCreateTime(\DateTimeImmutable $orderCreateTime): void
    {
        $this->orderCreateTime = $orderCreateTime;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExtra(): ?array
    {
        return $this->extra;
    }

    /**
     * @param array<string, mixed>|null $extra
     */
    public function setExtra(?array $extra): void
    {
        $this->extra = $extra;
    }

    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    public function setContractId(?string $contractId): void
    {
        $this->contractId = $contractId;
    }
}
