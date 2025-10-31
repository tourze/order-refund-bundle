<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\OrderRefundBundle\Entity\ReturnAddress;

/**
 * @extends AbstractCrudController<ReturnAddress>
 */
#[AdminCrud(routePath: '/order-refund/return-address', routeName: 'order_refund_return_address')]
final class ReturnAddressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ReturnAddress::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('寄回地址')
            ->setEntityLabelInPlural('寄回地址')
            ->setPageTitle('index', '寄回地址管理')
            ->setPageTitle('new', '创建寄回地址')
            ->setPageTitle('edit', '编辑寄回地址')
            ->setPageTitle('detail', '寄回地址详情')
            ->setHelp('index', '管理售后退货的收货地址信息')
            ->setDefaultSort(['sortOrder' => 'ASC', 'isDefault' => 'DESC'])
            ->setSearchFields(['id', 'name', 'contactName', 'contactPhone', 'address', 'companyName'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->configureBasicFields();
        yield from $this->configureContactFields();
        yield from $this->configureAddressFields();
        yield from $this->configureStatusFields();
        yield from $this->configureAdditionalFields();
        yield from $this->configureTimeFields();
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureBasicFields(): iterable
    {
        yield TextField::new('id', 'ID')
            ->onlyOnIndex()
            ->setMaxLength(9999)
        ;

        yield TextField::new('name', '地址名称')
            ->setRequired(true)
            ->setHelp('为地址设置一个易识别的名称标识')
        ;

        yield TextField::new('companyName', '公司名称')
            ->hideOnIndex()
            ->setHelp('收货公司名称，可选')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureContactFields(): iterable
    {
        yield TextField::new('contactName', '联系人')
            ->setRequired(true)
        ;

        yield TextField::new('contactPhone', '联系电话')
            ->setRequired(true)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureAddressFields(): iterable
    {
        yield TextField::new('province', '省份')
            ->setRequired(true)
        ;

        yield TextField::new('city', '城市')
            ->setRequired(true)
        ;

        yield TextField::new('district', '区县')
            ->hideOnIndex()
        ;

        yield TextareaField::new('address', '详细地址')
            ->setRequired(true)
        ;

        yield TextField::new('zipCode', '邮政编码')
            ->hideOnIndex()
        ;

        yield TextField::new('fullAddress', '完整地址')
            ->onlyOnIndex()
            ->formatValue(fn ($value, $entity) => $entity instanceof ReturnAddress ? $entity->getFullAddress() : '')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureStatusFields(): iterable
    {
        yield BooleanField::new('isDefault', '是否默认')
            ->setHelp('是否为默认寄回地址')
        ;

        yield BooleanField::new('isActive', '启用状态')
            ->setHelp('是否启用该地址')
        ;

        yield IntegerField::new('sortOrder', '排序')
            ->setHelp('数值越小越靠前显示')
            ->hideOnIndex()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureAdditionalFields(): iterable
    {
        yield TextareaField::new('businessHours', '营业时间')
            ->hideOnIndex()
            ->setHelp('收货时间说明，如：工作日 9:00-18:00')
        ;

        yield TextareaField::new('specialInstructions', '特殊说明')
            ->hideOnIndex()
            ->setHelp('收货的特殊说明或注意事项')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureTimeFields(): iterable
    {
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '地址名称'))
            ->add(TextFilter::new('contactName', '联系人'))
            ->add(TextFilter::new('contactPhone', '联系电话'))
            ->add(TextFilter::new('province', '省份'))
            ->add(TextFilter::new('city', '城市'))
            ->add(TextFilter::new('district', '区县'))
            ->add(TextFilter::new('companyName', '公司名称'))
            ->add(BooleanFilter::new('isDefault', '默认地址'))
            ->add(BooleanFilter::new('isActive', '启用状态'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
