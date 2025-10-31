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
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\OrderRefundBundle\Entity\ExpressCompany;

/**
 * @extends AbstractCrudController<ExpressCompany>
 */
#[AdminCrud(routePath: '/order-refund/express-company', routeName: 'order_refund_express_company')]
final class ExpressCompanyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ExpressCompany::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('快递公司')
            ->setEntityLabelInPlural('快递公司')
            ->setPageTitle('index', '快递公司管理')
            ->setPageTitle('new', '添加快递公司')
            ->setPageTitle('edit', '编辑快递公司')
            ->setPageTitle('detail', '快递公司详情')
            ->setHelp('index', '管理系统中的快递公司信息')
            ->setDefaultSort(['sortOrder' => 'ASC', 'isActive' => 'DESC'])
            ->setSearchFields(['id', 'code', 'name', 'description'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->configureBasicFields();
        yield from $this->configureTrackingFields();
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

        yield TextField::new('code', '快递公司代码')
            ->setRequired(true)
            ->setHelp('唯一的快递公司标识代码，如：SF、YTO、ZTO')
        ;

        yield TextField::new('name', '快递公司名称')
            ->setRequired(true)
            ->setHelp('快递公司的完整名称')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureTrackingFields(): iterable
    {
        yield TextField::new('trackingUrlTemplate', '物流查询URL模板')
            ->hideOnIndex()
            ->setHelp('物流查询链接模板，使用 {trackingNumber} 占位符，如：https://example.com/track?no={trackingNumber}')
        ;

        yield TextField::new('trackingUrlPreview', '查询链接预览')
            ->onlyOnDetail()
            ->formatValue(function ($value, $entity) {
                if (!$entity instanceof ExpressCompany) {
                    return '';
                }

                $template = $entity->getTrackingUrlTemplate();
                if (null === $template || '' === $template) {
                    return '未设置';
                }

                return str_replace('{trackingNumber}', '示例单号123456', $template);
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureStatusFields(): iterable
    {
        yield BooleanField::new('isActive', '启用状态')
            ->setHelp('是否启用该快递公司')
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
        yield TextareaField::new('description', '描述信息')
            ->hideOnIndex()
            ->setHelp('快递公司的详细描述或备注信息')
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
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('code', '快递公司代码'))
            ->add(TextFilter::new('name', '快递公司名称'))
            ->add(TextFilter::new('description', '描述信息'))
            ->add(BooleanFilter::new('isActive', '启用状态'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
