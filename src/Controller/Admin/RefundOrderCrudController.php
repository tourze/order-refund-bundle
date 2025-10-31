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
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OrderRefundBundle\Entity\RefundOrder;
use Tourze\OrderRefundBundle\Enum\PaymentMethod;
use Tourze\OrderRefundBundle\Enum\RefundStatus;

/**
 * @extends AbstractCrudController<RefundOrder>
 */
#[AdminCrud(routePath: '/order-refund/refund-order', routeName: 'order_refund_refund_order')]
final class RefundOrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return RefundOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('退款订单')
            ->setEntityLabelInPlural('退款订单')
            ->setPageTitle('index', '退款订单列表')
            ->setPageTitle('new', '创建退款订单')
            ->setPageTitle('edit', '编辑退款订单')
            ->setPageTitle('detail', '退款订单详情')
            ->setHelp('index', '管理所有退款订单，包括各种支付方式的退款')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'refundNo', 'originalTransactionNo', 'refundTransactionNo'])
            ->setPaginatorPageSize(20)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName): iterable
    {
        yield from $this->configureBasicFields();
        yield from $this->configurePaymentAndStatusFields();
        yield from $this->configureAmountFields();
        yield from $this->configureTransactionFields();
        yield from $this->configureRetryFields();
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

        yield TextField::new('refundNo', '退款单号')
            ->setRequired(true)
        ;

        yield AssociationField::new('aftersales', '售后申请')
            ->setRequired(true)
            ->formatValue(function ($value, $entity) {
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
    private function configurePaymentAndStatusFields(): iterable
    {
        yield ChoiceField::new('paymentMethod', '支付方式')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => PaymentMethod::class])
            ->formatValue(fn ($value) => $value instanceof PaymentMethod ? $value->getLabel() : '')
        ;

        yield ChoiceField::new('status', '退款状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => RefundStatus::class])
            ->formatValue(fn ($value) => $value instanceof RefundStatus ? $value->getLabel() : '')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureAmountFields(): iterable
    {
        yield MoneyField::new('refundAmount', '退款金额')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
        ;

        yield IntegerField::new('refundPoints', '退还积分');
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureTransactionFields(): iterable
    {
        yield TextField::new('originalTransactionNo', '原交易号')
            ->hideOnIndex()
        ;

        yield TextField::new('refundTransactionNo', '退款交易号')
            ->hideOnIndex()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureRetryFields(): iterable
    {
        yield IntegerField::new('retryCount', '重试次数')
            ->hideOnForm()
        ;

        yield TextField::new('failureReason', '失败原因')
            ->hideOnIndex()
            ->hideOnForm()
        ;

        yield TextField::new('gatewayResponse', '网关响应')
            ->onlyOnDetail()
            ->formatValue(fn ($value) => $value ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureTimeFields(): iterable
    {
        yield DateTimeField::new('processTime', '处理时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '-')
        ;

        yield DateTimeField::new('completeTime', '完成时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '-')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->formatValue(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // 处理退款
        $processAction = Action::new('process', '处理退款')
            ->linkToCrudAction('process')
            ->setCssClass('btn btn-primary')
            ->setIcon('fa fa-play')
            ->displayIf(function ($entity) {
                return is_object($entity) && method_exists($entity, 'getStatus') && RefundStatus::PENDING === $entity->getStatus();
            })
        ;

        // 重试退款
        $retryAction = Action::new('retry', '重试退款')
            ->linkToCrudAction('retry')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-redo')
            ->displayIf(function ($entity) {
                return is_object($entity) && method_exists($entity, 'getStatus') && method_exists($entity, 'canRetry') && RefundStatus::FAILED === $entity->getStatus() && $entity->canRetry();
            })
        ;

        // 标记完成
        $markCompletedAction = Action::new('markCompleted', '标记完成')
            ->linkToCrudAction('markCompleted')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-check')
            ->displayIf(function ($entity) {
                return is_object($entity) && method_exists($entity, 'getStatus') && RefundStatus::PROCESSING === $entity->getStatus();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $processAction)
            ->add(Crud::PAGE_INDEX, $retryAction)
            ->add(Crud::PAGE_DETAIL, $processAction)
            ->add(Crud::PAGE_DETAIL, $retryAction)
            ->add(Crud::PAGE_DETAIL, $markCompletedAction)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'process', 'retry'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 支付方式筛选
        $paymentMethodChoices = [];
        foreach (PaymentMethod::cases() as $case) {
            $paymentMethodChoices[$case->getLabel()] = $case->value;
        }

        // 退款状态筛选
        $statusChoices = [];
        foreach (RefundStatus::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(TextFilter::new('refundNo', '退款单号'))
            ->add(EntityFilter::new('aftersales', '售后申请'))
            ->add(ChoiceFilter::new('paymentMethod', '支付方式')->setChoices($paymentMethodChoices))
            ->add(ChoiceFilter::new('status', '退款状态')->setChoices($statusChoices))
            ->add(NumericFilter::new('refundAmount', '退款金额'))
            ->add(NumericFilter::new('retryCount', '重试次数'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('processTime', '处理时间'))
            ->add(DateTimeFilter::new('completeTime', '完成时间'))
        ;
    }

    /**
     * 处理退款
     */
    #[AdminAction(routePath: '{entityId}/process', routeName: 'refund_order_process')]
    public function process(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof RefundOrder);

        if (RefundStatus::PENDING !== $entity->getStatus()) {
            $this->addFlash('warning', '该退款订单状态不允许处理');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        // TODO: 实际应用中应该调用退款服务进行处理
        $entity->setStatus(RefundStatus::PROCESSING);
        $entity->setProcessTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('退款订单 %s 已开始处理', $entity->getRefundNo()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 重试退款
     */
    #[AdminAction(routePath: '{entityId}/retry', routeName: 'refund_order_retry')]
    public function retry(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof RefundOrder);

        if (!$entity->canRetry()) {
            $this->addFlash('warning', '该退款订单不能重试');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        $entity->setStatus(RefundStatus::PENDING);
        $entity->incrementRetryCount();

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('退款订单 %s 已重新加入处理队列', $entity->getRefundNo()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 标记完成
     */
    #[AdminAction(routePath: '{entityId}/markCompleted', routeName: 'refund_order_mark_completed')]
    public function markCompleted(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof RefundOrder);

        $entity->markAsSuccess();

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('退款订单 %s 已标记为完成', $entity->getRefundNo()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }
}
