<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Service;

use BizUserBundle\Repository\BizUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesLog;
use Tourze\OrderRefundBundle\Enum\AftersalesLogAction;
use Tourze\OrderRefundBundle\Enum\AftersalesStage;
use Tourze\OrderRefundBundle\Enum\AftersalesState;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Exception\GeneralAftersalesException;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;

#[WithMonologChannel(channel: 'order_refund')]
readonly class OmsAftersalesSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AftersalesRepository $aftersalesRepository,
        private BizUserRepository $userRepository,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
        private OmsStatusMapper $statusMapper,
        private OmsProductDataMapper $productDataMapper,
        private AftersalesLogisticsProcessor $logisticsProcessor,
        private AftersalesFieldUpdater $fieldUpdater,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return Aftersales
     * @throws GeneralAftersalesException
     */
    public function syncFromOms(array $data): Aftersales
    {
        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');
        $this->logger->info('开始同步OMS售后信息', ['aftersalesNo' => $aftersalesNo]);

        // 检查售后单是否已存在
        $existingAftersales = $this->aftersalesRepository->findOneBy(['referenceNumber' => $aftersalesNo]);
        if (null !== $existingAftersales) {
            return $this->updateExistingAftersales($existingAftersales, $data);
        }

        return $this->createNewAftersales($data);
    }

    /**
     * 从OMS创建新的售后单
     * @param array<string, mixed> $data
     * @return Aftersales
     * @throws GeneralAftersalesException
     */
    public function createFromOms(array $data): Aftersales
    {
        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');
        $this->logger->info('从OMS创建售后单', ['aftersalesNo' => $aftersalesNo]);

        // 检查售后单是否已存在
        $existingAftersales = $this->aftersalesRepository->findOneBy(['referenceNumber' => $aftersalesNo]);
        if (null !== $existingAftersales) {
            throw new GeneralAftersalesException('售后单已存在: ' . $aftersalesNo);
        }

        return $this->createNewAftersales($data);
    }

    /**
     * 从OMS更新售后单状态
     * @param array<string, mixed> $data
     * @return array{aftersales: Aftersales, oldStatus: string}
     * @throws GeneralAftersalesException
     */
    public function updateStatusFromOms(array $data): array
    {
        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');
        $this->logger->info('从OMS更新售后单状态', ['aftersalesNo' => $aftersalesNo]);

        $aftersales = $this->aftersalesRepository->findOneBy(['referenceNumber' => $aftersalesNo]);
        if (null === $aftersales) {
            throw new GeneralAftersalesException('售后单不存在: ' . $aftersalesNo);
        }

        $oldStatus = $aftersales->getState()->value;
        $result = $this->updateExistingAftersalesStatus($aftersales, $data);

        return [
            'aftersales' => $result,
            'oldStatus' => $oldStatus,
        ];
    }

    /**
     * 从OMS更新售后单信息
     * @param array<string, mixed> $data
     * @return Aftersales
     * @throws GeneralAftersalesException
     */
    public function updateInfoFromOms(array $data): Aftersales
    {
        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');
        $this->logger->info('从OMS更新售后单信息', ['aftersalesNo' => $aftersalesNo]);

        $aftersales = $this->aftersalesRepository->findOneBy(['referenceNumber' => $aftersalesNo]);
        if (null === $aftersales) {
            throw new GeneralAftersalesException('售后单不存在: ' . $aftersalesNo);
        }

        return $this->updateExistingAftersalesInfo($aftersales, $data);
    }

    /**
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return Aftersales
     * @throws GeneralAftersalesException
     */
    private function updateExistingAftersales(Aftersales $aftersales, array $data): Aftersales
    {
        $this->logger->info('更新现有售后单', ['id' => $aftersales->getId()]);

        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');

        return $this->executeInTransaction(
            fn () => $this->performAftersalesUpdate($aftersales, $data),
            $aftersalesNo,
            '更新售后信息失败'
        );
    }

    /**
     * 执行售后更新操作
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return Aftersales
     */
    private function performAftersalesUpdate(Aftersales $aftersales, array $data): Aftersales
    {
        $this->syncAftersalesState($aftersales, $data);
        $this->syncBasicAftersalesInfo($aftersales, $data);
        $this->validateAndPersist($aftersales);

        $this->logger->info('OMS售后信息更新成功', [
            'aftersalesNo' => $data['aftersalesNo'],
            'id' => $aftersales->getId(),
        ]);

        return $aftersales;
    }

    /**
     * 同步售后状态
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function syncAftersalesState(Aftersales $aftersales, array $data): void
    {
        $status = $this->extractStringValue($data, 'status', '');
        $newState = $this->statusMapper->mapOmsStatusToState($status);
        if ($aftersales->getState() !== $newState) {
            $aftersales->setState($newState);
            $this->createStatusChangeLog($aftersales, $data);
        }
    }

    /**
     * 同步基本售后信息
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function syncBasicAftersalesInfo(Aftersales $aftersales, array $data): void
    {
        $this->fieldUpdater->syncBasicAftersalesInfo($aftersales, $data);
    }

    /**
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return Aftersales
     * @throws GeneralAftersalesException
     */
    private function updateExistingAftersalesStatus(Aftersales $aftersales, array $data): Aftersales
    {
        $this->logger->info('更新售后单状态', ['id' => $aftersales->getId()]);

        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');

        return $this->executeInTransaction(
            fn () => $this->performStatusUpdate($aftersales, $data),
            $aftersalesNo,
            '更新售后单状态失败'
        );
    }

    /**
     * 执行状态更新操作
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return Aftersales
     */
    private function performStatusUpdate(Aftersales $aftersales, array $data): Aftersales
    {
        $this->updateAftersalesStatus($aftersales, $data);
        $this->updateAftersalesMetadata($aftersales, $data);
        $this->updateAftersalesTimestamps($aftersales, $data);
        $this->validateAndPersist($aftersales);

        $this->logger->info('售后单状态更新成功', [
            'aftersalesNo' => $data['aftersalesNo'],
            'id' => $aftersales->getId(),
            'status' => $data['status'],
        ]);

        return $aftersales;
    }

    /**
     * 更新售后状态
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function updateAftersalesStatus(Aftersales $aftersales, array $data): void
    {
        $status = $this->extractStringValue($data, 'status', '');
        $newState = $this->statusMapper->mapOmsStatusToState($status);
        if ($aftersales->getState() !== $newState) {
            $aftersales->setState($newState);
            $aftersales->setStage($this->statusMapper->mapOmsStatusToStage($status));
            $this->createStatusChangeLog($aftersales, $data);
        }
    }

    /**
     * 更新售后元数据
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function updateAftersalesMetadata(Aftersales $aftersales, array $data): void
    {
        $this->fieldUpdater->updateAftersalesMetadata($aftersales, $data);
        if (isset($data['returnLogistics']) && is_array($data['returnLogistics'])) {
            $this->logisticsProcessor->processReturnLogistics($aftersales, $data['returnLogistics']);
        }
    }

    /**
     * 更新时间戳
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function updateAftersalesTimestamps(Aftersales $aftersales, array $data): void
    {
        $this->fieldUpdater->updateAftersalesTimestamps($aftersales, $data);
    }

    /**
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return Aftersales
     * @throws GeneralAftersalesException
     */
    private function updateExistingAftersalesInfo(Aftersales $aftersales, array $data): Aftersales
    {
        $this->logger->info('更新售后单信息', ['id' => $aftersales->getId()]);

        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');

        return $this->executeInTransaction(
            fn () => $this->performInfoUpdate($aftersales, $data),
            $aftersalesNo,
            '更新售后单信息失败'
        );
    }

    /**
     * 执行信息更新操作
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return Aftersales
     */
    private function performInfoUpdate(Aftersales $aftersales, array $data): Aftersales
    {
        $modifiedFields = $this->updateAllFields($aftersales, $data);
        $this->incrementModificationCount($aftersales);
        $modifyReason = $this->extractStringValue($data, 'modifyReason', '');
        $this->createModificationLog($aftersales, $modifyReason, $modifiedFields);
        $this->validateAndPersist($aftersales);

        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');
        $this->logger->info('售后单信息更新成功', [
            'aftersalesNo' => $aftersalesNo,
            'id' => $aftersales->getId(),
            'modifiedFields' => $modifiedFields,
        ]);

        return $aftersales;
    }

    /**
     * 更新所有字段
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function updateAllFields(Aftersales $aftersales, array $data): array
    {
        $basicFields = $this->updateBasicFields($aftersales, $data);
        $additionalFields = $this->updateAdditionalFields($aftersales, $data);

        return array_merge($basicFields, $additionalFields);
    }

    /**
     * 增加修改次数
     * @param Aftersales $aftersales
     */
    private function incrementModificationCount(Aftersales $aftersales): void
    {
        $aftersales->setModificationCount($aftersales->getModificationCount() + 1);
    }

    /**
     * @param array<string, mixed> $data
     * @return Aftersales
     * @throws GeneralAftersalesException
     */
    private function createNewAftersales(array $data): Aftersales
    {
        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');

        return $this->executeInTransaction(
            fn () => $this->performAftersalesCreation($data),
            $aftersalesNo,
            '同步售后信息失败'
        );
    }

    /**
     * 执行售后创建操作
     * @param array<string, mixed> $data
     * @return Aftersales
     */
    private function performAftersalesCreation(array $data): Aftersales
    {
        $aftersales = $this->buildAftersalesEntity($data);
        $this->processAdditionalData($aftersales, $data);
        $this->persistAftersales($aftersales, $data);

        $this->logger->info('OMS售后信息同步成功', [
            'aftersalesNo' => $data['aftersalesNo'],
            'id' => $aftersales->getId(),
        ]);

        return $aftersales;
    }

    /**
     * 构建售后实体
     * @param array<string, mixed> $data
     * @return Aftersales
     */
    private function buildAftersalesEntity(array $data): Aftersales
    {
        $aftersales = new Aftersales();
        $this->setAftersalesBasicInfo($aftersales, $data);
        $this->setAftersalesAuditInfo($aftersales, $data);

        /** @var array<int, array<string, mixed>> $products */
        $products = isset($data['products']) && is_array($data['products']) ? $data['products'] : [];
        $this->productDataMapper->setAftersalesProductData($aftersales, $products);

        return $aftersales;
    }

    /**
     * 设置售后基本信息
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function setAftersalesBasicInfo(Aftersales $aftersales, array $data): void
    {
        $this->setRequiredFields($aftersales, $data);
        $this->setOptionalApplicationTime($aftersales, $data);
    }

    /**
     * 设置审核信息
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function setAftersalesAuditInfo(Aftersales $aftersales, array $data): void
    {
        $value = $this->extractStringValue($data, 'auditTime', '');
        if ('' !== $value) {
            $aftersales->setProcessedTime(new \DateTimeImmutable($value));
        }

        $auditor = $this->extractStringValue($data, 'auditor', '');
        if ('' !== $auditor) {
            $aftersales->setProcessor($auditor);
        }

        $auditRemark = $this->extractStringValue($data, 'auditRemark', '');
        if ('' !== $auditRemark) {
            $aftersales->setRejectReason($auditRemark);
        }
    }

    /**
     * 设置可选的申请时间
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function setOptionalApplicationTime(Aftersales $aftersales, array $data): void
    {
        $value = $this->extractStringValue($data, 'applyTime', '');
        if ('' !== $value) {
            $aftersales->setApplicationTime(new \DateTimeImmutable($value));
        }
    }

    /**
     * 设置必需字段
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function setRequiredFields(Aftersales $aftersales, array $data): void
    {
        $aftersalesNo = $this->extractStringValue($data, 'aftersalesNo', '');
        $aftersalesType = $this->extractStringValue($data, 'aftersalesType', '');
        $reason = $this->extractStringValue($data, 'reason', '');
        $status = $this->extractStringValue($data, 'status', '');

        $aftersales->setReferenceNumber($aftersalesNo);
        $aftersales->setType($this->statusMapper->mapOmsTypeToEnum($aftersalesType));
        $aftersales->setReason($this->statusMapper->mapOmsReasonToEnum($reason));
        $aftersales->setDescription($this->extractStringValue($data, 'description', ''));

        /** @var array<string> $proofImages */
        $proofImages = isset($data['proofImages']) && is_array($data['proofImages'])
            ? array_map(static fn (mixed $item): string => is_string($item) ? $item : '', $data['proofImages'])
            : [];
        $aftersales->setProofImages($proofImages);
        $aftersales->setState($this->statusMapper->mapOmsStatusToState($status));
        $aftersales->setStage($this->statusMapper->mapOmsStatusToStage($status));

        $refundAmount = isset($data['refundAmount']) && is_numeric($data['refundAmount']) ? (int) $data['refundAmount'] : 0;
        $aftersales->setRequestedAmount($refundAmount);
        $aftersales->setApprovedAmount(0);
        $aftersales->setApplicantName($this->extractStringValue($data, 'applicantName', ''));
        $aftersales->setApplicantPhone($this->extractStringValue($data, 'applicantPhone', ''));
        $aftersales->setOrderNumber($this->extractStringValue($data, 'orderNo', ''));
    }

    /**
     * 处理额外数据
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function processAdditionalData(Aftersales $aftersales, array $data): void
    {
        $applicantPhone = $this->extractStringValue($data, 'applicantPhone', '');
        $this->tryAssociateUser($aftersales, $applicantPhone);
        $this->validateEntity($aftersales);
        $this->createAftersalesLog($aftersales, '从OMS同步售后信息', $data);
        $this->processOptionalLogisticsData($aftersales, $data);
    }

    /**
     * 处理可选的物流数据
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function processOptionalLogisticsData(Aftersales $aftersales, array $data): void
    {
        if (isset($data['returnLogistics']) && is_array($data['returnLogistics'])) {
            $this->logisticsProcessor->processReturnLogistics($aftersales, $data['returnLogistics']);
        }
        if (isset($data['exchangeAddress']) && is_array($data['exchangeAddress'])) {
            $this->logisticsProcessor->processExchangeAddress($aftersales, $data['exchangeAddress']);
        }
    }

    /**
     * 持久化售后实体
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function persistAftersales(Aftersales $aftersales, array $data): void
    {
        $this->entityManager->persist($aftersales);
        $this->entityManager->flush();
    }

    /**
     * @param Aftersales $aftersales
     * @param string $action
     * @param array<string, mixed> $data
     */
    private function createAftersalesLog(Aftersales $aftersales, string $action, array $data): void
    {
        $log = new AftersalesLog();
        $log->setAftersales($aftersales);
        $log->setAction(AftersalesLogAction::SYSTEM_SYNC);
        $log->setContent($action);
        $log->setDescription($action);
        $log->setSystemOperator('OMS_SYNC');
        $log->setOperationTime(new \DateTimeImmutable());
        $log->setDetails($data);

        $this->validateEntity($log);
        $this->entityManager->persist($log);
    }

    /**
     * @param Aftersales $aftersales
     * @param string $reason
     * @param array<string> $modifiedFields
     */
    private function createModificationLog(Aftersales $aftersales, string $reason, array $modifiedFields): void
    {
        $log = new AftersalesLog();
        $log->setAftersales($aftersales);
        $log->setAction(AftersalesLogAction::MODIFY_INFO);
        $log->setContent('OMS修改售后信息: ' . $reason);
        $log->setDescription('OMS修改售后信息: ' . $reason);
        $log->setSystemOperator('OMS_SYNC');
        $log->setOperationTime(new \DateTimeImmutable());
        $log->setDetails([
            'reason' => $reason,
            'modifiedFields' => $modifiedFields,
        ]);

        $this->validateEntity($log);
        $this->entityManager->persist($log);
    }

    /**
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     */
    private function createStatusChangeLog(Aftersales $aftersales, array $data): void
    {
        $logData = $this->extractLogData($data);
        $log = $this->buildStatusChangeLog($aftersales, $logData);

        $this->validateEntity($log);
        $this->entityManager->persist($log);
    }

    /**
     * 提取日志数据
     * @param array<string, mixed> $data
     * @return array{status: string, auditor: string, auditTime: string, auditRemark: string}
     */
    private function extractLogData(array $data): array
    {
        return [
            'status' => $this->extractStringValue($data, 'status', ''),
            'auditor' => $this->extractStringValue($data, 'auditor', 'OMS_SYNC'),
            'auditTime' => $this->extractStringValue($data, 'auditTime', 'now'),
            'auditRemark' => $this->extractStringValue($data, 'auditRemark', ''),
        ];
    }

    /**
     * 安全提取字符串值
     * @param array<string, mixed> $data
     * @param string $key
     * @param string $default
     * @return string
     */
    private function extractStringValue(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * 构建状态变更日志
     * @param Aftersales $aftersales
     * @param array{status: string, auditor: string, auditTime: string, auditRemark: string} $logData
     */
    private function buildStatusChangeLog(Aftersales $aftersales, array $logData): AftersalesLog
    {
        $log = new AftersalesLog();
        $log->setAftersales($aftersales);
        $log->setAction(AftersalesLogAction::STATUS_CHANGE);
        $log->setContent('OMS同步状态变更: ' . $logData['status']);
        $log->setDescription('OMS同步状态变更: ' . $logData['status']);
        $log->setSystemOperator($logData['auditor']);
        $log->setOperationTime(new \DateTimeImmutable($logData['auditTime']));
        $log->setDetails([
            'old_status' => $aftersales->getState()->value,
            'new_status' => $logData['status'],
            'remark' => $logData['auditRemark'],
        ]);

        return $log;
    }

    /**
     * @param Aftersales $aftersales
     * @param string $phone
     */
    private function tryAssociateUser(Aftersales $aftersales, string $phone): void
    {
        try {
            $user = $this->userRepository->findOneBy(['mobile' => $phone]);
            if (null !== $user) {
                $aftersales->setUser($user);
            }
        } catch (\Exception $e) {
            $this->logger->warning('无法关联用户', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param object $entity
     * @throws GeneralAftersalesException
     */
    private function validateEntity(object $entity): void
    {
        $violations = $this->validator->validate($entity);
        if (count($violations) > 0) {
            $messages = array_map(fn ($violation) => $violation->getMessage(), iterator_to_array($violations));
            throw new GeneralAftersalesException('数据验证失败: ' . implode(', ', $messages));
        }
    }

    /**
     * 更新售后单的基本字段信息
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return array<string> 被修改的字段名
     */
    private function updateBasicFields(Aftersales $aftersales, array $data): array
    {
        return $this->fieldUpdater->updateBasicFields($aftersales, $data);
    }

    /**
     * 更新售后单的额外字段信息
     * @param Aftersales $aftersales
     * @param array<string, mixed> $data
     * @return array<string> 被修改的字段名
     */
    private function updateAdditionalFields(Aftersales $aftersales, array $data): array
    {
        $modifiedFields = $this->fieldUpdater->updateAdditionalFields($aftersales, $data);

        // 处理退货物流信息
        if (isset($data['returnLogistics']) && is_array($data['returnLogistics'])) {
            $this->logisticsProcessor->processReturnLogistics($aftersales, $data['returnLogistics']);
            $modifiedFields[] = 'returnLogistics';
        }

        // 处理换货地址
        if (isset($data['exchangeAddress']) && is_array($data['exchangeAddress'])) {
            $this->logisticsProcessor->processExchangeAddress($aftersales, $data['exchangeAddress']);
            $modifiedFields[] = 'exchangeAddress';
        }

        return $modifiedFields;
    }

    /**
     * 在事务中执行操作
     * @template T
     * @param callable(): T $operation
     * @param string $aftersalesNo
     * @param string $errorMessage
     * @return T
     * @throws GeneralAftersalesException
     */
    private function executeInTransaction(callable $operation, string $aftersalesNo, string $errorMessage): mixed
    {
        $this->entityManager->beginTransaction();
        try {
            $result = $operation();
            $this->entityManager->commit();

            return $result;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error($errorMessage, [
                'aftersalesNo' => $aftersalesNo,
                'error' => $e->getMessage(),
            ]);
            throw new GeneralAftersalesException($errorMessage . ': ' . $e->getMessage());
        }
    }

    /**
     * 验证并持久化实体
     * @param Aftersales $aftersales
     */
    private function validateAndPersist(Aftersales $aftersales): void
    {
        $this->validateEntity($aftersales);
        $this->entityManager->persist($aftersales);
        $this->entityManager->flush();
    }
}
