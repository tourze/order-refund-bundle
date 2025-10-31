<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;

class AftersalesFixtures extends Fixture
{
    public const AFTERSALES_REFERENCE = 'aftersales-reference';

    public function load(ObjectManager $manager): void
    {
        $aftersales = new Aftersales();
        $aftersales->setReferenceNumber('REF-' . uniqid());
        $aftersales->setType(AftersalesType::RETURN_REFUND);
        $aftersales->setReason(RefundReason::DONT_WANT);
        $aftersales->setDescription('Test aftersales application for fixtures');

        // 设置必填的商品字段
        $aftersales->setOrderProductId('ORDER-PROD-' . uniqid());
        $aftersales->setProductId('PROD-' . uniqid());
        $aftersales->setSkuId('SKU-' . uniqid());
        $aftersales->setProductName('Test Product');
        $aftersales->setSkuName('Test SKU');
        $aftersales->setQuantity(1);
        $aftersales->setOriginalPrice('100.00');
        $aftersales->setPaidPrice('80.00');
        $aftersales->setRefundAmount('80.00');
        $aftersales->setOriginalRefundAmount('80.00');
        $aftersales->setActualRefundAmount('80.00');

        $manager->persist($aftersales);
        $manager->flush();

        $this->addReference(self::AFTERSALES_REFERENCE, $aftersales);
    }
}
