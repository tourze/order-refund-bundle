<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\RefundOrder;
use Tourze\OrderRefundBundle\Enum\PaymentMethod;
use Tourze\OrderRefundBundle\Enum\RefundStatus;

class RefundOrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Aftersales $aftersales */
        $aftersales = $this->getReference(AftersalesFixtures::AFTERSALES_REFERENCE, Aftersales::class);

        $refundOrder = new RefundOrder();
        $refundOrder->setAftersales($aftersales);
        $refundOrder->setRefundNo('REF-' . uniqid());
        $refundOrder->setPaymentMethod(PaymentMethod::WECHAT_PAY);
        $refundOrder->setStatus(RefundStatus::PENDING);
        $refundOrder->setRefundAmount('90.00');

        $manager->persist($refundOrder);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AftersalesFixtures::class,
        ];
    }
}
