<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;

class ExpressCompanyFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $companies = [
            [
                'code' => 'SF',
                'name' => '顺丰',
                'trackingUrlTemplate' => 'https://www.sf-express.com/chn/sc/dynamic_function/waybill/#search/bill-number/%s',
                'sortOrder' => 1,
            ],
            [
                'code' => 'STO',
                'name' => '申通',
                'trackingUrlTemplate' => 'https://www.sto.cn/query.html?no=%s',
                'sortOrder' => 2,
            ],
            [
                'code' => 'YD',
                'name' => '韵达',
                'trackingUrlTemplate' => 'https://www.yundaex.com/index.php/query/index.html?no=%s',
                'sortOrder' => 3,
            ],
            [
                'code' => 'ZTO',
                'name' => '中通',
                'trackingUrlTemplate' => 'https://www.zto.com/Home/QueryOrderInfo?txtBillCode=%s',
                'sortOrder' => 4,
            ],
            [
                'code' => 'YTO',
                'name' => '圆通',
                'trackingUrlTemplate' => 'https://www.yto.net.cn/query.html?no=%s',
                'sortOrder' => 5,
            ],
        ];

        foreach ($companies as $companyData) {
            $company = new ExpressCompany();
            $company->setCode($companyData['code']);
            $company->setName($companyData['name']);
            $company->setTrackingUrlTemplate($companyData['trackingUrlTemplate']);
            $company->setSortOrder($companyData['sortOrder']);
            $company->setIsActive(true);

            $manager->persist($company);
            $this->addReference('express_company_' . strtolower($companyData['code']), $company);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['order_refund'];
    }
}
