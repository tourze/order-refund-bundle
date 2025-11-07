<?php

declare(strict_types=1);

namespace Tourze\OrderRefundBundle\Procedure\Aftersales;

use OrderCoreBundle\Entity\Contract;
use OrderCoreBundle\Entity\OrderProduct;
use OrderCoreBundle\Repository\ContractRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Attribute\MethodTag;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Procedure\BaseProcedure;
use Tourze\OrderRefundBundle\Enum\AftersalesType;
use Tourze\OrderRefundBundle\Enum\RefundReason;
use Tourze\OrderRefundBundle\Repository\AftersalesRepository;
use Tourze\OrderRefundBundle\Service\AftersalesDataBuilder;
use Tourze\OrderRefundBundle\Service\AftersalesService;
use Tourze\OrderRefundBundle\Service\AftersalesValidator;

#[MethodTag(name: '售后管理')]
#[MethodDoc(description: '申请售后 - 每个商品创建独立的售后单')]
#[MethodExpose(method: 'ApplyAftersalesProcedure')]
#[IsGranted(attribute: 'ROLE_USER')]
class ApplyAftersalesProcedure extends BaseProcedure
{
    #[MethodParam(description: '订单ID')]
    public string $contractId = '';

    #[MethodParam(description: '售后类型')]
    public ?string $type = null;

    #[MethodParam(description: '退款原因')]
    public ?string $reason = null;

    #[MethodParam(description: '问题描述')]
    public ?string $description = null;

    /** @var array<string> */
    #[MethodParam(description: '凭证图片')]
    public array $proofImages = [];

    /** @var array<array{orderProductId: string, quantity: int}> */
    #[MethodParam(description: '售后商品列表')]
    public array $items = [];

    public function __construct(
        private readonly Security $security,
        private readonly AftersalesService $aftersalesService,
        private readonly ContractRepository $contractRepository,
        private readonly AftersalesRepository $aftersalesRepository,
        private readonly AftersalesValidator $validator,
        private readonly AftersalesDataBuilder $dataBuilder,
    ) {
    }

    public function execute(): array
    {
        $user = $this->getCurrentUser();
        $type = $this->validator->validateAftersalesType($this->type);
        $reason = $this->validator->validateRefundReason($this->reason);

        try {
            $contract = $this->validateContract($user);
            if ($contract->getType() == 'redeem'){
                throw new ApiException('赠品不允许售后，如有疑问请联系客服');
            }
            $baseOrderData = $this->dataBuilder->buildBaseOrderData($contract, $user);

            return $this->processAftersalesItems($contract, $baseOrderData, $type, $reason, $user);
        } catch (\InvalidArgumentException $e) {
            throw new ApiException($e->getMessage());
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage());
        }
    }

    private function getCurrentUser(): UserInterface
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            throw new ApiException('用户未登录或类型错误');
        }

        return $user;
    }

    private function validateContract(UserInterface $user): Contract
    {
        $contract = $this->contractRepository->find($this->contractId);
        if (null === $contract) {
            throw new ApiException('订单不存在');
        }

        $this->validator->validateContract($this->contractId, $this->items, $contract, $user);

        return $contract;
    }

    /**
     * @param array<string, mixed> $baseOrderData
     * @return array<string, mixed>
     */
    private function processAftersalesItems(
        Contract $contract,
        array $baseOrderData,
        AftersalesType $type,
        RefundReason $reason,
        UserInterface $user,
    ): array {
        $aftersalesList = [];
        $errors = [];

        $orderProductIds = array_column($this->items, 'orderProductId');
        $activeAftersales = $this->aftersalesRepository->findActiveAftersalesByOrderProductIds($orderProductIds);

        foreach ($this->items as $index => $item) {
            $result = $this->processSingleAftersalesItem(
                $contract, $baseOrderData, $type, $reason, $user,
                $item, $index, $activeAftersales
            );

            if (null !== $result['aftersales']) {
                $aftersalesList[] = $result['aftersales'];
            }

            if (null !== $result['error']) {
                $errors[] = $result['error'];
            }
        }

        return $this->buildFinalResult($aftersalesList, $errors);
    }

    /**
     * @param array<string, mixed> $baseOrderData
     * @param array<string, mixed> $item
     * @param array<string, array<string>> $activeAftersales
     * @return array{aftersales: ?array<string, mixed>, error: ?string}
     */
    private function processSingleAftersalesItem(
        Contract $contract,
        array $baseOrderData,
        AftersalesType $type,
        RefundReason $reason,
        UserInterface $user,
        array $item,
        int $index,
        array $activeAftersales,
    ): array {
        try {
            $orderProduct = $this->validator->validateAftersalesItem(
                $contract,
                $item,
                $index,
                $activeAftersales
            );

            $singleProductData = $this->dataBuilder->buildProductData($orderProduct);

            // Ensure proper type conversion with validation
            if (!is_string($item['orderProductId']) && !is_numeric($item['orderProductId'])) {
                throw new ApiException('订单商品ID格式错误');
            }
            $orderProductId = (string) $item['orderProductId'];

            if (!is_int($item['quantity']) && !is_numeric($item['quantity'])) {
                throw new ApiException('商品数量格式错误');
            }
            $quantity = (int) $item['quantity'];

            $aftersales = $this->aftersalesService->createFromArray(
                $baseOrderData,
                $singleProductData,
                $orderProductId,
                $quantity,
                $type,
                $reason,
                $this->description,
                $this->proofImages,
                $user
            );

            return [
                'aftersales' => $this->dataBuilder->buildAftersalesResponse($aftersales, $orderProduct, $item),
                'error' => null,
            ];
        } catch (\Exception $e) {
            $orderProductId = is_string($item['orderProductId'] ?? '') || is_numeric($item['orderProductId'] ?? '')
                ? (string) ($item['orderProductId'] ?? '')
                : 'unknown';

            return [
                'aftersales' => null,
                'error' => '创建商品 ' . $orderProductId . ' 的售后单失败: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<array<string, mixed>> $aftersalesList
     * @param array<string> $errors
     * @return array<string, mixed>
     */
    private function buildFinalResult(array $aftersalesList, array $errors): array
    {
        if ([] === $aftersalesList) {
            throw new ApiException('所有售后申请都失败了: ' . implode('; ', $errors));
        }

        return $this->dataBuilder->buildFinalResult($aftersalesList, $errors);
    }

    // public function getLockResource(JsonRpcParams $params): ?array
    // {
    //     $user = $this->security->getUser();
    //     if (!$user instanceof UserInterface) {
    //         throw new ApiException('用户未登录或类型错误');
    //     }
    //
    //     return [sprintf('aftersales_apply:%s:%s', $user->getUserIdentifier(), $this->contractId)];
    // }
}
