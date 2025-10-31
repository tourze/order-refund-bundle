<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Enum\ReturnStatus;

class ReturnOrderFixtures extends Fixture implements DependentFixtureInterface
{
    public const RETURN_ORDER_REFERENCE = 'return-order';

    public function load(ObjectManager $manager): void
    {
        /** @var Aftersales $aftersales */
        $aftersales = $this->getReference(AftersalesFixtures::AFTERSALES_REFERENCE, Aftersales::class);

        $returnOrder = new ReturnOrder();
        $returnOrder->setAftersales($aftersales);
        $returnOrder->setReturnNo('RET-' . uniqid());
        $returnOrder->setStatus(ReturnStatus::PENDING);

        $manager->persist($returnOrder);
        $manager->flush();

        $this->addReference(self::RETURN_ORDER_REFERENCE, $returnOrder);
    }

    public function getDependencies(): array
    {
        return [
            AftersalesFixtures::class,
        ];
    }
}
