<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;
use Tourze\OrderRefundBundle\Repository\ReturnAddressRepository;

/**
 * 寄回地址服务
 */
readonly class ReturnAddressService
{
    public function __construct(
        private ReturnAddressRepository $returnAddressRepository,
    ) {
    }

    /**
     * 为售后单获取合适的寄回地址
     */
    public function getReturnAddressForAftersales(?Aftersales $aftersales = null): ?ReturnAddress
    {
        // 优先返回默认地址
        $defaultAddress = $this->returnAddressRepository->findDefaultAddress();
        if (null !== $defaultAddress) {
            return $defaultAddress;
        }

        // 如果没有默认地址，返回第一个启用的地址
        return $this->returnAddressRepository->findFirstActiveAddress();
    }

    /**
     * 格式化地址为API返回格式
     * @return array<string, mixed>|null
     */
    public function formatAddressForApi(?ReturnAddress $address): ?array
    {
        if (null === $address) {
            return null;
        }

        return [
            'id' => $address->getId(),
            'name' => $address->getName(),
            'contactName' => $address->getContactName(),
            'contactPhone' => $address->getContactPhone(),
            'fullAddress' => $address->getFullAddress(),
            'province' => $address->getProvince(),
            'city' => $address->getCity(),
            'district' => $address->getDistrict(),
            'address' => $address->getAddress(),
            'zipCode' => $address->getZipCode(),
            'businessHours' => $address->getBusinessHours(),
            'specialInstructions' => $address->getSpecialInstructions(),
            'companyName' => $address->getCompanyName(),
        ];
    }

    /**
     * 验证地址数据完整性
     */
    public function validateAddressData(ReturnAddress $address): bool
    {
        // 检查必要字段是否已填写
        if (null === $address->getName() || '' === $address->getName()
            || null === $address->getContactName() || '' === $address->getContactName()
            || null === $address->getContactPhone() || '' === $address->getContactPhone()
            || null === $address->getProvince() || '' === $address->getProvince()
            || null === $address->getCity() || '' === $address->getCity()
            || null === $address->getAddress() || '' === $address->getAddress()
        ) {
            return false;
        }

        return true;
    }

    /**
     * 获取所有可用的寄回地址
     * @return array<ReturnAddress>
     */
    public function getAvailableAddresses(): array
    {
        return $this->returnAddressRepository->findActiveAddresses();
    }

    /**
     * 获取所有可用地址的API格式
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableAddressesForApi(): array
    {
        $addresses = $this->getAvailableAddresses();
        $result = [];

        foreach ($addresses as $address) {
            $formatted = $this->formatAddressForApi($address);
            if (null !== $formatted) {
                $result[] = $formatted;
            }
        }

        return $result;
    }

    /**
     * 设置默认地址
     */
    public function setDefaultAddress(ReturnAddress $address): void
    {
        $this->returnAddressRepository->setDefaultAddress($address);
    }

    /**
     * 按地区获取寄回地址
     * @return array<ReturnAddress>
     */
    public function getAddressesByRegion(string $province, ?string $city = null): array
    {
        return $this->returnAddressRepository->findByRegion($province, $city);
    }

    /**
     * 检查是否有可用的寄回地址
     */
    public function hasAvailableAddress(): bool
    {
        return $this->returnAddressRepository->countActiveAddresses() > 0;
    }

    /**
     * 获取默认地址，如果没有则获取第一个可用地址
     */
    public function getDefaultOrFirstAddress(): ?ReturnAddress
    {
        return $this->getReturnAddressForAftersales();
    }

    /**
     * 为API返回格式化的默认地址
     * @return array<string, mixed>|null
     */
    public function getDefaultAddressForApi(): ?array
    {
        $address = $this->getDefaultOrFirstAddress();

        return $this->formatAddressForApi($address);
    }

    /**
     * 创建新的寄回地址
     */
    public function createReturnAddress(
        string $name,
        string $contactName,
        string $contactPhone,
        string $province,
        string $city,
        string $address,
        ?string $district = null,
        ?string $zipCode = null,
        ?string $businessHours = null,
        ?string $specialInstructions = null,
        ?string $companyName = null,
        bool $isDefault = false,
        bool $isActive = true,
        int $sortOrder = 0,
    ): ReturnAddress {
        $returnAddress = new ReturnAddress();
        $returnAddress->setName($name);
        $returnAddress->setContactName($contactName);
        $returnAddress->setContactPhone($contactPhone);
        $returnAddress->setProvince($province);
        $returnAddress->setCity($city);
        $returnAddress->setAddress($address);
        $returnAddress->setDistrict($district);
        $returnAddress->setZipCode($zipCode);
        $returnAddress->setBusinessHours($businessHours);
        $returnAddress->setSpecialInstructions($specialInstructions);
        $returnAddress->setCompanyName($companyName);
        $returnAddress->setIsDefault($isDefault);
        $returnAddress->setIsActive($isActive);
        $returnAddress->setSortOrder($sortOrder);

        // 如果设置为默认地址，需要确保唯一性
        if ($isDefault) {
            $this->returnAddressRepository->setDefaultAddress($returnAddress);
        } else {
            $this->returnAddressRepository->save($returnAddress, true);
        }

        return $returnAddress;
    }

    /**
     * 根据名称查找地址
     */
    public function findByName(string $name): ?ReturnAddress
    {
        return $this->returnAddressRepository->findByName($name);
    }

    /**
     * 检查是否有默认地址
     */
    public function hasDefaultAddress(): bool
    {
        return null !== $this->returnAddressRepository->findDefaultAddress();
    }

    /**
     * 统计活跃地址数量
     */
    public function countActiveAddresses(): int
    {
        return $this->returnAddressRepository->countActiveAddresses();
    }
}
