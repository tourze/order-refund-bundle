<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\ExpressCompany;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Repository\ExpressCompanyRepository;

/**
 * 快递跟踪服务
 */
readonly class ExpressTrackingService
{
    public function __construct(
        private ExpressCompanyRepository $expressCompanyRepository,
    ) {
    }

    /**
     * 生成物流跟踪URL
     */
    public function generateTrackingUrl(string $expressCompanyName, string $trackingNo): ?string
    {
        if ('' === $expressCompanyName || '' === $trackingNo) {
            return null;
        }

        // 先尝试按名称查找
        $company = $this->findCompanyByName($expressCompanyName);

        if (null === $company || null === $company->getTrackingUrlTemplate() || '' === $company->getTrackingUrlTemplate()) {
            return null;
        }

        return sprintf($company->getTrackingUrlTemplate(), $trackingNo);
    }

    /**
     * 为退货单生成跟踪URL
     */
    public function generateTrackingUrlForReturn(ReturnOrder $returnOrder): ?string
    {
        $expressCompany = $returnOrder->getExpressCompany();
        $trackingNo = $returnOrder->getTrackingNo();

        if (null === $expressCompany || null === $trackingNo) {
            return null;
        }

        return $this->generateTrackingUrl($expressCompany, $trackingNo);
    }

    /**
     * 验证快递公司是否存在且启用
     */
    public function validateExpressCompany(string $expressCompanyName): bool
    {
        $company = $this->findCompanyByName($expressCompanyName);

        return null !== $company && $company->isActive();
    }

    /**
     * 根据名称或代码查找快递公司
     */
    private function findCompanyByName(string $nameOrCode): ?ExpressCompany
    {
        // 先按代码查找
        $company = $this->expressCompanyRepository->findByCode($nameOrCode);

        if (null !== $company) {
            return $company;
        }

        // 再按名称查找
        return $this->expressCompanyRepository->findOneBy(['name' => $nameOrCode, 'isActive' => true]);
    }

    /**
     * 获取所有可用的快递公司
     * @return array<ExpressCompany>
     */
    public function getAvailableCompanies(): array
    {
        return $this->expressCompanyRepository->findActiveCompanies();
    }
}
