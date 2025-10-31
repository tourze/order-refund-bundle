<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;

class AftersalesOrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Aftersales $aftersales */
        $aftersales = $this->getReference(AftersalesFixtures::AFTERSALES_REFERENCE, Aftersales::class);

        $order = new AftersalesOrder();
        $order->setAftersales($aftersales);
        $order->setOrderNumber('ORD-' . uniqid());
        $order->setOrderStatus('paid');
        $order->setOrderCreateTime(new \DateTimeImmutable());
        $order->setUserId('user-' . uniqid());
        $order->setTotalAmount('199.90');

        $manager->persist($order);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AftersalesFixtures::class,
        ];
    }
}
