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
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OrderRefundBundle\Entity\ExchangeOrder;
use Tourze\OrderRefundBundle\Enum\ExchangeStatus;

/**
 * @extends AbstractCrudController<ExchangeOrder>
 */
#[AdminCrud(routePath: '/order-refund/exchange-order', routeName: 'order_refund_exchange_order')]
final class ExchangeOrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ExchangeOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('换货订单')
            ->setEntityLabelInPlural('换货订单')
            ->setPageTitle('index', '换货订单列表')
            ->setPageTitle('new', '创建换货订单')
            ->setPageTitle('edit', '编辑换货订单')
            ->setPageTitle('detail', '换货订单详情')
            ->setHelp('index', '管理所有换货订单，跟踪换货流程')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'exchangeNo', 'returnTrackingNo', 'sendTrackingNo'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield from $this->configureBasicFields();
        yield from $this->configureStatusFields();
        yield from $this->configureExchangeInfoFields();
        yield from $this->configurePriceFields();
        yield from $this->configureReturnLogisticsFields();
        yield from $this->configureSendLogisticsFields();
        yield from $this->configureDeliveryFields();
        yield from $this->configureRejectionFields();
        yield from $this->configureRemarkFields();
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

        yield TextField::new('exchangeNo', '换货单号')
            ->setRequired(true)
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
    private function configureStatusFields(): iterable
    {
        yield ChoiceField::new('status', '换货状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => ExchangeStatus::class])
            ->formatValue(fn ($value) => $value instanceof ExchangeStatus ? $value->getLabel() : '')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureExchangeInfoFields(): iterable
    {
        yield TextareaField::new('exchangeReason', '换货原因')
            ->hideOnIndex()
        ;

        yield TextField::new('originalItems', '原商品')
            ->onlyOnDetail()
            ->formatValue(fn ($value) => $this->formatJsonData($value))
        ;

        yield TextField::new('exchangeItems', '换货商品')
            ->onlyOnDetail()
            ->formatValue(fn ($value) => $this->formatJsonData($value))
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configurePriceFields(): iterable
    {
        yield MoneyField::new('priceDifference', '价格差额')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->formatValue(fn ($value) => $this->formatPriceDifference($value))
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureReturnLogisticsFields(): iterable
    {
        yield TextField::new('returnExpressCompany', '退货快递公司');

        yield TextField::new('returnTrackingNo', '退货单号');

        yield DateTimeField::new('returnShipTime', '退货发货时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $this->formatDateTime($value))
        ;

        yield DateTimeField::new('returnReceiveTime', '退货收货时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $this->formatDateTime($value))
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureSendLogisticsFields(): iterable
    {
        yield TextField::new('sendExpressCompany', '发货快递公司');

        yield TextField::new('sendTrackingNo', '发货单号');

        yield DateTimeField::new('exchangeShipTime', '换货发货时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $this->formatDateTime($value))
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureDeliveryFields(): iterable
    {
        yield TextField::new('deliveryAddress', '收货地址')
            ->hideOnIndex()
        ;

        yield TextField::new('recipientName', '收货人');

        yield TextField::new('recipientPhone', '收货电话');
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureRejectionFields(): iterable
    {
        yield TextField::new('rejectionReason', '拒绝原因')
            ->hideOnIndex()
            ->hideOnForm()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureRemarkFields(): iterable
    {
        yield TextareaField::new('remark', '备注')
            ->hideOnIndex()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureTimeFields(): iterable
    {
        yield DateTimeField::new('completeTime', '完成时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $this->formatDateTime($value))
        ;

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

    private function formatJsonData(mixed $value): string
    {
        if (null === $value || false === $value || [] === $value || '' === $value) {
            return '';
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return false !== $encoded ? $encoded : '';
    }

    private function formatPriceDifference(mixed $value): string
    {
        if (is_numeric($value)) {
            $diff = (float) $value;
            $prefix = $diff > 0 ? '+' : '';

            return $prefix . number_format($diff, 2);
        }

        return '0.00';
    }

    private function formatDateTime(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return '-';
    }

    public function configureActions(Actions $actions): Actions
    {
        // 审核通过
        $approveAction = Action::new('approve', '审核通过')
            ->linkToCrudAction('approve')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-check')
            ->displayIf(function ($entity) {
                return is_object($entity) && method_exists($entity, 'getStatus') && ExchangeStatus::PENDING === $entity->getStatus();
            })
        ;

        // 拒绝换货
        $rejectAction = Action::new('reject', '拒绝换货')
            ->linkToCrudAction('reject')
            ->setCssClass('btn btn-danger')
            ->setIcon('fa fa-times')
            ->displayIf(function ($entity) {
                return is_object($entity) && method_exists($entity, 'getStatus') && ExchangeStatus::PENDING === $entity->getStatus();
            })
        ;

        // 确认收货（退货）
        $confirmReturnAction = Action::new('confirmReturn', '确认收货')
            ->linkToCrudAction('confirmReturn')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-inbox')
            ->displayIf(function ($entity) {
                return is_object($entity) && method_exists($entity, 'getStatus') && ExchangeStatus::RETURN_SHIPPED === $entity->getStatus();
            })
        ;

        // 发货换货
        $shipExchangeAction = Action::new('shipExchange', '发货换货')
            ->linkToCrudAction('shipExchange')
            ->setCssClass('btn btn-primary')
            ->setIcon('fa fa-truck')
            ->displayIf(function ($entity) {
                return is_object($entity) && method_exists($entity, 'getStatus') && ExchangeStatus::RETURN_RECEIVED === $entity->getStatus();
            })
        ;

        // 标记完成
        $markCompletedAction = Action::new('markCompleted', '标记完成')
            ->linkToCrudAction('markCompleted')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-flag-checkered')
            ->displayIf(function ($entity) {
                return is_object($entity) && method_exists($entity, 'getStatus') && ExchangeStatus::EXCHANGE_SHIPPED === $entity->getStatus();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $confirmReturnAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $confirmReturnAction)
            ->add(Crud::PAGE_DETAIL, $shipExchangeAction)
            ->add(Crud::PAGE_DETAIL, $markCompletedAction)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'approve', 'reject', 'confirmReturn'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 换货状态筛选
        $statusChoices = [];
        foreach (ExchangeStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(TextFilter::new('exchangeNo', '换货单号'))
            ->add(EntityFilter::new('aftersales', '售后申请'))
            ->add(ChoiceFilter::new('status', '换货状态')->setChoices($statusChoices))
            ->add(TextFilter::new('returnExpressCompany', '退货快递公司'))
            ->add(TextFilter::new('returnTrackingNo', '退货单号'))
            ->add(TextFilter::new('sendExpressCompany', '发货快递公司'))
            ->add(TextFilter::new('sendTrackingNo', '发货单号'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('returnShipTime', '退货发货时间'))
            ->add(DateTimeFilter::new('exchangeShipTime', '换货发货时间'))
            ->add(DateTimeFilter::new('completeTime', '完成时间'))
        ;
    }

    /**
     * 审核通过
     */
    #[AdminAction(routePath: '{entityId}/approve', routeName: 'exchange_order_approve')]
    public function approve(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ExchangeOrder);

        if (ExchangeStatus::PENDING !== $entity->getStatus()) {
            $this->addFlash('warning', '该换货订单状态不允许审核通过');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        $entity->setStatus(ExchangeStatus::APPROVED);

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('换货订单 %s 已审核通过', $entity->getExchangeNo()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 拒绝换货
     */
    #[AdminAction(routePath: '{entityId}/reject', routeName: 'exchange_order_reject')]
    public function reject(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ExchangeOrder);

        if (ExchangeStatus::PENDING !== $entity->getStatus()) {
            $this->addFlash('warning', '该换货订单状态不允许拒绝');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        // TODO: 实际应用中应该有一个表单让管理员输入拒绝原因
        $entity->markAsRejected('管理员拒绝');

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('换货订单 %s 已拒绝', $entity->getExchangeNo()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 确认收货（退货）
     */
    #[AdminAction(routePath: '{entityId}/confirmReturn', routeName: 'exchange_order_confirm_return')]
    public function confirmReturn(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ExchangeOrder);

        if (ExchangeStatus::RETURN_SHIPPED !== $entity->getStatus()) {
            $this->addFlash('warning', '该换货订单状态不允许确认收货');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        $entity->markReturnAsReceived();

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('换货订单 %s 退货已确认收货', $entity->getExchangeNo()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 发货换货
     */
    #[AdminAction(routePath: '{entityId}/shipExchange', routeName: 'exchange_order_ship_exchange')]
    public function shipExchange(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ExchangeOrder);

        if (ExchangeStatus::RETURN_RECEIVED !== $entity->getStatus()) {
            $this->addFlash('warning', '该换货订单状态不允许发货');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        // TODO: 实际应用中应该有一个表单让管理员输入快递公司和单号
        $entity->markExchangeAsShipped('顺丰速运', 'SF' . random_int(1000000000, 9999999999));

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('换货订单 %s 已发货', $entity->getExchangeNo()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 标记完成
     */
    #[AdminAction(routePath: '{entityId}/markCompleted', routeName: 'exchange_order_mark_completed')]
    public function markCompleted(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof ExchangeOrder);

        $entity->markAsCompleted();

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('换货订单 %s 已标记为完成', $entity->getExchangeNo()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }
}
