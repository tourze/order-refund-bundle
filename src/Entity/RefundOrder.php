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
use Tourze\OrderRefundBundle\Enum\PaymentMethod;
use Tourze\OrderRefundBundle\Enum\RefundStatus;
use Tourze\OrderRefundBundle\Repository\RefundOrderRepository;

/**
 * 退款订单实体
 */
#[ORM\Entity(repositoryClass: RefundOrderRepository::class)]
#[ORM\Table(name: 'order_refund_orders', options: ['comment' => '退款订单表'])]
class RefundOrder implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[ORM\ManyToOne(targetEntity: Aftersales::class)]
    #[ORM\JoinColumn(name: 'aftersales_id', nullable: false)]
    private ?Aftersales $aftersales = null;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[ORM\Column(type: Types::STRING, name: 'refund_no', length: 64, unique: true, options: ['comment' => '退款单号'])]
    private ?string $refundNo = null;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [PaymentMethod::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'payment_method', length: 30, enumType: PaymentMethod::class, options: ['comment' => '支付方式'])]
    private PaymentMethod $paymentMethod = PaymentMethod::ALIPAY;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [RefundStatus::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'status', length: 20, enumType: RefundStatus::class, options: ['comment' => '退款状态'])]
    private RefundStatus $status = RefundStatus::PENDING;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 20)]
    #[ORM\Column(type: Types::DECIMAL, name: 'refund_amount', precision: 10, scale: 2, options: ['comment' => '退款金额'])]
    private ?string $refundAmount = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, name: 'refund_points', options: ['comment' => '退还积分', 'default' => 0])]
    private int $refundPoints = 0;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 128)]
    #[ORM\Column(type: Types::STRING, name: 'original_transaction_no', length: 128, nullable: true, options: ['comment' => '原始交易单号'])]
    private ?string $originalTransactionNo = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 128)]
    #[ORM\Column(type: Types::STRING, name: 'refund_transaction_no', length: 128, nullable: true, options: ['comment' => '退款交易单号'])]
    private ?string $refundTransactionNo = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'failure_reason', length: 500, nullable: true, options: ['comment' => '失败原因'])]
    private ?string $failureReason = null;

    /** @var array<string, mixed>|null */
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, name: 'gateway_response', nullable: true, options: ['comment' => '支付网关响应'])]
    private ?array $gatewayResponse = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, name: 'retry_count', options: ['comment' => '重试次数', 'default' => 0])]
    private int $retryCount = 0;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'process_time', nullable: true, options: ['comment' => '处理时间'])]
    private ?\DateTimeImmutable $processTime = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'complete_time', nullable: true, options: ['comment' => '完成时间'])]
    private ?\DateTimeImmutable $completeTime = null;

    public function __construct()
    {
        $this->refundNo = $this->generateRefundNo();
    }

    public function __toString(): string
    {
        return $this->refundNo ?? (string) $this->getId();
    }

    public function getAftersales(): ?Aftersales
    {
        return $this->aftersales;
    }

    public function setAftersales(?Aftersales $aftersales): void
    {
        $this->aftersales = $aftersales;
    }

    public function getRefundNo(): ?string
    {
        return $this->refundNo;
    }

    public function setRefundNo(string $refundNo): void
    {
        $this->refundNo = $refundNo;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getStatus(): RefundStatus
    {
        return $this->status;
    }

    public function setStatus(RefundStatus $status): void
    {
        $this->status = $status;
    }

    public function getRefundAmount(): ?string
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(string $refundAmount): void
    {
        $this->refundAmount = $refundAmount;
    }

    public function getRefundPoints(): int
    {
        return $this->refundPoints;
    }

    public function setRefundPoints(int $refundPoints): void
    {
        $this->refundPoints = $refundPoints;
    }

    public function getOriginalTransactionNo(): ?string
    {
        return $this->originalTransactionNo;
    }

    public function setOriginalTransactionNo(?string $originalTransactionNo): void
    {
        $this->originalTransactionNo = $originalTransactionNo;
    }

    public function getRefundTransactionNo(): ?string
    {
        return $this->refundTransactionNo;
    }

    public function setRefundTransactionNo(?string $refundTransactionNo): void
    {
        $this->refundTransactionNo = $refundTransactionNo;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): void
    {
        $this->failureReason = $failureReason;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getGatewayResponse(): ?array
    {
        return $this->gatewayResponse;
    }

    /**
     * @param array<string, mixed>|null $gatewayResponse
     */
    public function setGatewayResponse(?array $gatewayResponse): void
    {
        $this->gatewayResponse = $gatewayResponse;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function incrementRetryCount(): self
    {
        ++$this->retryCount;

        return $this;
    }

    public function getProcessTime(): ?\DateTimeImmutable
    {
        return $this->processTime;
    }

    public function setProcessTime(?\DateTimeImmutable $processTime): void
    {
        $this->processTime = $processTime;
    }

    public function getCompleteTime(): ?\DateTimeImmutable
    {
        return $this->completeTime;
    }

    public function setCompleteTime(?\DateTimeImmutable $completeTime): void
    {
        $this->completeTime = $completeTime;
    }

    /**
     * 生成退款单号
     */
    private function generateRefundNo(): string
    {
        return 'RF' . date('Ymd') . sprintf('%06d', random_int(100000, 999999));
    }

    /**
     * 检查是否可以重试
     */
    public function canRetry(): bool
    {
        return RefundStatus::FAILED === $this->status && $this->retryCount < 3;
    }

    /**
     * 检查是否已完成
     */
    public function isCompleted(): bool
    {
        return RefundStatus::SUCCESS === $this->status;
    }

    /**
     * 检查是否失败
     */
    public function isFailed(): bool
    {
        return RefundStatus::FAILED === $this->status;
    }

    /**
     * 标记为处理中
     */
    public function markAsProcessing(): self
    {
        $this->status = RefundStatus::PROCESSING;
        $this->processTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 标记为成功
     */
    public function markAsSuccess(?string $transactionNo = null): self
    {
        $this->status = RefundStatus::SUCCESS;
        $this->refundTransactionNo = $transactionNo;
        $this->completeTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 标记为失败
     */
    public function markAsFailed(string $reason): self
    {
        $this->status = RefundStatus::FAILED;
        $this->failureReason = $reason;
        $this->incrementRetryCount();

        return $this;
    }

    /**
     * 获取退款金额（浮点数）
     */
    public function getRefundAmountFloat(): float
    {
        return (float) $this->refundAmount;
    }

    /**
     * 获取总退款价值（金额+积分等价）
     */
    public function getTotalRefundValue(float $pointsToAmountRate = 0.01): float
    {
        return $this->getRefundAmountFloat() + ($this->refundPoints * $pointsToAmountRate);
    }
}
