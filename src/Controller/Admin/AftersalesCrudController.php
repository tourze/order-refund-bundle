<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Controller\Admin;

use Doctrine\Common\Collections\Collection;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Service\AftersalesService;

/**
 * @extends AbstractCrudController<Aftersales>
 */
#[AdminCrud(routePath: '/order-refund/aftersales', routeName: 'order_refund_aftersales')]
final class AftersalesCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AftersalesService $aftersalesService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Aftersales::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('售后申请')
            ->setEntityLabelInPlural('售后申请')
            ->setPageTitle('index', '售后申请列表')
            ->setPageTitle('new', '创建售后申请')
            ->setPageTitle('edit', '编辑售后申请')
            ->setPageTitle('detail', '售后申请详情')
            ->setHelp('index', '管理所有售后申请，包括退款、退货、换货等')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['id', 'description', 'rejectReason', 'serviceNote'])
            ->setPaginatorPageSize(20)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName): iterable
    {
        yield from $this->configureBasicFields();
        yield from $this->configureProductFields();
        yield from $this->configureEnumFields();
        yield from $this->configureTextFields();
        yield from $this->configureAssociationFields();
        yield from $this->configureDateTimeFields();
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

        yield TextField::new('referenceNumber', '关联单号')
            ->setRequired(true)
        ;

        yield AssociationField::new('user', '用户')
            ->formatValue($this->createUserFormatter())
        ;

        yield IntegerField::new('modificationCount', '修改次数')
            ->hideOnForm()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureProductFields(): iterable
    {
        yield from $this->configureBasicProductFields();
        yield from $this->configurePriceFields();
        yield from $this->configureRefundAmountFields();
        yield from $this->configureProductSnapshotFields();
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureBasicProductFields(): iterable
    {
        yield TextField::new('productName', '商品名称')
            ->hideOnForm()
        ;

        yield TextField::new('skuName', 'SKU名称')
            ->hideOnForm()
            ->hideOnIndex()
        ;

        yield IntegerField::new('quantity', '售后数量')
            ->hideOnForm()
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configurePriceFields(): iterable
    {
        $priceFormatter = function ($value) {
            return null !== $value && is_numeric($value) ? '¥' . number_format((float) $value, 2) : '-';
        };

        yield TextField::new('originalPrice', '原价')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue($priceFormatter)
        ;

        yield TextField::new('paidPrice', '实付价')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue($priceFormatter)
        ;

        yield TextField::new('originalRefundAmount', '原始申请金额')
            ->hideOnForm()
            ->formatValue($priceFormatter)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureRefundAmountFields(): iterable
    {
        yield TextField::new('actualRefundAmount', '实际退款金额')
            ->setFormTypeOptions([
                'attr' => ['step' => '0.01', 'min' => '0'],
            ])
            ->formatValue(function ($value) {
                return null !== $value && is_numeric($value) ? '¥' . number_format((float) $value, 2) : '-';
            })
            ->hideOnIndex()
            ->hideOnForm()
        ;

        yield TextField::new('refundAmountModifyReason', '修改原因')
            ->hideOnIndex()
            ->hideOnForm()
        ;

        yield TextField::new('refundAmount', '退款金额')
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                if (!$entity instanceof Aftersales) {
                    return null !== $value && is_numeric($value) ? '¥' . number_format((float) $value, 2) : '-';
                }

                // 显示实际退款金额，如果修改过则加上标识
                $actualAmount = $entity->getActualRefundAmount() ?? $value;
                $formatted = null !== $actualAmount && is_numeric($actualAmount) ? '¥' . number_format((float) $actualAmount, 2) : '-';

                if ($entity->isRefundAmountModified()) {
                    $formatted .= ' (已修改)';
                }

                return $formatted;
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureProductSnapshotFields(): iterable
    {
        // 商品主图展示
        yield TextField::new('productMainImage', '商品主图')
            ->hideOnForm()
            ->hideOnIndex()
            ->onlyOnDetail()
            ->formatValue(function ($value, $entity) {
                if (!$entity instanceof Aftersales) {
                    return '';
                }

                $snapshot = $entity->getProductSnapshot();
                $mainImage = $snapshot['productMainImage'] ?? $snapshot['skuMainImage'] ?? null;

                return $mainImage ?? '无图片';
            })
        ;

        // 商品副标题
        yield TextField::new('productSubtitle', '商品副标题')
            ->hideOnForm()
            ->hideOnIndex()
            ->onlyOnDetail()
            ->formatValue(function ($value, $entity) {
                if (!$entity instanceof Aftersales) {
                    return '';
                }

                $snapshot = $entity->getProductSnapshot();

                return $snapshot['productSubtitle'] ?? '-';
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureEnumFields(): iterable
    {
        yield $this->createEnumField('type', '售后类型', AftersalesType::class);
        yield $this->createEnumField('reason', '退款原因', RefundReason::class);
        yield $this->createEnumField('state', '售后状态', AftersalesState::class);
        yield $this->createEnumField('stage', '售后阶段', AftersalesStage::class);
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureTextFields(): iterable
    {
        yield TextareaField::new('description', '问题描述')
            ->hideOnIndex()
        ;

        yield TextField::new('rejectReason', '拒绝原因')
            ->hideOnIndex()
            ->hideOnForm()
        ;

        yield TextField::new('serviceNote', '客服备注');
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureAssociationFields(): iterable
    {
        // 注意：AftersalesOrder、RefundOrder、ReturnOrder、ExchangeOrder 等实体
        // 通过外键关联到 Aftersales，但 Aftersales 实体中没有定义反向关联
        // 如需要显示这些信息，可以通过自定义格式化器或模板实现

        yield AssociationField::new('logs', '操作日志')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                if ($value instanceof Collection) {
                    return sprintf('共 %d 条记录', $value->count());
                }

                return '-';
            })
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function configureDateTimeFields(): iterable
    {
        $formatter = $this->createDateTimeFormatter();

        yield DateTimeField::new('autoProcessTime', '自动处理时间')
            ->hideOnForm()
            ->formatValue($formatter)
        ;

        yield DateTimeField::new('auditTime', '审核时间')
            ->hideOnForm()
            ->formatValue($formatter)
        ;

        yield DateTimeField::new('completedTime', '完成时间')
            ->hideOnForm()
            ->formatValue($formatter)
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue($formatter)
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue($formatter)
        ;
    }

    private function createEnumField(string $property, string $label, string $enumClass): ChoiceField
    {
        return ChoiceField::new($property, $label)
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => $enumClass])
            ->formatValue(function ($value) {
                return is_object($value) && method_exists($value, 'getLabel') ? $value->getLabel() : '';
            })
        ;
    }

    private function createUserFormatter(): \Closure
    {
        return function ($value) {
            if (is_object($value) && method_exists($value, 'getUsername') && method_exists($value, 'getId')) {
                $username = $value->getUsername();
                $id = $value->getId();

                $usernameStr = is_string($username) || is_int($username) || is_float($username) ? (string) $username : '';
                $idStr = is_string($id) || is_int($id) ? (string) $id : '';

                return sprintf('%s (%s)', $usernameStr, $idStr);
            }

            return '';
        };
    }

    private function createDateTimeFormatter(): \Closure
    {
        return function ($value) {
            return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '-';
        };
    }

    public function configureActions(Actions $actions): Actions
    {
        // 审核通过
        $approveAction = Action::new('approve', '审核通过')
            ->linkToCrudAction('approve')
            ->setCssClass('btn btn-success')
            ->setIcon('fa fa-check')
            ->displayIf(function ($entity) {
                return $entity instanceof Aftersales && AftersalesState::PENDING_APPROVAL === $entity->getState();
            })
        ;

        // 审核拒绝
        $rejectAction = Action::new('reject', '审核拒绝')
            ->linkToCrudAction('reject')
            ->setCssClass('btn btn-danger')
            ->setIcon('fa fa-times')
            ->displayIf(function ($entity) {
                return $entity instanceof Aftersales && AftersalesState::PENDING_APPROVAL === $entity->getState();
            })
        ;

        // 完成售后
        $completeAction = Action::new('complete', '完成售后')
            ->linkToCrudAction('complete')
            ->setCssClass('btn btn-primary')
            ->setIcon('fa fa-flag-checkered')
            ->displayIf(function ($entity) {
                if (!$entity instanceof Aftersales) {
                    return false;
                }

                return in_array($entity->getState(), [
                    AftersalesState::APPROVED,
                    AftersalesState::PENDING_REFUND,
                    AftersalesState::PENDING_EXCHANGE,
                ], true);
            })
        ;

        // 修改退款金额
        $modifyRefundAmountAction = Action::new('modifyRefundAmount', '修改退款金额')
            ->linkToCrudAction('modifyRefundAmount')
            ->setCssClass('btn btn-warning')
            ->setIcon('fa fa-edit')
            ->displayIf(function ($entity) {
                return $entity instanceof Aftersales && $entity->canModifyRefundAmount();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_INDEX, $modifyRefundAmountAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $completeAction)
            ->add(Crud::PAGE_DETAIL, $modifyRefundAmountAction)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'approve', 'reject', 'modifyRefundAmount'])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        // 售后类型筛选
        $typeChoices = [];
        foreach (AftersalesType::cases() as $case) {
            $typeChoices[$case->getLabel()] = $case->value;
        }

        // 售后状态筛选
        $stateChoices = [];
        foreach (AftersalesState::cases() as $case) {
            $stateChoices[$case->getLabel()] = $case->value;
        }

        // 售后阶段筛选
        $stageChoices = [];
        foreach (AftersalesStage::cases() as $case) {
            $stageChoices[$case->getLabel()] = $case->value;
        }

        // 退款原因筛选
        $reasonChoices = [];
        foreach (RefundReason::cases() as $case) {
            $reasonChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(TextFilter::new('referenceNumber', '关联单号'))
            ->add(EntityFilter::new('user', '用户'))
            ->add(ChoiceFilter::new('type', '售后类型')->setChoices($typeChoices))
            ->add(ChoiceFilter::new('reason', '退款原因')->setChoices($reasonChoices))
            ->add(ChoiceFilter::new('state', '售后状态')->setChoices($stateChoices))
            ->add(ChoiceFilter::new('stage', '售后阶段')->setChoices($stageChoices))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('auditTime', '审核时间'))
            ->add(DateTimeFilter::new('completedTime', '完成时间'))
        ;
    }

    /**
     * 审核通过
     */
    #[AdminAction(routePath: '{entityId}/approve', routeName: 'approve')]
    public function approve(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Aftersales);

        if (AftersalesState::PENDING_APPROVAL !== $entity->getState()) {
            $this->addFlash('warning', '该售后申请状态不允许审核');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        $entity->setState(AftersalesState::APPROVED);
        $entity->setAuditTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('售后申请 #%s 已审核通过', (string) $entity->getId()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 审核拒绝
     */
    #[AdminAction(routePath: '{entityId}/reject', routeName: 'reject')]
    public function reject(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Aftersales);

        if (AftersalesState::PENDING_APPROVAL !== $entity->getState()) {
            $this->addFlash('warning', '该售后申请状态不允许拒绝');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        // TODO: 实际应用中应该有一个表单让管理员输入拒绝原因
        $entity->setState(AftersalesState::REJECTED);
        $entity->setRejectReason('管理员拒绝');
        $entity->setAuditTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('售后申请 #%s 已拒绝', (string) $entity->getId()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 完成售后
     */
    #[AdminAction(routePath: '{entityId}/complete', routeName: 'complete')]
    public function complete(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Aftersales);

        $entity->setState(AftersalesState::COMPLETED);
        $entity->setCompletedTime(new \DateTimeImmutable());

        $this->entityManager->flush();

        $this->addFlash('success', sprintf('售后申请 #%s 已完成', (string) $entity->getId()));

        return $this->redirect($request->headers->get('referer') ?? '/admin');
    }

    /**
     * 修改退款金额
     */
    #[AdminAction(routePath: '{entityId}/modify-refund-amount', routeName: 'modifyRefundAmount', methods: ['GET', 'POST'])]
    public function modifyRefundAmount(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Aftersales);

        if (!$entity->canModifyRefundAmount()) {
            $this->addFlash('danger', '当前状态不允许修改退款金额');

            return $this->redirect($request->headers->get('referer') ?? '/admin');
        }

        // 如果是 POST 请求，处理表单提交
        if ($request->isMethod('POST')) {
            $newAmount = $request->request->get('refund_amount');
            $reason = $request->request->get('reason');

            if (null === $newAmount || !is_numeric($newAmount) || (float) $newAmount < 0) {
                $this->addFlash('danger', '请输入有效的退款金额');

                return $this->redirect($request->getUri());
            }

            // 验证金额不能超过原始申请金额
            $originalAmount = (float) $entity->getOriginalRefundAmount();
            if ((float) $newAmount > $originalAmount) {
                $this->addFlash('danger', sprintf('退款金额不能超过原始申请金额 ¥%.2f', $originalAmount));

                return $this->redirect($request->getUri());
            }

            try {
                $this->aftersalesService->modifyRefundAmount(
                    (string) $entity->getId(),
                    (string) $newAmount,
                    (string) $reason
                );

                $this->addFlash('success', sprintf('退款金额已修改为 ¥%.2f', (float) $newAmount));

                return $this->redirect($request->headers->get('referer') ?? '/admin');
            } catch (\Exception $e) {
                $this->addFlash('danger', '修改失败: ' . $e->getMessage());
            }
        }

        // 显示修改表单 (简化版本，实际应用中应该有专门的模板)
        return new Response(sprintf(
            '<h1>修改退款金额</h1><p>原始金额: %s</p><p>当前金额: %s</p><form method="post"><input name="refund_amount" placeholder="新金额"><input name="reason" placeholder="修改原因"><button type="submit">提交</button></form>',
            $entity->getOriginalRefundAmount(),
            $entity->getActualRefundAmount()
        ));
    }
}
