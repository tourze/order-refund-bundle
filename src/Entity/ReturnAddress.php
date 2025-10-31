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
use Tourze\OrderRefundBundle\Repository\ReturnAddressRepository;

/**
 * 寄回地址实体
 */
#[ORM\Entity(repositoryClass: ReturnAddressRepository::class)]
#[ORM\Table(name: 'order_return_addresses', options: ['comment' => '寄回地址表'])]
#[ORM\Index(columns: ['is_active', 'sort_order'], name: 'order_return_addresses_return_address_active_sort')]
#[ORM\Index(columns: ['province', 'city'], name: 'order_return_addresses_return_address_region')]
class ReturnAddress implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, name: 'name', length: 100, options: ['comment' => '地址名称/标识'])]
    private ?string $name = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'contact_name', length: 50, options: ['comment' => '联系人姓名'])]
    private ?string $contactName = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[ORM\Column(type: Types::STRING, name: 'contact_phone', length: 20, options: ['comment' => '联系电话'])]
    private ?string $contactPhone = null;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'province', length: 50, options: ['comment' => '省份'])]
    private ?string $province = null;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'city', length: 50, options: ['comment' => '城市'])]
    private ?string $city = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'district', length: 50, nullable: true, options: ['comment' => '区县'])]
    private ?string $district = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[ORM\Column(type: Types::STRING, name: 'address', length: 200, options: ['comment' => '详细地址'])]
    private ?string $address = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 10)]
    #[ORM\Column(type: Types::STRING, name: 'zip_code', length: 10, nullable: true, options: ['comment' => '邮政编码'])]
    private ?string $zipCode = null;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, name: 'is_default', options: ['default' => false, 'comment' => '是否默认地址'])]
    private bool $isDefault = false;

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
    #[Assert\Length(max: 200)]
    #[ORM\Column(type: Types::STRING, name: 'business_hours', length: 200, nullable: true, options: ['comment' => '营业时间说明'])]
    private ?string $businessHours = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'special_instructions', length: 500, nullable: true, options: ['comment' => '特殊说明'])]
    private ?string $specialInstructions = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, name: 'company_name', length: 100, nullable: true, options: ['comment' => '公司名称'])]
    private ?string $companyName = null;

    public function __toString(): string
    {
        return $this->name ?? $this->getFullAddress();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(string $province): void
    {
        $this->province = $province;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): void
    {
        $this->district = $district;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
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

    public function getBusinessHours(): ?string
    {
        return $this->businessHours;
    }

    public function setBusinessHours(?string $businessHours): void
    {
        $this->businessHours = $businessHours;
    }

    public function getSpecialInstructions(): ?string
    {
        return $this->specialInstructions;
    }

    public function setSpecialInstructions(?string $specialInstructions): void
    {
        $this->specialInstructions = $specialInstructions;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * 获取完整地址字符串
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->province,
            $this->city,
            $this->district,
            $this->address,
        ], static fn ($value): bool => null !== $value && '' !== $value);

        return implode('', $parts);
    }
}
