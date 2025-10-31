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
use Tourze\OrderRefundBundle\Enum\ReturnStatus;
use Tourze\OrderRefundBundle\Repository\ReturnOrderRepository;

/**
 * 退货订单实体
 */
#[ORM\Entity(repositoryClass: ReturnOrderRepository::class)]
#[ORM\Table(name: 'order_return_orders', options: ['comment' => '退货订单表'])]
#[ORM\Index(columns: ['express_company', 'tracking_no'], name: 'order_return_orders_return_tracking')]
class ReturnOrder implements \Stringable
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
    #[ORM\Column(type: Types::STRING, name: 'return_no', length: 64, unique: true, options: ['comment' => '退货单号'])]
    private ?string $returnNo = null;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ReturnStatus::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'status', length: 20, enumType: ReturnStatus::class, options: ['comment' => '退货状态'])]
    private ReturnStatus $status = ReturnStatus::PENDING;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'express_company', length: 50, nullable: true, options: ['comment' => '快递公司'])]
    private ?string $expressCompany = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'tracking_no', length: 50, nullable: true, options: ['comment' => '快递单号'])]
    private ?string $trackingNo = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 200)]
    #[ORM\Column(type: Types::STRING, name: 'return_address', length: 200, nullable: true, options: ['comment' => '退货地址'])]
    private ?string $returnAddress = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 50)]
    #[ORM\Column(type: Types::STRING, name: 'contact_person', length: 50, nullable: true, options: ['comment' => '联系人'])]
    private ?string $contactPerson = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 20)]
    #[ORM\Column(type: Types::STRING, name: 'contact_phone', length: 20, nullable: true, options: ['comment' => '联系电话'])]
    private ?string $contactPhone = null;

    /** @var array<int, array<string, mixed>>|null */
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, name: 'tracking_info', nullable: true, options: ['comment' => '物流跟踪信息'])]
    private ?array $trackingInfo = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'rejection_reason', length: 500, nullable: true, options: ['comment' => '拒收原因'])]
    private ?string $rejectionReason = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'remark', length: 500, nullable: true, options: ['comment' => '备注信息'])]
    private ?string $remark = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'ship_time', nullable: true, options: ['comment' => '发货时间'])]
    private ?\DateTimeImmutable $shipTime = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'receive_time', nullable: true, options: ['comment' => '收货时间'])]
    private ?\DateTimeImmutable $receiveTime = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'inspect_time', nullable: true, options: ['comment' => '检查时间'])]
    private ?\DateTimeImmutable $inspectTime = null;

    public function __construct()
    {
        $this->returnNo = $this->generateReturnNo();
    }

    public function __toString(): string
    {
        return $this->returnNo ?? (string) $this->getId();
    }

    public function getAftersales(): ?Aftersales
    {
        return $this->aftersales;
    }

    public function setAftersales(?Aftersales $aftersales): void
    {
        $this->aftersales = $aftersales;
    }

    public function getReturnNo(): ?string
    {
        return $this->returnNo;
    }

    public function setReturnNo(string $returnNo): void
    {
        $this->returnNo = $returnNo;
    }

    public function getStatus(): ReturnStatus
    {
        return $this->status;
    }

    public function setStatus(ReturnStatus $status): void
    {
        $this->status = $status;
    }

    public function getExpressCompany(): ?string
    {
        return $this->expressCompany;
    }

    public function setExpressCompany(?string $expressCompany): void
    {
        $this->expressCompany = $expressCompany;
    }

    public function getTrackingNo(): ?string
    {
        return $this->trackingNo;
    }

    public function setTrackingNo(?string $trackingNo): void
    {
        $this->trackingNo = $trackingNo;
    }

    public function getReturnAddress(): ?string
    {
        return $this->returnAddress;
    }

    public function setReturnAddress(?string $returnAddress): void
    {
        $this->returnAddress = $returnAddress;
    }

    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(?string $contactPerson): void
    {
        $this->contactPerson = $contactPerson;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getTrackingInfo(): ?array
    {
        return $this->trackingInfo;
    }

    /**
     * @param array<int, array<string, mixed>>|null $trackingInfo
     */
    public function setTrackingInfo(?array $trackingInfo): void
    {
        $this->trackingInfo = $trackingInfo;
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

    public function getShipTime(): ?\DateTimeImmutable
    {
        return $this->shipTime;
    }

    public function setShipTime(?\DateTimeImmutable $shipTime): void
    {
        $this->shipTime = $shipTime;
    }

    public function getReceiveTime(): ?\DateTimeImmutable
    {
        return $this->receiveTime;
    }

    public function setReceiveTime(?\DateTimeImmutable $receiveTime): void
    {
        $this->receiveTime = $receiveTime;
    }

    public function getInspectTime(): ?\DateTimeImmutable
    {
        return $this->inspectTime;
    }

    public function setInspectTime(?\DateTimeImmutable $inspectTime): void
    {
        $this->inspectTime = $inspectTime;
    }

    /**
     * 生成退货单号
     */
    private function generateReturnNo(): string
    {
        return 'RT' . date('Ymd') . sprintf('%06d', random_int(100000, 999999));
    }

    /**
     * 检查是否可以发货
     */
    public function canShip(): bool
    {
        return ReturnStatus::PENDING === $this->status;
    }

    /**
     * 检查是否已发货
     */
    public function isShipped(): bool
    {
        return in_array($this->status, [
            ReturnStatus::SHIPPED,
            ReturnStatus::IN_TRANSIT,
            ReturnStatus::RECEIVED,
            ReturnStatus::INSPECTED,
        ], true);
    }

    /**
     * 检查是否已完成
     */
    public function isCompleted(): bool
    {
        return ReturnStatus::INSPECTED === $this->status;
    }

    /**
     * 标记为已发货
     */
    public function markAsShipped(string $expressCompany, string $trackingNo): self
    {
        $this->status = ReturnStatus::SHIPPED;
        $this->expressCompany = $expressCompany;
        $this->trackingNo = $trackingNo;
        $this->shipTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 标记为已收货
     */
    public function markAsReceived(): self
    {
        $this->status = ReturnStatus::RECEIVED;
        $this->receiveTime = new \DateTimeImmutable();

        return $this;
    }

    /**
     * 标记为已检查
     */
    public function markAsInspected(bool $passed, ?string $rejectionReason = null): self
    {
        $this->status = $passed ? ReturnStatus::INSPECTED : ReturnStatus::REJECTED;
        $this->inspectTime = new \DateTimeImmutable();

        if (!$passed) {
            $this->rejectionReason = $rejectionReason;
        }

        return $this;
    }

    /**
     * 更新物流信息
     * @param array<int, array<string, mixed>> $trackingInfo
     */
    public function updateTrackingInfo(array $trackingInfo): self
    {
        $this->trackingInfo = $trackingInfo;

        // 根据物流信息自动更新状态
        if ([] !== $trackingInfo) {
            $lastStatus = end($trackingInfo)['status'] ?? '';
            match ($lastStatus) {
                'in_transit' => $this->status = ReturnStatus::IN_TRANSIT,
                'delivered' => $this->status = ReturnStatus::RECEIVED,
                default => null,
            };
        }

        return $this;
    }

    /**
     * 获取物流跟踪 URL
     * @deprecated 请使用 ExpressTrackingService::generateTrackingUrlForReturn() 代替
     */
    public function getTrackingUrl(): ?string
    {
        if (null === $this->expressCompany || null === $this->trackingNo) {
            return null;
        }

        // 基础的硬编码映射，建议使用 ExpressTrackingService 服务
        $urlMap = [
            'SF' => 'https://www.sf-express.com/chn/sc/dynamic_function/waybill/#search/bill-number/%s',
            '顺丰' => 'https://www.sf-express.com/chn/sc/dynamic_function/waybill/#search/bill-number/%s',
            'STO' => 'https://www.sto.cn/query.html?no=%s',
            '申通' => 'https://www.sto.cn/query.html?no=%s',
            'YD' => 'https://www.yundaex.com/index.php/query/index.html?no=%s',
            '韵达' => 'https://www.yundaex.com/index.php/query/index.html?no=%s',
            'ZTO' => 'https://www.zto.com/Home/QueryOrderInfo?txtBillCode=%s',
            '中通' => 'https://www.zto.com/Home/QueryOrderInfo?txtBillCode=%s',
            'YTO' => 'https://www.yto.net.cn/query.html?no=%s',
            '圆通' => 'https://www.yto.net.cn/query.html?no=%s',
        ];

        $template = $urlMap[$this->expressCompany] ?? null;

        return null !== $template ? sprintf($template, $this->trackingNo) : null;
    }
}
