<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Entity;

use BizUserBundle\Entity\BizUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;

/**
 * 售后申请实体
 */
#[ORM\Entity(repositoryClass: AftersalesRepository::class)]
#[ORM\Table(name: 'order_aftersales', options: ['comment' => '售后申请表'])]
class Aftersales implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;
    use IpTraceableAware;
    use AftersalesExtensions;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AftersalesType::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'type', length: 24, enumType: AftersalesType::class, options: ['comment' => '售后类型'])]
    private ?AftersalesType $type = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, name: 'reference_number', length: 100, options: ['comment' => '关联单号'])]
    private ?string $referenceNumber = null;

    #[Groups(groups: ['admin_curd'])]
    #[ORM\ManyToOne(targetEntity: BizUser::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?BizUser $user = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [RefundReason::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'reason', length: 30, enumType: RefundReason::class, options: ['comment' => '退款原因'])]
    private ?RefundReason $reason = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::TEXT, name: 'description', nullable: true, options: ['comment' => '问题描述'])]
    private ?string $description = null;

    /**
     * @var array<string>
     */
    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'array')]
    #[Assert\Count(max: 9)]
    #[ORM\Column(type: Types::JSON, name: 'proof_images', options: ['comment' => '凭证图片'])]
    private array $proofImages = [];

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AftersalesState::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'state', length: 30, enumType: AftersalesState::class, options: ['comment' => '售后状态'])]
    private AftersalesState $state = AftersalesState::PENDING_APPROVAL;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [AftersalesStage::class, 'cases'])]
    #[ORM\Column(type: Types::STRING, name: 'stage', length: 20, enumType: AftersalesStage::class, options: ['comment' => '售后阶段'])]
    private AftersalesStage $stage = AftersalesStage::APPLY;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'reject_reason', length: 500, nullable: true, options: ['comment' => '拒绝原因'])]
    private ?string $rejectReason = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'service_note', length: 500, nullable: true, options: ['comment' => '客服备注'])]
    private ?string $serviceNote = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, name: 'modification_count', options: ['comment' => '修改申请次数', 'default' => 0])]
    private int $modificationCount = 0;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'auto_process_time', nullable: true, options: ['comment' => '自动处理时间'])]
    private ?\DateTimeImmutable $autoProcessTime = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'audit_time', nullable: true, options: ['comment' => '审核时间'])]
    private ?\DateTimeImmutable $auditTime = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'completed_time', nullable: true, options: ['comment' => '完成时间'])]
    private ?\DateTimeImmutable $completedTime = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, name: 'order_product_id', length: 255, nullable: false, options: ['comment' => '订单商品ID'])]
    private ?string $orderProductId = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, name: 'product_id', length: 100, options: ['comment' => '商品ID'])]
    private ?string $productId = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING, name: 'sku_id', length: 100, options: ['comment' => 'SKU ID'])]
    private ?string $skuId = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, name: 'product_name', length: 255, options: ['comment' => '商品名称'])]
    private ?string $productName = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: Types::STRING, name: 'sku_name', length: 255, options: ['comment' => 'SKU名称'])]
    private ?string $skuName = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotNull]
    #[Assert\Positive]
    #[ORM\Column(type: Types::INTEGER, name: 'quantity', options: ['comment' => '售后数量'])]
    private ?int $quantity = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, name: 'original_price', precision: 10, scale: 2, options: ['comment' => '商品原价'])]
    private ?string $originalPrice = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, name: 'paid_price', precision: 10, scale: 2, options: ['comment' => '商品实付价'])]
    private ?string $paidPrice = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, name: 'refund_amount', precision: 10, scale: 2, options: ['comment' => '退款金额（兼容字段）'])]
    private ?string $refundAmount = null;

    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, name: 'original_refund_amount', precision: 10, scale: 2, options: ['comment' => '原始申请退款金额'])]
    private ?string $originalRefundAmount = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::DECIMAL, name: 'actual_refund_amount', precision: 10, scale: 2, options: ['comment' => '实际退款金额'])]
    private ?string $actualRefundAmount = null;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, name: 'refund_amount_modified', options: ['comment' => '退款金额是否被修改', 'default' => false])]
    private bool $refundAmountModified = false;

    #[TrackColumn]
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Length(max: 500)]
    #[ORM\Column(type: Types::STRING, name: 'refund_amount_modify_reason', length: 500, nullable: true, options: ['comment' => '退款金额修改原因'])]
    private ?string $refundAmountModifyReason = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(groups: ['restful_read', 'admin_curd'])]
    #[Assert\Type(type: 'array')]
    #[ORM\Column(type: Types::JSON, name: 'product_snapshot', nullable: true, options: ['comment' => '商品快照数据'])]
    private ?array $productSnapshot = null;

    /**
     * @var Collection<int, AftersalesLog>
     */
    #[ORM\OneToMany(mappedBy: 'aftersales', targetEntity: AftersalesLog::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $logs;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }

    public function getType(): ?AftersalesType
    {
        return $this->type;
    }

    public function setType(AftersalesType $type): void
    {
        $this->type = $type;
    }

    public function getReferenceNumber(): ?string
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber(string $referenceNumber): void
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function getUser(): ?BizUser
    {
        return $this->user;
    }

    public function setUser(?BizUser $user): void
    {
        $this->user = $user;
    }

    public function getReason(): ?RefundReason
    {
        return $this->reason;
    }

    public function setReason(RefundReason $reason): void
    {
        $this->reason = $reason;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<string>
     */
    public function getProofImages(): array
    {
        return $this->proofImages;
    }

    /**
     * @param array<string> $proofImages
     */
    public function setProofImages(array $proofImages): void
    {
        $this->proofImages = $proofImages;
    }

    public function getState(): AftersalesState
    {
        return $this->state;
    }

    public function setState(AftersalesState $state): void
    {
        $this->state = $state;
    }

    public function getStage(): AftersalesStage
    {
        return $this->stage;
    }

    public function setStage(AftersalesStage $stage): void
    {
        $this->stage = $stage;
    }

    public function getRejectReason(): ?string
    {
        return $this->rejectReason;
    }

    public function setRejectReason(?string $rejectReason): void
    {
        $this->rejectReason = $rejectReason;
    }

    public function getServiceNote(): ?string
    {
        return $this->serviceNote;
    }

    public function setServiceNote(?string $serviceNote): void
    {
        $this->serviceNote = $serviceNote;
    }

    public function getModificationCount(): int
    {
        return $this->modificationCount;
    }

    public function setModificationCount(int $modificationCount): void
    {
        $this->modificationCount = $modificationCount;
    }

    public function incrementModificationCount(): void
    {
        ++$this->modificationCount;
    }

    public function getAutoProcessTime(): ?\DateTimeImmutable
    {
        return $this->autoProcessTime;
    }

    public function setAutoProcessTime(?\DateTimeImmutable $autoProcessTime): void
    {
        $this->autoProcessTime = $autoProcessTime;
    }

    public function getAuditTime(): ?\DateTimeImmutable
    {
        return $this->auditTime;
    }

    public function setAuditTime(?\DateTimeImmutable $auditTime): void
    {
        $this->auditTime = $auditTime;
    }

    public function getCompletedTime(): ?\DateTimeImmutable
    {
        return $this->completedTime;
    }

    public function setCompletedTime(?\DateTimeImmutable $completedTime): void
    {
        $this->completedTime = $completedTime;
    }

    public function getOrderProductId(): ?string
    {
        return $this->orderProductId;
    }

    public function setOrderProductId(string $orderProductId): void
    {
        $this->orderProductId = $orderProductId;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getSkuId(): ?string
    {
        return $this->skuId;
    }

    public function setSkuId(string $skuId): void
    {
        $this->skuId = $skuId;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): void
    {
        $this->productName = $productName;
    }

    public function getSkuName(): ?string
    {
        return $this->skuName;
    }

    public function setSkuName(string $skuName): void
    {
        $this->skuName = $skuName;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getOriginalPrice(): ?string
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(string $originalPrice): void
    {
        $this->originalPrice = $originalPrice;
    }

    public function getPaidPrice(): ?string
    {
        return $this->paidPrice;
    }

    public function setPaidPrice(string $paidPrice): void
    {
        $this->paidPrice = $paidPrice;
    }

    public function getRefundAmount(): ?string
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(string $refundAmount): void
    {
        $this->refundAmount = $refundAmount;
    }

    public function getOriginalRefundAmount(): ?string
    {
        return $this->originalRefundAmount;
    }

    public function setOriginalRefundAmount(string $originalRefundAmount): void
    {
        $this->originalRefundAmount = $originalRefundAmount;
    }

    public function getActualRefundAmount(): ?string
    {
        return $this->actualRefundAmount;
    }

    public function setActualRefundAmount(string $actualRefundAmount): void
    {
        $this->actualRefundAmount = $actualRefundAmount;
    }

    public function isRefundAmountModified(): bool
    {
        return $this->refundAmountModified;
    }

    public function setRefundAmountModified(bool $refundAmountModified): void
    {
        $this->refundAmountModified = $refundAmountModified;
    }

    public function getRefundAmountModifyReason(): ?string
    {
        return $this->refundAmountModifyReason;
    }

    public function setRefundAmountModifyReason(?string $refundAmountModifyReason): void
    {
        $this->refundAmountModifyReason = $refundAmountModifyReason;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProductSnapshot(): ?array
    {
        return $this->productSnapshot;
    }

    /**
     * @param array<string, mixed>|null $productSnapshot
     */
    public function setProductSnapshot(?array $productSnapshot): void
    {
        $this->productSnapshot = $productSnapshot;
    }

    /**
     * @return Collection<int, AftersalesLog>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(AftersalesLog $log): void
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setAftersales($this);
        }
    }

    public function removeLog(AftersalesLog $log): void
    {
        if ($this->logs->removeElement($log)) {
            if ($log->getAftersales() === $this) {
                $log->setAftersales(null);
            }
        }
    }

    /**
     * 计算总退款金额
     */
    public function getTotalRefundAmount(): float
    {
        // 优先使用实际退款金额，如果不存在则使用兼容字段
        return (float) ($this->actualRefundAmount ?? $this->refundAmount ?? 0.0);
    }

    /**
     * 修改退款金额
     */
    public function modifyRefundAmount(string $newAmount, ?string $reason = null): void
    {
        // 验证金额不能超过原始申请金额
        if (null !== $this->originalRefundAmount && (float) $newAmount > (float) $this->originalRefundAmount) {
            throw new \InvalidArgumentException('实际退款金额不能超过原始申请金额');
        }

        $this->actualRefundAmount = $newAmount;
        $this->refundAmountModified = true;
        $this->refundAmountModifyReason = $reason;

        // 同时更新兼容字段
        $this->refundAmount = $newAmount;
    }

    /**
     * 检查是否可以修改退款金额
     */
    public function canModifyRefundAmount(): bool
    {
        return AftersalesState::PENDING_APPROVAL === $this->state;
    }

    /**
     * 检查是否可以修改申请
     */
    public function canModify(): bool
    {
        return AftersalesState::REJECTED === $this->state && $this->modificationCount < 3;
    }

    /**
     * 检查是否超时
     */
    public function isTimeout(): bool
    {
        if (null === $this->autoProcessTime) {
            return false;
        }

        return new \DateTimeImmutable() > $this->autoProcessTime;
    }

    /**
     * 检查是否需要客服介入
     */
    public function needsCustomerServiceIntervention(): bool
    {
        return $this->modificationCount >= 3 || $this->isTimeout();
    }

    /**
     * 检查是否可以取消
     */
    public function canCancel(): bool
    {
        return in_array($this->state, [
            AftersalesState::PENDING_APPROVAL,
            AftersalesState::PENDING_RETURN,
            AftersalesState::REJECTED,
        ], true);
    }

    /**
     * 获取当前可执行的操作
     * @return array<string>
     */
    public function getAvailableActions(): array
    {
        $actions = [];

        switch ($this->state) {
            case AftersalesState::PENDING_APPROVAL:
                $actions[] = 'approve';
                $actions[] = 'reject';
                $actions[] = 'cancel';
                break;

            case AftersalesState::APPROVED:
                if (AftersalesType::REFUND_ONLY === $this->type) {
                    $actions[] = 'refund';
                } else {
                    $actions[] = 'wait_return';
                }
                break;

            case AftersalesState::PENDING_RETURN:
                $actions[] = 'fill_express';
                $actions[] = 'cancel';
                break;

            case AftersalesState::PENDING_RECEIVE:
                $actions[] = 'confirm_receive';
                $actions[] = 'reject_receive';
                break;

            case AftersalesState::PENDING_REFUND:
                $actions[] = 'refund';
                break;

            case AftersalesState::REJECTED:
                if ($this->canModify()) {
                    $actions[] = 'modify';
                }
                $actions[] = 'cancel';
                break;
        }

        return $actions;
    }
}
