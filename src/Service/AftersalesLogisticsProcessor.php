<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * 售后物流处理器
 * 负责处理售后相关的物流信息
 */
readonly class AftersalesLogisticsProcessor
{
    /**
     * 处理退货物流信息
     */
    public function processReturnLogistics(Aftersales $aftersales, mixed $logistics): void
    {
        if (!is_array($logistics)) {
            return;
        }

        /** @var array<string, mixed> $logisticsData */
        $logisticsData = $logistics;
        $logisticsInfo = $this->extractLogisticsInfo($logisticsData);

        if (!$this->isValidLogisticsInfo($logisticsInfo)) {
            return;
        }

        $this->setLogisticsData($aftersales, $logisticsInfo);
        $this->setReturnTime($aftersales, $logisticsInfo['returnTime']);
    }

    /**
     * 处理换货地址信息
     */
    public function processExchangeAddress(Aftersales $aftersales, mixed $address): void
    {
        if (!is_array($address)) {
            return;
        }

        /** @var array<string, mixed> $addressData */
        $addressData = $address;

        $aftersales->setExchangeAddress([
            'name' => $this->extractStringValue($addressData, 'name', ''),
            'phone' => $this->extractStringValue($addressData, 'phone', ''),
            'province' => $this->extractStringValue($addressData, 'province', ''),
            'city' => $this->extractStringValue($addressData, 'city', ''),
            'district' => $this->extractStringValue($addressData, 'district', ''),
            'address' => $this->extractStringValue($addressData, 'address', ''),
            'zipCode' => $this->extractStringValue($addressData, 'zipCode', ''),
        ]);
    }

    /**
     * 提取物流信息
     * @param array<string, mixed> $logistics
     * @return array{company: string, trackingNumber: string, returnTime: string}
     */
    private function extractLogisticsInfo(array $logistics): array
    {
        return [
            'company' => $this->extractStringValue($logistics, 'company', ''),
            'trackingNumber' => $this->extractStringValue($logistics, 'trackingNumber', ''),
            'returnTime' => $this->extractStringValue($logistics, 'returnTime', ''),
        ];
    }

    /**
     * 验证物流信息是否有效
     * @param array{company: string, trackingNumber: string, returnTime: string} $logisticsInfo
     */
    private function isValidLogisticsInfo(array $logisticsInfo): bool
    {
        return '' !== $logisticsInfo['company'] && '' !== $logisticsInfo['trackingNumber'];
    }

    /**
     * 设置物流数据
     * @param array{company: string, trackingNumber: string, returnTime: string} $logisticsInfo
     */
    private function setLogisticsData(Aftersales $aftersales, array $logisticsInfo): void
    {
        $aftersales->setReturnExpressCompany($logisticsInfo['company']);
        $aftersales->setReturnExpressNumber($logisticsInfo['trackingNumber']);
    }

    /**
     * 设置退货时间
     */
    private function setReturnTime(Aftersales $aftersales, string $returnTime): void
    {
        if ('' !== $returnTime) {
            $aftersales->setReturnShippingTime(new \DateTimeImmutable($returnTime));
        }
    }

    /**
     * 安全提取字符串值
     * @param array<string, mixed> $data
     * @param string $key
     * @param string $default
     */
    private function extractStringValue(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }
}
