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
use Tourze\OrderRefundBundle\Enum\ExchangeStatus;
use Tourze\OrderRefundBundle\Repository\ExchangeOrderRepository;

/**
 * 换货订单实体
 */
#[ORM\Entity(repositoryClass: ExchangeOrderRepository::class)]
#[ORM\Table(name: 'order_exchange_orders', options: ['comment' => '换货订单表'])]
class ExchangeOrder implements \Stringable
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
    #[ORM\Column(type: Types::STRING, name: 'exchange_no', length: 64, unique: true, options: ['comment' => '换货单号'])]
    private ?string $exchangeNo = null;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ExchangeStatus::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'status', length: 20, enumType: ExchangeStatus::class, options: ['comment' => '换货状态'])]
    private ExchangeStatus $status = ExchangeStatus::PENDING;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'exchange_reason', length: 500, options: ['comment' => '换货原因'])]
    private ?string $exchangeReason = null;

    /** @var array<int, array<string, mixed>>|null */
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, name: 'original_items', options: ['comment' => '原商品信息'])]
    private ?array $originalItems = null;

    /** @var array<int, array<string, mixed>>|null */
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, name: 'exchange_items', options: ['comment' => '换货商品信息'])]
    private ?array $exchangeItems = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 20)]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, name: 'price_difference', precision: 10, scale: 2, options: ['comment' => '价格差额', 'default' => '0.00'])]
    private string $priceDifference = '0.00';

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'return_express_company', length: 50, nullable: true, options: ['comment' => '退货快递公司'])]
    private ?string $returnExpressCompany = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'return_tracking_no', length: 50, nullable: true, options: ['comment' => '退货快递单号'])]
    private ?string $returnTrackingNo = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'send_express_company', length: 50, nullable: true, options: ['comment' => '发货快递公司'])]
    private ?string $sendExpressCompany = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'send_tracking_no', length: 50, nullable: true, options: ['comment' => '发货快递单号'])]
    private ?string $sendTrackingNo = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 200)]
    #[ORM\Column(type: Types::STRING, name: 'delivery_address', length: 200, nullable: true, options: ['comment' => '收货地址'])]
    private ?string $deliveryAddress = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'recipient_name', length: 50, nullable: true, options: ['comment' => '收货人姓名'])]
    private ?string $recipientName = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 20)]
    #[ORM\Column(type: Types::STRING, name: 'recipient_phone', length: 20, nullable: true, options: ['comment' => '收货人电话'])]
    private ?string $recipientPhone = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'rejection_reason', length: 500, nullable: true, options: ['comment' => '拒绝原因'])]
    private ?string $rejectionReason = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'remark', length: 500, nullable: true, options: ['comment' => '备注信息'])]
    private ?string $remark = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'return_ship_time', nullable: true, options: ['comment' => '退货发货时间'])]
    private ?\DateTimeImmutable $returnShipTime = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'return_receive_time', nullable: true, options: ['comment' => '退货收货时间'])]
    private ?\DateTimeImmutable $returnReceiveTime = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'exchange_ship_time', nullable: true, options: ['comment' => '换货发货时间'])]
    private ?\DateTimeImmutable $exchangeShipTime = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'complete_time', nullable: true, options: ['comment' => '完成时间'])]
    private ?\DateTimeImmutable $completeTime = null;

    public function __construct()
    {
        $this->exchangeNo = $this->generateExchangeNo();
    }

    public function __toString(): string
    {
        return $this->exchangeNo ?? (string) $this->getId();
    }

    public function getAftersales(): ?Aftersales
    {
        return $this->aftersales;
    }

    public function setAftersales(?Aftersales $aftersales): void
    {
        $this->aftersales = $aftersales;
    }

    public function getExchangeNo(): ?string
    {
        return $this->exchangeNo;
    }

    public function setExchangeNo(string $exchangeNo): void
    {
        $this->exchangeNo = $exchangeNo;
    }

    public function getStatus(): ExchangeStatus
    {
        return $this->status;
    }

    public function setStatus(ExchangeStatus $status): void
    {
        $this->status = $status;
    }

    public function getExchangeReason(): ?string
    {
        return $this->exchangeReason;
    }

    public function setExchangeReason(?string $exchangeReason): void
    {
        $this->exchangeReason = $exchangeReason;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getOriginalItems(): ?array
    {
        return $this->originalItems;
    }

    /**
     * @param array<int, array<string, mixed>>|null $originalItems
     */
    public function setOriginalItems(?array $originalItems): void
    {
        $this->originalItems = $originalItems;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getExchangeItems(): ?array
    {
        return $this->exchangeItems;
    }

    /**
     * @param array<int, array<string, mixed>>|null $exchangeItems
     */
    public function setExchangeItems(?array $exchangeItems): void
    {
        $this->exchangeItems = $exchangeItems;
    }

    public function getPriceDifference(): string
    {
        return $this->priceDifference;
    }

    public function setPriceDifference(string $priceDifference): void
    {
        $this->priceDifference = $priceDifference;
    }

    public function getReturnExpressCompany(): ?string
    {
        return $this->returnExpressCompany;
    }

    public function setReturnExpressCompany(?string $returnExpressCompany): void
    {
        $this->returnExpressCompany = $returnExpressCompany;
    }

    public function getReturnTrackingNo(): ?string
    {
        return $this->returnTrackingNo;
    }

    public function setReturnTrackingNo(?string $returnTrackingNo): void
    {
        $this->returnTrackingNo = $returnTrackingNo;
    }

    public function getSendExpressCompany(): ?string
    {
        return $this->sendExpressCompany;
    }

    public function setSendExpressCompany(?string $sendExpressCompany): void
    {
        $this->sendExpressCompany = $sendExpressCompany;
    }

    public function getSendTrackingNo(): ?string
    {
        return $this->sendTrackingNo;
    }

    public function setSendTrackingNo(?string $sendTrackingNo): void
    {
        $this->sendTrackingNo = $sendTrackingNo;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?string $deliveryAddress): void
    {
        $this->deliveryAddress = $deliveryAddress;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function setRecipientName(?string $recipientName): void
    {
        $this->recipientName = $recipientName;
    }

    public function getRecipientPhone(): ?string
    {
        return $this->recipientPhone;
    }

    public function setRecipientPhone(?string $recipientPhone): void
    {
        $this->recipientPhone = $recipientPhone;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): void
    {
        $this->rejectionReason = $rejectionReason;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getReturnShipTime(): ?\DateTimeImmutable
    {
        return $this->returnShipTime;
    }

    public function setReturnShipTime(?\DateTimeImmutable $returnShipTime): void
    {
        $this->returnShipTime = $returnShipTime;
    }

    public function getReturnReceiveTime(): ?\DateTimeImmutable
    {
        return $this->returnReceiveTime;
    }

    public function setReturnReceiveTime(?\DateTimeImmutable $returnReceiveTime): void
    {
        $this->returnReceiveTime = $returnReceiveTime;
    }

    public function getExchangeShipTime(): ?\DateTimeImmutable
    {
        return $this->exchangeShipTime;
    }

    public function setExchangeShipTime(?\DateTimeImmutable $exchangeShipTime): void
    {
        $this->exchangeShipTime = $exchangeShipTime;
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
     * 生成换货单号
     */
    private function generateExchangeNo(): string
    {
        return 'EX' . date('Ymd') . sprintf('%06d', random_int(100000, 999999));
    }

    /**
     * 获取价格差额（浮点数）
     */
    public function getPriceDifferenceFloat(): float
    {
        return (float) $this->priceDifference;
    }

    /**
     * 检查是否需要补差价
     */
    public function needsAdditionalPayment(): bool
    {
        return $this->getPriceDifferenceFloat() > 0;
    }

    /**
     * 检查是否需要退差价
     */
    public function needsRefund(): bool
    {
        return $this->getPriceDifferenceFloat() < 0;
    }

    /**
     * 检查是否已完成
     */
    public function isCompleted(): bool
    {
        return ExchangeStatus::COMPLETED === $this->status;
    }

    /**
     * 检查是否需要用户操作
     */
    public function needsUserAction(): bool
    {
        return in_array($this->status, [
            ExchangeStatus::PENDING,
            ExchangeStatus::APPROVED,
        ], true);
    }

    /**
     * 检查是否需要商家操作
     */
    public function needsMerchantAction(): bool
    {
        return ExchangeStatus::RETURN_RECEIVED === $this->status;
    }

    /**
     * 标记为已发货（退货）
     */
    public function markReturnAsShipped(string $expressCompany, string $trackingNo): void
    {
        $this->status = ExchangeStatus::RETURN_SHIPPED;
        $this->returnExpressCompany = $expressCompany;
        $this->returnTrackingNo = $trackingNo;
        $this->returnShipTime = new \DateTimeImmutable();
    }

    /**
     * 标记为已收货（退货）
     */
    public function markReturnAsReceived(): void
    {
        $this->status = ExchangeStatus::RETURN_RECEIVED;
        $this->returnReceiveTime = new \DateTimeImmutable();
    }

    /**
     * 标记为已发货（换货）
     */
    public function markExchangeAsShipped(string $expressCompany, string $trackingNo): void
    {
        $this->status = ExchangeStatus::EXCHANGE_SHIPPED;
        $this->sendExpressCompany = $expressCompany;
        $this->sendTrackingNo = $trackingNo;
        $this->exchangeShipTime = new \DateTimeImmutable();
    }

    /**
     * 标记为已完成
     */
    public function markAsCompleted(): void
    {
        $this->status = ExchangeStatus::COMPLETED;
        $this->completeTime = new \DateTimeImmutable();
    }

    /**
     * 标记为已拒绝
     */
    public function markAsRejected(string $reason): void
    {
        $this->status = ExchangeStatus::REJECTED;
        $this->rejectionReason = $reason;
    }
}
