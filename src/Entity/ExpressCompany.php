<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;

/**
 * 快递公司实体
 */
#[ORM\Entity(repositoryClass: ExpressCompanyRepository::class)]
#[ORM\Table(name: 'order_express_companies', options: ['comment' => '快递公司表'])]
#[ORM\Index(columns: ['is_active', 'sort_order'], name: 'order_express_companies_express_active_sort')]
class ExpressCompany implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[ORM\Column(type: Types::STRING, name: 'code', length: 20, unique: true, options: ['comment' => '快递公司代码'])]
    private ?string $code = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, name: 'name', length: 100, options: ['comment' => '快递公司名称'])]
    private ?string $name = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'tracking_url_template', length: 500, nullable: true, options: ['comment' => '物流查询URL模板'])]
    private ?string $trackingUrlTemplate = null;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, name: 'is_active', options: ['default' => true, 'comment' => '是否启用'])]
    private bool $isActive = true;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'integer')]
    #[Assert\Range(min: 0, max: 9999)]
    #[ORM\Column(type: Types::INTEGER, name: 'sort_order', options: ['default' => 0, 'comment' => '排序序号'])]
    private int $sortOrder = 0;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'description', length: 500, nullable: true, options: ['comment' => '描述信息'])]
    private ?string $description = null;

    public function __toString(): string
    {
        return $this->name ?? $this->code ?? (string) $this->getId();
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTrackingUrlTemplate(): ?string
    {
        return $this->trackingUrlTemplate;
    }

    public function setTrackingUrlTemplate(?string $trackingUrlTemplate): void
    {
        $this->trackingUrlTemplate = $trackingUrlTemplate;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
