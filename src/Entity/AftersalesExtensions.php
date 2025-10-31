<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 为售后实体添加OMS同步相关字段的Trait
 *
 * 此Trait应在售后实体中使用，以添加OMS同步所需的字段
 */
trait AftersalesExtensions
{
    #[ORM\Column(type: Types::INTEGER, name: 'requested_amount', options: ['comment' => '申请金额(分)', 'default' => 0])]
    #[Assert\PositiveOrZero]
    private int $requestedAmount = 0;

    #[ORM\Column(type: Types::INTEGER, name: 'approved_amount', options: ['comment' => '批准金额(分)', 'default' => 0])]
    #[Assert\PositiveOrZero]
    private int $approvedAmount = 0;

    #[ORM\Column(type: Types::STRING, name: 'applicant_name', length: 100, nullable: true, options: ['comment' => '申请人姓名'])]
    #[Assert\Length(max: 100)]
    private ?string $applicantName = null;

    /** @var string|null 申请人电话 */
    #[ORM\Column(type: Types::STRING, name: 'applicant_phone', length: 20, nullable: true, options: ['comment' => '申请人电话'])]
    #[Assert\Length(max: 20)]
    private ?string $applicantPhone = null;

    /** @var string|null 订单号 */
    #[ORM\Column(type: Types::STRING, name: 'order_number', length: 100, nullable: true, options: ['comment' => '订单号'])]
    #[Assert\Length(max: 100)]
    private ?string $orderNumber = null;

    /** @var \DateTimeImmutable|null 申请时间 */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'application_time', nullable: true, options: ['comment' => '申请时间'])]
    private ?\DateTimeImmutable $applicationTime = null;

    /** @var \DateTimeImmutable|null 处理时间 */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'processed_time', nullable: true, options: ['comment' => '处理时间'])]
    private ?\DateTimeImmutable $processedTime = null;

    /** @var string|null 处理人 */
    #[ORM\Column(type: Types::STRING, name: 'processor', length: 100, nullable: true, options: ['comment' => '处理人'])]
    #[Assert\Length(max: 100)]
    private ?string $processor = null;

    /** @var string|null 退货快递公司 */
    #[ORM\Column(type: Types::STRING, name: 'return_express_company', length: 100, nullable: true, options: ['comment' => '退货快递公司'])]
    #[Assert\Length(max: 100)]
    private ?string $returnExpressCompany = null;

    /** @var string|null 退货快递单号 */
    #[ORM\Column(type: Types::STRING, name: 'return_express_number', length: 100, nullable: true, options: ['comment' => '退货快递单号'])]
    #[Assert\Length(max: 100)]
    private ?string $returnExpressNumber = null;

    /** @var \DateTimeImmutable|null 退货发货时间 */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'return_shipping_time', nullable: true, options: ['comment' => '退货发货时间'])]
    private ?\DateTimeImmutable $returnShippingTime = null;

    /** @var array<string, mixed>|null 换货地址 */
    #[ORM\Column(type: Types::JSON, name: 'exchange_address', nullable: true, options: ['comment' => '换货地址'])]
    private ?array $exchangeAddress = null;

    public function getRequestedAmount(): int
    {
        return $this->requestedAmount;
    }

    public function setRequestedAmount(int $requestedAmount): void
    {
        $this->requestedAmount = $requestedAmount;
    }

    public function getApprovedAmount(): int
    {
        return $this->approvedAmount;
    }

    public function setApprovedAmount(int $approvedAmount): void
    {
        $this->approvedAmount = $approvedAmount;
    }

    public function getApplicantName(): ?string
    {
        return $this->applicantName;
    }

    public function setApplicantName(?string $applicantName): void
    {
        $this->applicantName = $applicantName;
    }

    public function getApplicantPhone(): ?string
    {
        return $this->applicantPhone;
    }

    public function setApplicantPhone(?string $applicantPhone): void
    {
        $this->applicantPhone = $applicantPhone;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getApplicationTime(): ?\DateTimeImmutable
    {
        return $this->applicationTime;
    }

    public function setApplicationTime(?\DateTimeImmutable $applicationTime): void
    {
        $this->applicationTime = $applicationTime;
    }

    public function getProcessedTime(): ?\DateTimeImmutable
    {
        return $this->processedTime;
    }

    public function setProcessedTime(?\DateTimeImmutable $processedTime): void
    {
        $this->processedTime = $processedTime;
    }

    public function getProcessor(): ?string
    {
        return $this->processor;
    }

    public function setProcessor(?string $processor): void
    {
        $this->processor = $processor;
    }

    public function getReturnExpressCompany(): ?string
    {
        return $this->returnExpressCompany;
    }

    public function setReturnExpressCompany(?string $returnExpressCompany): void
    {
        $this->returnExpressCompany = $returnExpressCompany;
    }

    public function getReturnExpressNumber(): ?string
    {
        return $this->returnExpressNumber;
    }

    public function setReturnExpressNumber(?string $returnExpressNumber): void
    {
        $this->returnExpressNumber = $returnExpressNumber;
    }

    public function getReturnShippingTime(): ?\DateTimeImmutable
    {
        return $this->returnShippingTime;
    }

    public function setReturnShippingTime(?\DateTimeImmutable $returnShippingTime): void
    {
        $this->returnShippingTime = $returnShippingTime;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExchangeAddress(): ?array
    {
        return $this->exchangeAddress;
    }

    /**
     * @param array<string, mixed>|null $exchangeAddress
     */
    public function setExchangeAddress(?array $exchangeAddress): void
    {
        $this->exchangeAddress = $exchangeAddress;
    }
}
