<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\RefundReason;

/**
 * 自动审核服务
 */
class AutoAuditService
{
    public function processAutoAudit(Aftersales $aftersales): void
    {
        if ($this->shouldAutoApprove($aftersales)) {
            $aftersales->setState(AftersalesState::APPROVED);
            $aftersales->setAuditTime(new \DateTimeImmutable());
        }
    }

    private function shouldAutoApprove(Aftersales $aftersales): bool
    {
        $reason = $aftersales->getReason();

        if (null === $reason) {
            return false;
        }

        $amount = $aftersales->getTotalRefundAmount();

        if (!$reason->supportsAutoApproval()) {
            return false;
        }

        if ($reason->isMerchantResponsibility()) {
            return true;
        }

        if ($amount <= 200.0 && RefundReason::DONT_WANT === $reason) {
            return true;
        }

        return false;
    }

    public function isEligibleForAutoProcess(Aftersales $aftersales): bool
    {
        if (!$aftersales->isTimeout()) {
            return false;
        }

        return AftersalesState::PENDING_APPROVAL === $aftersales->getState();
    }
}
