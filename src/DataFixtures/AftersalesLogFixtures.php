<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesLog;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;

class AftersalesLogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Aftersales $aftersales */
        $aftersales = $this->getReference(AftersalesFixtures::AFTERSALES_REFERENCE, Aftersales::class);

        $log = new AftersalesLog();
        $log->setAftersales($aftersales);
        $log->setAction(AftersalesLogAction::CREATE);
        $log->setOperatorType('system');
        $log->setOperatorName('System');
        $log->setContent('Aftersales record created');
        $log->setPreviousState(null);
        $log->setCurrentState('pending_approval');

        $manager->persist($log);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AftersalesFixtures::class,
        ];
    }
}
