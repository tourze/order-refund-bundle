<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ExchangeOrder;
use Tourze\OrderRefundBundle\Enum\ExchangeStatus;

class ExchangeOrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Aftersales $aftersales */
        $aftersales = $this->getReference(AftersalesFixtures::AFTERSALES_REFERENCE, Aftersales::class);

        $exchangeOrder = new ExchangeOrder();
        $exchangeOrder->setAftersales($aftersales);
        $exchangeOrder->setExchangeNo('EXC-' . uniqid());
        $exchangeOrder->setStatus(ExchangeStatus::PENDING);
        $exchangeOrder->setExchangeReason('Test exchange reason for fixtures');
        $exchangeOrder->setOriginalItems([
            ['product_id' => 'PROD_001', 'quantity' => 1, 'price' => '100.00'],
        ]);
        $exchangeOrder->setExchangeItems([
            ['product_id' => 'PROD_002', 'quantity' => 1, 'price' => '90.00'],
        ]);

        $manager->persist($exchangeOrder);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AftersalesFixtures::class,
        ];
    }
}
