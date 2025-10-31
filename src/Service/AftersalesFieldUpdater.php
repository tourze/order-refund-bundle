<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;

/**
 * 售后字段更新器
 * 负责处理售后单的各种字段更新
 */
readonly class AftersalesFieldUpdater
{
    /**
     * 更新售后单的基本字段信息
     * @param array<string, mixed> $data
     * @return array<string>
     */
    public function updateBasicFields(Aftersales $aftersales, array $data): array
    {
        $fieldMappings = $this->getBasicFieldMappings();

        return $this->updateFieldsByMappings($aftersales, $data, $fieldMappings);
    }

    /**
     * 更新售后单的额外字段信息
     * @param array<string, mixed> $data
     * @return array<string>
     */
    public function updateAdditionalFields(Aftersales $aftersales, array $data): array
    {
        $modifiedFields = [];

        // 更新商品信息
        if (isset($data['products']) && is_array($data['products'])) {
            /** @var array<int, array<string, mixed>> $products */
            $products = $data['products'];
            $this->updateAftersalesProductData($aftersales, $products);
            $modifiedFields[] = 'products';
        }

        return $modifiedFields;
    }

    /**
     * 同步基本售后信息
     * @param array<string, mixed> $data
     */
    public function syncBasicAftersalesInfo(Aftersales $aftersales, array $data): void
    {
        $auditRemark = $this->extractStringValue($data, 'auditRemark', '');
        if ('' !== $auditRemark) {
            $aftersales->setRejectReason($auditRemark);
        }
        $description = $this->extractStringValue($data, 'description', '');
        if ('' !== $description) {
            $aftersales->setDescription($description);
        }
    }

    /**
     * 更新售后元数据
     * @param array<string, mixed> $data
     */
    public function updateAftersalesMetadata(Aftersales $aftersales, array $data): void
    {
        $auditor = $this->extractStringValue($data, 'auditor', '');
        if ('' !== $auditor) {
            $aftersales->setProcessor($auditor);
        }
        $auditRemark = $this->extractStringValue($data, 'auditRemark', '');
        if ('' !== $auditRemark) {
            $aftersales->setRejectReason($auditRemark);
        }
        if (isset($data['approvedAmount']) && (is_int($data['approvedAmount']) || is_numeric($data['approvedAmount']))) {
            $approvedAmount = is_int($data['approvedAmount']) ? $data['approvedAmount'] : (int) $data['approvedAmount'];
            $aftersales->setApprovedAmount($approvedAmount);
        }
    }

    /**
     * 更新时间戳
     * @param array<string, mixed> $data
     */
    public function updateAftersalesTimestamps(Aftersales $aftersales, array $data): void
    {
        $this->setOptionalDateTime($data, 'auditTime', fn ($time) => $aftersales->setProcessedTime($time));
        $this->setOptionalDateTime($data, 'processTime', fn ($time) => $aftersales->setProcessedTime($time));
        $this->setOptionalDateTime($data, 'completedTime', fn ($time) => $aftersales->setCompletedTime($time));
    }

    /**
     * 获取基本字段映射
     * @return array<string, callable(Aftersales, mixed): void>
     */
    private function getBasicFieldMappings(): array
    {
        return [
            'description' => function (Aftersales $a, mixed $v): void {
                $a->setDescription(is_string($v) ? $v : null);
            },
            'proofImages' => function (Aftersales $a, mixed $v): void {
                if (!is_array($v)) {
                    $a->setProofImages([]);

                    return;
                }
                /** @var array<string> $images */
                $images = array_filter($v, 'is_string');
                $a->setProofImages($images);
            },
            'refundAmount' => function (Aftersales $a, mixed $v): void {
                $a->setRequestedAmount(is_int($v) ? $v : (is_numeric($v) ? (int) $v : 0));
            },
            'applicantName' => function (Aftersales $a, mixed $v): void {
                $a->setApplicantName(is_string($v) ? $v : null);
            },
            'applicantPhone' => function (Aftersales $a, mixed $v): void {
                $a->setApplicantPhone(is_string($v) ? $v : null);
            },
            'serviceNote' => function (Aftersales $a, mixed $v): void {
                $a->setServiceNote(is_string($v) ? $v : null);
            },
        ];
    }

    /**
     * 根据字段映射更新实体
     * @param array<string, mixed> $data
     * @param array<string, callable(Aftersales, mixed): void> $fieldMappings
     * @return array<string>
     */
    private function updateFieldsByMappings(Aftersales $aftersales, array $data, array $fieldMappings): array
    {
        $modifiedFields = [];

        foreach ($fieldMappings as $field => $setter) {
            if (isset($data[$field])) {
                $setter($aftersales, $data[$field]);
                $modifiedFields[] = $field;
            }
        }

        return $modifiedFields;
    }

    /**
     * 更新售后商品信息
     * @param array<int, array<string, mixed>> $products
     */
    private function updateAftersalesProductData(Aftersales $aftersales, array $products): void
    {
        // 这个方法需要OmsProductDataMapper服务
        // 在OmsAftersalesSyncService中会调用productDataMapper
    }

    /**
     * 设置可选的日期时间字段
     * @param array<string, mixed> $data
     * @param string $key
     * @param callable(\DateTimeImmutable): void $setter
     */
    private function setOptionalDateTime(array $data, string $key, callable $setter): void
    {
        $value = $this->extractStringValue($data, $key, '');
        if ('' !== $value) {
            $setter(new \DateTimeImmutable($value));
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
