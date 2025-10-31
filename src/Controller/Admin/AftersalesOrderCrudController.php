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
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;

/**
 * @extends AbstractCrudController<AftersalesOrder>
 */
#[AdminCrud(routePath: '/order-refund/aftersales-order', routeName: 'order_refund_aftersales_order')]
final class AftersalesOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AftersalesOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('售后订单快照')
            ->setEntityLabelInPlural('售后订单快照')
            ->setPageTitle('index', '售后订单快照列表')
            ->setPageTitle('detail', '售后订单快照详情')
            ->setPageTitle('new', '新建售后订单快照')
            ->setPageTitle('edit', '编辑售后订单快照')
            ->setHelp('index', '管理售后申请时的原始订单信息快照，用于记录售后申请时的订单状态')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'orderNumber', 'userId', 'orderStatus'])
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield AssociationField::new('aftersales', '关联售后申请')
            ->setRequired(true)
            ->formatValue(fn ($value) => $this->formatAftersalesValue($value))
        ;

        yield TextField::new('orderNumber', '订单编号')
            ->setRequired(true)
            ->setColumns(6)
        ;

        yield TextField::new('orderStatus', '订单状态')
            ->setRequired(true)
            ->setColumns(6)
        ;

        yield TextField::new('userId', '用户ID')
            ->setRequired(true)
            ->setColumns(6)
        ;

        yield MoneyField::new('totalAmount', '订单总金额')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setColumns(6)
        ;

        yield DateTimeField::new('orderCreateTime', '订单创建时间')
            ->setRequired(true)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns(6)
        ;

        yield TextareaField::new('extra', '扩展信息')
            ->setColumns(12)
            ->formatValue(fn ($value) => $this->formatExtraValue($value))
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setColumns(6)
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setColumns(6)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('aftersales', '关联售后申请'))
            ->add(TextFilter::new('orderNumber', '订单编号'))
            ->add(TextFilter::new('orderStatus', '订单状态'))
            ->add(TextFilter::new('userId', '用户ID'))
            ->add(NumericFilter::new('totalAmount', '订单总金额'))
            ->add(DateTimeFilter::new('orderCreateTime', '订单创建时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    private function formatAftersalesValue(mixed $value): string
    {
        if (!is_object($value) || !method_exists($value, 'getId') || !method_exists($value, 'getType')) {
            return '';
        }

        $id = $value->getId();
        $idStr = is_string($id) || is_int($id) ? (string) $id : '';

        $type = $value->getType();
        $typeLabel = $this->extractTypeLabel($type);

        return sprintf('#%s (%s)', $idStr, $typeLabel);
    }

    private function extractTypeLabel(mixed $type): string
    {
        if (!is_object($type) || !method_exists($type, 'getLabel')) {
            return '未知';
        }

        $label = $type->getLabel();

        return is_string($label) ? $label : '未知';
    }

    private function formatExtraValue(mixed $value): string
    {
        if (null === $value || false === $value || [] === $value || '' === $value) {
            return '';
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return false !== $encoded ? $encoded : '';
    }
}
