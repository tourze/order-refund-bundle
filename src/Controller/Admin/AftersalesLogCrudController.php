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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\OrderRefundBundle\Entity\AftersalesLog;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;

/**
 * @extends AbstractCrudController<AftersalesLog>
 */
#[AdminCrud(routePath: '/order-refund/aftersales-log', routeName: 'order_refund_aftersales_log')]
final class AftersalesLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AftersalesLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('售后日志')
            ->setEntityLabelInPlural('售后日志')
            ->setPageTitle('index', '售后操作日志')
            ->setPageTitle('new', '创建售后日志')
            ->setPageTitle('edit', '编辑售后日志')
            ->setPageTitle('detail', '售后日志详情')
            ->setHelp('index', '查看所有售后申请的操作审计日志')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'content', 'operatorName', 'remark'])
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->configureBasicFields();
        yield from $this->configureOperatorFields();
        yield from $this->configureStateFields();
        yield from $this->configureContentFields();
        yield from $this->configureContextFields();
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

        yield AssociationField::new('aftersales', '售后申请')
            ->setRequired(true)
            ->formatValue(function ($value) {
                if (is_object($value) && method_exists($value, 'getId')) {
                    $id = $value->getId();

                    return sprintf('#%s', is_string($id) || is_int($id) ? (string) $id : '');
                }

                return '';
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureOperatorFields(): iterable
    {
        yield ChoiceField::new('action', '操作动作')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => AftersalesLogAction::class])
            ->formatValue(fn ($value) => $value instanceof AftersalesLogAction ? $value->getLabel() : '')
        ;

        yield TextField::new('operatorType', '操作者类型')
            ->formatValue(fn ($value) => is_string($value) ? $this->getOperatorTypeLabel($value) : '')
        ;

        yield TextField::new('operatorId', '操作者ID')
            ->hideOnIndex()
        ;

        yield TextField::new('operatorName', '操作者姓名');

        yield AssociationField::new('user', '关联用户')
            ->formatValue(function ($value) {
                if (is_object($value) && method_exists($value, 'getUsername') && method_exists($value, 'getId')) {
                    $username = $value->getUsername();
                    $id = $value->getId();
                    $usernameStr = is_string($username) || is_int($username) ? (string) $username : '';
                    $idStr = is_string($id) || is_int($id) ? (string) $id : '';

                    return sprintf('%s (%s)', $usernameStr, $idStr);
                }

                return '';
            })
        ;

        yield TextField::new('clientIp', '操作IP')
            ->hideOnIndex()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureStateFields(): iterable
    {
        yield TextField::new('previousState', '变更前状态')
            ->hideOnIndex()
        ;

        yield TextField::new('currentState', '变更后状态')
            ->hideOnIndex()
        ;

        yield TextField::new('stateChange', '状态变更')
            ->onlyOnIndex()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureContentFields(): iterable
    {
        yield TextareaField::new('content', '操作内容')
            ->hideOnIndex()
        ;

        yield TextField::new('contentSummary', '操作内容')
            ->onlyOnIndex()
        ;

        yield TextField::new('remarkSummary', '备注')
            ->onlyOnIndex()
        ;

        yield TextareaField::new('remark', '备注信息')
            ->hideOnIndex()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureContextFields(): iterable
    {
        yield TextField::new('contextData', '上下文数据')
            ->onlyOnDetail()
            ->formatValue(fn ($value) => $value ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureTimeFields(): iterable
    {
        yield DateTimeField::new('createTime', '创建时间')
            ->formatValue(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '')
        ;
    }

    private function getOperatorTypeLabel(string $value): string
    {
        return match ($value) {
            'SYSTEM' => '系统',
            'USER' => '用户',
            'ADMIN' => '管理员',
            default => $value,
        };
    }

    public function configureActions(Actions $actions): Actions
    {
        // 售后日志通常只查看，不允许编辑或删除
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 操作动作筛选
        $actionChoices = [];
        foreach (AftersalesLogAction::cases() as $case) {
            $actionChoices[$case->getLabel()] = $case->value;
        }

        // 操作者类型筛选
        $operatorTypeChoices = [
            '系统' => 'SYSTEM',
            '用户' => 'USER',
            '管理员' => 'ADMIN',
        ];

        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(EntityFilter::new('aftersales', '售后申请'))
            ->add(ChoiceFilter::new('action', '操作动作')->setChoices($actionChoices))
            ->add(ChoiceFilter::new('operatorType', '操作者类型')->setChoices($operatorTypeChoices))
            ->add(TextFilter::new('operatorName', '操作者姓名'))
            ->add(TextFilter::new('previousState', '变更前状态'))
            ->add(TextFilter::new('currentState', '变更后状态'))
            ->add(TextFilter::new('content', '操作内容'))
            ->add(EntityFilter::new('user', '关联用户'))
            ->add(DateTimeFilter::new('createTime', '操作时间'))
        ;
    }
}
