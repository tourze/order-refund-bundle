<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DTO;

/**
 * 订单数据传输对象
 */
class OrderDataDTO
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly string $orderStatus,
        public readonly \DateTimeInterface $orderCreateTime,
        public readonly string $userId,
        public readonly float $totalAmount,
        /** @var array<string, mixed>|null */
        public readonly ?array $extra = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $orderCreateTime = $data['orderCreateTime'] ?? null;
        if (is_string($orderCreateTime)) {
            $orderCreateTime = new \DateTime($orderCreateTime);
        } elseif (!$orderCreateTime instanceof \DateTimeInterface) {
            throw new \InvalidArgumentException('orderCreateTime must be a string or DateTimeInterface');
        }

        $extra = $data['extra'] ?? null;
        /** @var array<string, mixed>|null $validExtra */
        $validExtra = is_array($extra) ? $extra : null;

        return new self(
            orderNumber: self::ensureString($data['orderNumber'] ?? '', 'orderNumber'),
            orderStatus: self::ensureString($data['orderStatus'] ?? '', 'orderStatus'),
            orderCreateTime: $orderCreateTime,
            userId: self::ensureString($data['userId'] ?? '', 'userId'),
            totalAmount: self::ensureFloat($data['totalAmount'] ?? 0, 'totalAmount'),
            extra: $validExtra
        );
    }

    private static function ensureString(mixed $value, string $fieldName): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("Field {$fieldName} must be a string, " . gettype($value) . ' given');
        }

        return $value;
    }

    private static function ensureFloat(mixed $value, string $fieldName): float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Field {$fieldName} must be numeric, " . gettype($value) . ' given');
        }

        return (float) $value;
    }

    /**
     * @return array<string>
     */
    public function validate(): array
    {
        $errors = [];

        if ('' === $this->orderNumber) {
            $errors[] = '订单编号不能为空';
        }

        if ('' === $this->orderStatus) {
            $errors[] = '订单状态不能为空';
        }

        if ('' === $this->userId) {
            $errors[] = '用户ID不能为空';
        }

        if ($this->totalAmount < 0) {
            $errors[] = '订单金额不能为负数';
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'orderNumber' => $this->orderNumber,
            'orderStatus' => $this->orderStatus,
            'orderCreateTime' => $this->orderCreateTime->format('Y-m-d H:i:s'),
            'userId' => $this->userId,
            'totalAmount' => $this->totalAmount,
            'extra' => $this->extra,
        ];
    }
}
