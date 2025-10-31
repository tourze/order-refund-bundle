<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OrderRefundBundle\Entity\ReturnOrder;
use Tourze\OrderRefundBundle\Enum\ReturnStatus;

/**
 * @extends AbstractCrudController<ReturnOrder>
 */
#[AdminCrud(routePath: '/order-refund/return-order', routeName: 'order_refund_return_order')]
final class ReturnOrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ReturnOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('退货订单')
            ->setEntityLabelInPlural('退货订单')
            ->setPageTitle('index', '退货订单列表')
            ->setPageTitle('new', '创建退货订单')
            ->setPageTitle('edit', '编辑退货订单')
            ->setPageTitle('detail', '退货订单详情')
            ->setHelp('index', '管理所有退货订单，跟踪退货物流状态')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'returnNo', 'expressCompany', 'trackingNo'])
            ->setPaginatorPageSize(20)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName): iterable
    {
        // 基本信息
        yield TextField::new('id', 'ID')
            ->onlyOnIndex()
            ->setMaxLength(9999)
        ;

        yield TextField::new('returnNo', '退货单号')
            ->setRequired(true)
        ;

        yield AssociationField::new('aftersales', '售后申请')
            ->setRequired(true)
            ->formatValue(fn ($value) => $this->formatAftersalesAssociation($value))
        ;

        // 状态信息
        yield ChoiceField::new('status', '退货状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => ReturnStatus::class])
            ->formatValue(fn ($value) => $this->formatReturnStatus($value))
        ;

        // 地址信息
        yield TextField::new('returnAddress', '退货地址')
            ->hideOnIndex()
        ;

        yield TextField::new('contactPerson', '联系人');

        yield TextField::new('contactPhone', '联系电话');

        // 物流信息
        yield TextField::new('expressCompany', '快递公司');

        yield TextField::new('trackingNo', '快递单号');

        yield TextField::new('trackingInfo', '物流信息')
            ->onlyOnDetail()
            ->formatValue(fn ($value) => $this->formatTrackingInfo($value))
        ;

        // 检验信息
        yield TextField::new('rejectionReason', '拒收原因')
            ->hideOnIndex()
            ->hideOnForm()
        ;

        // 备注
        yield TextareaField::new('remark', '备注')
            ->hideOnIndex()
        ;

        // 时间信息
        yield DateTimeField::new('shipTime', '发货时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $this->formatDateTime($value))
        ;

        yield DateTimeField::new('receiveTime', '收货时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '-';
            })
        ;

        yield DateTimeField::new('inspectTime', '检验时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '-';
            })
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '';
            })
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(function ($value) {
                return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '';
            })
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $confirmReceive = Action::new('confirmReceive', '确认收货')
            ->linkToCrudAction('confirmReceive')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-check')
            ->displayIf(fn (ReturnOrder $entity) => ReturnStatus::SHIPPED === $entity->getStatus())
        ;

        $inspectGoods = Action::new('inspectGoods', '质检商品')
            ->linkToCrudAction('inspectGoods')
            ->setCssClass('btn btn-info')
            ->setIcon('fa fa-search')
            ->displayIf(fn (ReturnOrder $entity) => ReturnStatus::RECEIVED === $entity->getStatus())
        ;

        $reject = Action::new('reject', '拒收')
            ->linkToCrudAction('reject')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-times')
            ->displayIf(fn (ReturnOrder $entity) => in_array($entity->getStatus(), [ReturnStatus::SHIPPED, ReturnStatus::RECEIVED], true))
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $confirmReceive)
            ->add(Crud::PAGE_INDEX, $inspectGoods)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_DETAIL, $confirmReceive)
            ->add(Crud::PAGE_DETAIL, $inspectGoods)
            ->add(Crud::PAGE_DETAIL, $reject)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'confirmReceive', 'inspectGoods', 'reject'])
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 退货状态筛选
        $statusChoices = [];
        foreach (ReturnStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(TextFilter::new('returnNo', '退货单号'))
            ->add(EntityFilter::new('aftersales', '售后申请'))
            ->add(ChoiceFilter::new('status', '退货状态')->setChoices($statusChoices))
            ->add(TextFilter::new('expressCompany', '快递公司'))
            ->add(TextFilter::new('trackingNo', '快递单号'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('shipTime', '发货时间'))
            ->add(DateTimeFilter::new('receiveTime', '收货时间'))
        ;
    }

    #[AdminAction(routePath: '{entityId}/confirmReceive', routeName: 'return_order_confirm_receive')]
    public function confirmReceive(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ReturnOrder);

        if (ReturnStatus::SHIPPED !== $entity->getStatus()) {
            $this->addFlash('danger', '只有已发货状态的退货订单才能确认收货');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setStatus(ReturnStatus::RECEIVED);
        $entity->setReceiveTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('退货订单 %s 已确认收货', $entity->getReturnNo()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/inspectGoods', routeName: 'return_order_inspect_goods')]
    public function inspectGoods(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ReturnOrder);

        if (ReturnStatus::RECEIVED !== $entity->getStatus()) {
            $this->addFlash('danger', '只有已收货状态的退货订单才能进行质检');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setStatus(ReturnStatus::INSPECTED);
        $entity->setInspectTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('退货订单 %s 质检完成', $entity->getReturnNo()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    #[AdminAction(routePath: '{entityId}/reject', routeName: 'return_order_reject')]
    public function reject(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ReturnOrder);

        if (!in_array($entity->getStatus(), [ReturnStatus::SHIPPED, ReturnStatus::RECEIVED], true)) {
            $this->addFlash('danger', '当前状态不允许拒收操作');

            return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
        }

        $entity->setStatus(ReturnStatus::REJECTED);
        $entity->setRejectionReason('管理员拒收');

        $this->entityManager->flush();

        $this->addFlash('warning', sprintf('退货订单 %s 已被拒收', $entity->getReturnNo()));

        return $this->redirect($context->getRequest()->headers->get('referer') ?? '/admin');
    }

    private function formatAftersalesAssociation(mixed $value): string
    {
        if (!is_object($value) || !method_exists($value, 'getId')) {
            return '';
        }

        $id = $value->getId();

        return sprintf('#%s', is_string($id) || is_int($id) ? (string) $id : '');
    }

    private function formatReturnStatus(mixed $value): string
    {
        return $value instanceof ReturnStatus ? $value->getLabel() : '';
    }

    private function formatTrackingInfo(mixed $value): string
    {
        if (null === $value || false === $value || [] === $value || '' === $value) {
            return '';
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return false !== $encoded ? $encoded : '';
    }

    private function formatDateTime(mixed $value): string
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '-';
    }
}
