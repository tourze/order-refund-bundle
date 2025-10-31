<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;

class ReturnAddressFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $addresses = [
            [
                'name' => '北京退货中心',
                'contactName' => '张经理',
                'contactPhone' => '010-12345678',
                'province' => '北京市',
                'city' => '北京市',
                'district' => '朝阳区',
                'address' => '朝阳区建国路88号',
                'zipCode' => '100020',
                'businessHours' => '周一至周五 9:00-18:00',
                'specialInstructions' => '请在工作时间内寄送',
                'companyName' => '北京物流中心',
                'isDefault' => true,
                'isActive' => true,
                'sortOrder' => 1,
            ],
            [
                'name' => '上海退货中心',
                'contactName' => '李经理',
                'contactPhone' => '021-87654321',
                'province' => '上海市',
                'city' => '上海市',
                'district' => '浦东新区',
                'address' => '浦东新区世纪大道1000号',
                'zipCode' => '200120',
                'businessHours' => '周一至周日 8:00-20:00',
                'specialInstructions' => '支持24小时收货',
                'companyName' => '上海物流中心',
                'isDefault' => false,
                'isActive' => true,
                'sortOrder' => 2,
            ],
        ];

        foreach ($addresses as $addressData) {
            $address = new ReturnAddress();
            $address->setName($addressData['name']);
            $address->setContactName($addressData['contactName']);
            $address->setContactPhone($addressData['contactPhone']);
            $address->setProvince($addressData['province']);
            $address->setCity($addressData['city']);
            $address->setDistrict($addressData['district']);
            $address->setAddress($addressData['address']);
            $address->setZipCode($addressData['zipCode']);
            $address->setBusinessHours($addressData['businessHours']);
            $address->setSpecialInstructions($addressData['specialInstructions']);
            $address->setCompanyName($addressData['companyName']);
            $address->setIsDefault($addressData['isDefault']);
            $address->setIsActive($addressData['isActive']);
            $address->setSortOrder($addressData['sortOrder']);

            $manager->persist($address);
            $this->addReference('return_address_' . strtolower($addressData['name']), $address);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['order_refund'];
    }
}
