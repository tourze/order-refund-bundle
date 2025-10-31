# order-refund-bundle 技术设计（完全独立架构）

## 1. 技术概览

### 1.1 架构模式
采用**扁平化 Service 层架构** + **纯数据快照存储**模式，实现完全独立的售后处理系统，不依赖任何外部实体。

### 1.2 核心设计原则
- **完全独立**: 不依赖任何外部包的实体，通过纯数据传递实现功能
- **贫血模型**: 实体只包含数据和 getter/setter，业务逻辑在 Service 层
- **数据快照**: 创建时复制所有必要数据，确保历史准确性
- **事件驱动**: 通过事件实现与外部模块的松耦合通信
- **接口标准化**: 定义清晰的数据接口规范，便于集成

### 1.3 技术决策理由
- **移除实体依赖**: 不使用 `OrderCoreBundle\Entity\Contract`，避免强耦合
- **纯数据传递**: 通过数组或 DTO 传递数据，提高模块独立性
- **快照存储**: 确保售后记录的历史完整性，不受外部数据变更影响
- **灵活关联**: 通过 referenceNumber 支持多种关联场景

## 2. 公共API设计

### 2.1 核心服务接口

#### AftersalesService - 售后核心服务
```php
namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;

class AftersalesService
{
    /**
     * 创建售后申请
     * 
     * @param OrderDataDTO $orderData 订单数据传输对象
     * @param ProductDataDTO[] $products 商品数据数组
     * @param array $refundItems 退款项目 [['productId' => string, 'quantity' => int], ...]
     * @param string $type 售后类型 'refund_only' | 'return_refund'
     * @param string $reason 售后原因
     * @param string|null $userId 用户ID
     * @return Aftersales
     */
    public function create(
        OrderDataDTO $orderData,
        array $products,
        array $refundItems,
        string $type,
        string $reason,
        ?string $userId = null
    ): Aftersales;
    
    /**
     * 使用数组创建售后（便捷方法）
     */
    public function createFromArray(
        array $orderData,
        array $products,
        array $refundItems,
        string $type,
        string $reason,
        ?string $userId = null
    ): Aftersales;
    
    /**
     * 审批售后申请
     */
    public function approve(Aftersales $aftersales, ?string $approvedBy = null): void;
    
    /**
     * 拒绝售后申请
     */
    public function reject(Aftersales $aftersales, string $rejectReason, ?string $rejectedBy = null): void;
    
    /**
     * 完成售后处理
     */
    public function complete(Aftersales $aftersales, ?string $completedBy = null): void;
    
    /**
     * 获取售后详情
     */
    public function getAftersalesDetails(string $aftersalesId): ?Aftersales;
    
    /**
     * 按引用号查询售后申请
     */
    public function findByReferenceNumber(string $referenceNumber): array;
}
```

### 2.2 数据传输对象（DTO）

#### OrderDataDTO - 订单数据传输对象
```php
namespace Tourze\OrderRefundBundle\DTO;

class OrderDataDTO
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly string $orderStatus,
        public readonly \DateTimeInterface $orderCreatedAt,
        public readonly string $userId,
        public readonly float $totalAmount,
        public readonly ?array $extra = null
    ) {}
    
    /**
     * 从数组创建DTO
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderNumber: $data['orderNumber'],
            orderStatus: $data['orderStatus'],
            orderCreatedAt: $data['orderCreatedAt'] instanceof \DateTimeInterface 
                ? $data['orderCreatedAt'] 
                : new \DateTime($data['orderCreatedAt']),
            userId: $data['userId'],
            totalAmount: (float)$data['totalAmount'],
            extra: $data['extra'] ?? null
        );
    }
    
    /**
     * 验证数据完整性
     */
    public function validate(): array
    {
        $errors = [];
        if (empty($this->orderNumber)) {
            $errors[] = '订单编号不能为空';
        }
        if (empty($this->orderStatus)) {
            $errors[] = '订单状态不能为空';
        }
        if ($this->totalAmount < 0) {
            $errors[] = '订单金额不能为负数';
        }
        return $errors;
    }
}
```

#### ProductDataDTO - 商品数据传输对象
```php
namespace Tourze\OrderRefundBundle\DTO;

class ProductDataDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly string $skuId,
        public readonly string $productName,
        public readonly string $skuName,
        public readonly float $originalPrice,
        public readonly float $paidPrice,
        public readonly float $discountAmount,
        public readonly int $orderQuantity,
        public readonly ?array $attributes = null
    ) {}
    
    /**
     * 从数组创建DTO
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['productId'],
            skuId: $data['skuId'],
            productName: $data['productName'],
            skuName: $data['skuName'],
            originalPrice: (float)$data['originalPrice'],
            paidPrice: (float)$data['paidPrice'],
            discountAmount: (float)$data['discountAmount'],
            orderQuantity: (int)$data['orderQuantity'],
            attributes: $data['attributes'] ?? null
        );
    }
    
    /**
     * 验证数据完整性
     */
    public function validate(): array
    {
        $errors = [];
        if (empty($this->productId)) {
            $errors[] = '商品ID不能为空';
        }
        if (empty($this->productName)) {
            $errors[] = '商品名称不能为空';
        }
        if ($this->orderQuantity <= 0) {
            $errors[] = '商品数量必须大于0';
        }
        if ($this->paidPrice < 0) {
            $errors[] = '实付价格不能为负数';
        }
        return $errors;
    }
}
```

### 2.3 扩展接口定义

#### AftersalesRuleEngineInterface - 规则引擎接口
```php
namespace Tourze\OrderRefundBundle\Contract;

use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;

interface AftersalesRuleEngineInterface
{
    /**
     * 验证是否可以创建售后
     */
    public function canCreateAftersales(OrderDataDTO $orderData): bool;
    
    /**
     * 验证售后商品和数量
     */
    public function validateRefundItems(
        array $products,
        array $refundItems
    ): array;
    
    /**
     * 计算退款金额
     */
    public function calculateRefundAmount(array $aftersalesProducts): float;
    
    /**
     * 获取支持的售后类型
     */
    public function getSupportedTypes(OrderDataDTO $orderData): array;
}
```

### 2.4 使用示例代码

```php
use Tourze\OrderRefundBundle\Service\AftersalesService;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;

// 方式1：使用DTO创建售后
$orderData = new OrderDataDTO(
    orderNumber: 'ORD20241225001',
    orderStatus: 'paid',
    orderCreatedAt: new \DateTime('2024-12-20'),
    userId: 'user_123',
    totalAmount: 299.99
);

$products = [
    new ProductDataDTO(
        productId: 'prod_001',
        skuId: 'sku_001',
        productName: '商品名称',
        skuName: '规格名称',
        originalPrice: 199.99,
        paidPrice: 179.99,
        discountAmount: 20.00,
        orderQuantity: 2,
        attributes: ['color' => 'red', 'size' => 'L']
    )
];

$refundItems = [
    ['productId' => 'prod_001', 'quantity' => 1]
];

$aftersales = $aftersalesService->create(
    orderData: $orderData,
    products: $products,
    refundItems: $refundItems,
    type: 'refund_only',
    reason: '商品质量问题',
    userId: 'user_123'
);

// 方式2：使用数组创建售后（更灵活）
$aftersales = $aftersalesService->createFromArray(
    orderData: [
        'orderNumber' => 'ORD20241225001',
        'orderStatus' => 'paid',
        'orderCreatedAt' => '2024-12-20',
        'userId' => 'user_123',
        'totalAmount' => 299.99
    ],
    products: [
        [
            'productId' => 'prod_001',
            'skuId' => 'sku_001',
            'productName' => '商品名称',
            'skuName' => '规格名称',
            'originalPrice' => 199.99,
            'paidPrice' => 179.99,
            'discountAmount' => 20.00,
            'orderQuantity' => 2
        ]
    ],
    refundItems: [
        ['productId' => 'prod_001', 'quantity' => 1]
    ],
    type: 'refund_only',
    reason: '商品质量问题'
);

// 审批售后
$aftersalesService->approve($aftersales, 'admin_001');
```

### 2.5 错误处理策略

```php
namespace Tourze\OrderRefundBundle\Exception;

// 基础售后异常
class AftersalesException extends \RuntimeException {}

// 数据验证异常
class InvalidOrderDataException extends AftersalesException {}
class InvalidProductDataException extends AftersalesException {}
class MissingRequiredFieldException extends AftersalesException {}

// 业务规则异常
class OrderNotRefundableException extends AftersalesException {}
class InvalidRefundQuantityException extends AftersalesException {}
class RefundAmountExceededException extends AftersalesException {}

// 状态相关异常
class InvalidAftersalesStatusException extends AftersalesException {}
class WorkflowTransitionNotAllowedException extends AftersalesException {}
```

## 3. 内部架构

### 3.1 核心组件划分

```
packages/order-refund-bundle/
├── src/
│   ├── Entity/              # 贫血模型实体
│   │   ├── Aftersales.php
│   │   ├── AftersalesProduct.php
│   │   └── AftersalesOrder.php
│   ├── Repository/          # 数据访问层
│   │   ├── AftersalesRepository.php
│   │   ├── AftersalesProductRepository.php
│   │   └── AftersalesOrderRepository.php
│   ├── Service/             # 扁平化业务逻辑层
│   │   ├── AftersalesService.php
│   │   ├── DataValidationService.php
│   │   ├── SnapshotService.php
│   │   ├── RuleEngineService.php
│   │   ├── WorkflowService.php
│   │   └── RefundCalculatorService.php
│   ├── DTO/                 # 数据传输对象
│   │   ├── OrderDataDTO.php
│   │   ├── ProductDataDTO.php
│   │   └── RefundRequestDTO.php
│   ├── Event/               # 事件类
│   │   ├── AftersalesCreatedEvent.php
│   │   ├── AftersalesStatusChangedEvent.php
│   │   └── AftersalesCompletedEvent.php
│   ├── Contract/            # 接口定义
│   │   ├── AftersalesRuleEngineInterface.php
│   │   └── AftersalesWorkflowInterface.php
│   ├── Enum/                # 枚举类
│   │   ├── AftersalesType.php
│   │   └── AftersalesStatus.php
│   ├── Exception/           # 异常类
│   └── OrderRefundBundle.php
```

### 3.2 实体设计（贫血模型 + 完全独立）

#### Aftersales 实体（主实体）
```php
namespace Tourze\OrderRefundBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: AftersalesRepository::class)]
#[ORM\Table(name: 'aftersales')]
class Aftersales
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    private string $id;
    
    // 外部关联字段（不使用外键）
    #[ORM\Column(type: 'string', length: 64)]
    private string $referenceNumber;
    
    // 售后基本信息
    #[ORM\Column(type: 'string', length: 20)]
    private string $type; // 'refund_only', 'return_refund'
    
    #[ORM\Column(type: 'string', length: 20)]
    private string $status; // 'pending', 'approved', 'rejected', 'completed'
    
    #[ORM\Column(type: 'text')]
    private string $reason;
    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $totalRefundAmount;
    
    // 用户信息
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $userId = null;
    
    // 操作记录
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $approvedBy = null;
    
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $rejectedBy = null;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectReason = null;
    
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $completedBy = null;
    
    // 时间戳
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $rejectedAt = null;
    
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;
    
    // 关联的售后商品
    #[ORM\OneToMany(targetEntity: AftersalesProduct::class, mappedBy: 'aftersales', cascade: ['persist', 'remove'])]
    private Collection $aftersalesProducts;
    
    // 关联的订单快照
    #[ORM\OneToOne(targetEntity: AftersalesOrder::class, mappedBy: 'aftersales', cascade: ['persist', 'remove'])]
    private ?AftersalesOrder $aftersalesOrder = null;
    
    public function __construct()
    {
        $this->aftersalesProducts = new ArrayCollection();
    }
    
    // 只有 getter/setter，无业务逻辑
    public function getId(): string { return $this->id; }
    public function setId(string $id): self { $this->id = $id; return $this; }
    
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function setReferenceNumber(string $referenceNumber): self { 
        $this->referenceNumber = $referenceNumber; 
        return $this; 
    }
    
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    
    public function getReason(): string { return $this->reason; }
    public function setReason(string $reason): self { $this->reason = $reason; return $this; }
    
    public function getTotalRefundAmount(): float { return $this->totalRefundAmount; }
    public function setTotalRefundAmount(float $amount): self { 
        $this->totalRefundAmount = $amount; 
        return $this; 
    }
    
    public function getUserId(): ?string { return $this->userId; }
    public function setUserId(?string $userId): self { $this->userId = $userId; return $this; }
    
    // ... 其他 getter/setter
    
    public function getAftersalesProducts(): Collection { return $this->aftersalesProducts; }
    public function addAftersalesProduct(AftersalesProduct $product): self
    {
        if (!$this->aftersalesProducts->contains($product)) {
            $this->aftersalesProducts[] = $product;
            $product->setAftersales($this);
        }
        return $this;
    }
    
    public function getAftersalesOrder(): ?AftersalesOrder { return $this->aftersalesOrder; }
    public function setAftersalesOrder(?AftersalesOrder $order): self { 
        $this->aftersalesOrder = $order;
        if ($order && $order->getAftersales() !== $this) {
            $order->setAftersales($this);
        }
        return $this; 
    }
}
```

#### AftersalesProduct 实体（商品快照）
```php
namespace Tourze\OrderRefundBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AftersalesProductRepository::class)]
#[ORM\Table(name: 'aftersales_product')]
class AftersalesProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    private string $id;
    
    #[ORM\ManyToOne(targetEntity: Aftersales::class, inversedBy: 'aftersalesProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private Aftersales $aftersales;
    
    // 商品信息快照
    #[ORM\Column(type: 'string', length: 32)]
    private string $productId;
    
    #[ORM\Column(type: 'string', length: 32)]
    private string $skuId;
    
    #[ORM\Column(type: 'string', length: 255)]
    private string $productName;
    
    #[ORM\Column(type: 'string', length: 255)]
    private string $skuName;
    
    // 价格信息快照
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $originalPrice;
    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $paidPrice;
    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $discountAmount;
    
    // 数量信息
    #[ORM\Column(type: 'integer')]
    private int $orderQuantity;
    
    #[ORM\Column(type: 'integer')]
    private int $refundQuantity;
    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $refundAmount;
    
    // 商品属性（JSON）
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attributes = null;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;
    
    // 只有 getter/setter
    public function getId(): string { return $this->id; }
    public function setId(string $id): self { $this->id = $id; return $this; }
    
    public function getAftersales(): Aftersales { return $this->aftersales; }
    public function setAftersales(Aftersales $aftersales): self { 
        $this->aftersales = $aftersales; 
        return $this; 
    }
    
    // ... 其他 getter/setter
}
```

#### AftersalesOrder 实体（订单快照）
```php
namespace Tourze\OrderRefundBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AftersalesOrderRepository::class)]
#[ORM\Table(name: 'aftersales_order')]
class AftersalesOrder
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    private string $id;
    
    #[ORM\OneToOne(targetEntity: Aftersales::class, inversedBy: 'aftersalesOrder')]
    #[ORM\JoinColumn(nullable: false)]
    private Aftersales $aftersales;
    
    // 订单信息快照
    #[ORM\Column(type: 'string', length: 64)]
    private string $orderNumber;
    
    #[ORM\Column(type: 'string', length: 20)]
    private string $orderStatus;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $orderCreatedAt;
    
    #[ORM\Column(type: 'string', length: 32)]
    private string $userId;
    
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $totalAmount;
    
    // 扩展信息（JSON）
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extra = null;
    
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;
    
    // 只有 getter/setter
    public function getId(): string { return $this->id; }
    public function setId(string $id): self { $this->id = $id; return $this; }
    
    public function getAftersales(): Aftersales { return $this->aftersales; }
    public function setAftersales(Aftersales $aftersales): self { 
        $this->aftersales = $aftersales; 
        return $this; 
    }
    
    public function getOrderNumber(): string { return $this->orderNumber; }
    public function setOrderNumber(string $orderNumber): self { 
        $this->orderNumber = $orderNumber; 
        return $this; 
    }
    
    // ... 其他 getter/setter
}
```

### 3.3 服务层设计（扁平化 + 纯数据处理）

#### AftersalesService - 核心业务逻辑
```php
namespace Tourze\OrderRefundBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;
use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Event\AftersalesCreatedEvent;
use Tourze\OrderRefundBundle\Exception\InvalidOrderDataException;
use Tourze\OrderRefundBundle\Exception\InvalidProductDataException;

class AftersalesService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DataValidationService $validator,
        private readonly SnapshotService $snapshotService,
        private readonly RuleEngineService $ruleEngine,
        private readonly WorkflowService $workflow,
        private readonly RefundCalculatorService $calculator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}
    
    public function create(
        OrderDataDTO $orderData,
        array $products,
        array $refundItems,
        string $type,
        string $reason,
        ?string $userId = null
    ): Aftersales {
        // 1. 验证订单数据
        $orderErrors = $orderData->validate();
        if (!empty($orderErrors)) {
            throw new InvalidOrderDataException('订单数据验证失败: ' . implode(', ', $orderErrors));
        }
        
        // 2. 验证商品数据
        foreach ($products as $product) {
            if (!$product instanceof ProductDataDTO) {
                throw new InvalidProductDataException('商品数据必须是 ProductDataDTO 实例');
            }
            $productErrors = $product->validate();
            if (!empty($productErrors)) {
                throw new InvalidProductDataException('商品数据验证失败: ' . implode(', ', $productErrors));
            }
        }
        
        // 3. 验证业务规则
        if (!$this->ruleEngine->canCreateAftersales($orderData)) {
            throw new OrderNotRefundableException('订单不符合售后条件');
        }
        
        $validationErrors = $this->ruleEngine->validateRefundItems($products, $refundItems);
        if (!empty($validationErrors)) {
            throw new RefundRuleViolationException('售后商品验证失败: ' . implode(', ', $validationErrors));
        }
        
        // 4. 创建售后申请
        $aftersales = new Aftersales();
        $aftersales->setId($this->generateId());
        $aftersales->setReferenceNumber($orderData->orderNumber);
        $aftersales->setType($type);
        $aftersales->setStatus(AftersalesStatus::PENDING);
        $aftersales->setReason($reason);
        $aftersales->setUserId($userId ?? $orderData->userId);
        $aftersales->setCreatedAt(new \DateTime());
        
        // 5. 创建订单快照
        $orderSnapshot = $this->snapshotService->createOrderSnapshot($aftersales, $orderData);
        $aftersales->setAftersalesOrder($orderSnapshot);
        $this->entityManager->persist($orderSnapshot);
        
        // 6. 创建商品快照
        $totalRefundAmount = 0;
        foreach ($refundItems as $item) {
            $productData = $this->findProductData($products, $item['productId']);
            if (!$productData) {
                throw new ProductNotFoundException("商品不存在: {$item['productId']}");
            }
            
            $productSnapshot = $this->snapshotService->createProductSnapshot(
                $aftersales,
                $productData,
                $item['quantity']
            );
            
            $refundAmount = $this->calculator->calculateProductRefund($productSnapshot);
            $productSnapshot->setRefundAmount($refundAmount);
            $totalRefundAmount += $refundAmount;
            
            $aftersales->addAftersalesProduct($productSnapshot);
            $this->entityManager->persist($productSnapshot);
        }
        
        $aftersales->setTotalRefundAmount($totalRefundAmount);
        
        // 7. 保存到数据库
        $this->entityManager->persist($aftersales);
        $this->entityManager->flush();
        
        // 8. 发布事件
        $this->eventDispatcher->dispatch(
            new AftersalesCreatedEvent($aftersales)
        );
        
        return $aftersales;
    }
    
    public function createFromArray(
        array $orderData,
        array $products,
        array $refundItems,
        string $type,
        string $reason,
        ?string $userId = null
    ): Aftersales {
        // 转换数组为DTO
        $orderDTO = OrderDataDTO::fromArray($orderData);
        
        $productDTOs = [];
        foreach ($products as $product) {
            $productDTOs[] = ProductDataDTO::fromArray($product);
        }
        
        return $this->create($orderDTO, $productDTOs, $refundItems, $type, $reason, $userId);
    }
    
    public function approve(Aftersales $aftersales, ?string $approvedBy = null): void
    {
        if (!$this->workflow->canTransition($aftersales, 'approve')) {
            throw new WorkflowTransitionNotAllowedException('当前状态不允许审批');
        }
        
        $this->workflow->transition($aftersales, 'approve', $approvedBy);
        $aftersales->setApprovedBy($approvedBy);
        $aftersales->setApprovedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        $this->eventDispatcher->dispatch(
            new AftersalesStatusChangedEvent($aftersales, AftersalesStatus::APPROVED)
        );
    }
    
    // ... 其他方法实现
    
    private function generateId(): string
    {
        return 'afs_' . uniqid() . '_' . time();
    }
    
    private function findProductData(array $products, string $productId): ?ProductDataDTO
    {
        foreach ($products as $product) {
            if ($product->productId === $productId) {
                return $product;
            }
        }
        return null;
    }
}
```

#### SnapshotService - 数据快照服务
```php
namespace Tourze\OrderRefundBundle\Service;

use Tourze\OrderRefundBundle\Entity\Aftersales;
use Tourze\OrderRefundBundle\Entity\AftersalesProduct;
use Tourze\OrderRefundBundle\Entity\AftersalesOrder;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;

class SnapshotService
{
    /**
     * 创建订单快照
     */
    public function createOrderSnapshot(
        Aftersales $aftersales,
        OrderDataDTO $orderData
    ): AftersalesOrder {
        $snapshot = new AftersalesOrder();
        $snapshot->setId($this->generateId('aso'));
        $snapshot->setAftersales($aftersales);
        $snapshot->setOrderNumber($orderData->orderNumber);
        $snapshot->setOrderStatus($orderData->orderStatus);
        $snapshot->setOrderCreatedAt($orderData->orderCreatedAt);
        $snapshot->setUserId($orderData->userId);
        $snapshot->setTotalAmount($orderData->totalAmount);
        $snapshot->setExtra($orderData->extra);
        $snapshot->setCreatedAt(new \DateTime());
        
        return $snapshot;
    }
    
    /**
     * 创建商品快照
     */
    public function createProductSnapshot(
        Aftersales $aftersales,
        ProductDataDTO $productData,
        int $refundQuantity
    ): AftersalesProduct {
        $snapshot = new AftersalesProduct();
        $snapshot->setId($this->generateId('asp'));
        $snapshot->setAftersales($aftersales);
        
        // 复制商品信息
        $snapshot->setProductId($productData->productId);
        $snapshot->setSkuId($productData->skuId);
        $snapshot->setProductName($productData->productName);
        $snapshot->setSkuName($productData->skuName);
        
        // 复制价格信息
        $snapshot->setOriginalPrice($productData->originalPrice);
        $snapshot->setPaidPrice($productData->paidPrice);
        $snapshot->setDiscountAmount($productData->discountAmount);
        
        // 设置数量信息
        $snapshot->setOrderQuantity($productData->orderQuantity);
        $snapshot->setRefundQuantity($refundQuantity);
        
        // 复制属性
        $snapshot->setAttributes($productData->attributes);
        $snapshot->setCreatedAt(new \DateTime());
        
        return $snapshot;
    }
    
    private function generateId(string $prefix): string
    {
        return $prefix . '_' . uniqid() . '_' . time();
    }
}
```

### 3.4 数据流设计

```
创建售后申请流程：
用户请求（传入纯数据）
    ↓
AftersalesService::create() 或 createFromArray()
    ↓
数据验证（OrderDataDTO::validate(), ProductDataDTO::validate()）
    ↓
RuleEngineService::canCreateAftersales()  ← 验证业务规则
    ↓
RuleEngineService::validateRefundItems()  ← 验证商品和数量
    ↓
创建 Aftersales 实体
    ↓
SnapshotService::createOrderSnapshot()  ← 创建订单快照
    ↓
SnapshotService::createProductSnapshot()  ← 为每个商品创建快照
    ↓
RefundCalculatorService::calculateProductRefund()  ← 计算退款金额
    ↓
EntityManager::persist() & flush()
    ↓
EventDispatcher::dispatch(AftersalesCreatedEvent)
    ↓
外部模块通过事件监听处理
```

## 4. 扩展机制

### 4.1 配置架构（环境变量）

```bash
# .env 配置
AFTERSALES_MAX_DAYS=30              # 售后申请时限（天）
AFTERSALES_AUTO_APPROVE_AMOUNT=100  # 自动审批金额上限
AFTERSALES_FEE_RATE=0.05           # 手续费率
AFTERSALES_ALLOW_PARTIAL=true       # 是否允许部分退款
AFTERSALES_REQUIRE_REASON=true      # 是否必须填写退款原因
AFTERSALES_MIN_REFUND_AMOUNT=0.01  # 最小退款金额
```

### 4.2 规则引擎实现

```php
class RuleEngineService implements AftersalesRuleEngineInterface
{
    public function canCreateAftersales(OrderDataDTO $orderData): bool
    {
        // 检查订单状态
        $allowedStatuses = ['paid', 'shipped', 'received'];
        if (!in_array($orderData->orderStatus, $allowedStatuses)) {
            return false;
        }
        
        // 检查时间限制
        $maxDays = (int)($_ENV['AFTERSALES_MAX_DAYS'] ?? 30);
        $diffDays = (new \DateTime())->diff($orderData->orderCreatedAt)->days;
        if ($diffDays > $maxDays) {
            return false;
        }
        
        return true;
    }
    
    public function validateRefundItems(array $products, array $refundItems): array
    {
        $errors = [];
        $productMap = [];
        
        // 构建商品映射
        foreach ($products as $product) {
            $productMap[$product->productId] = $product;
        }
        
        // 验证退款项目
        foreach ($refundItems as $item) {
            if (!isset($productMap[$item['productId']])) {
                $errors[] = "商品不存在: {$item['productId']}";
                continue;
            }
            
            $product = $productMap[$item['productId']];
            if ($item['quantity'] > $product->orderQuantity) {
                $errors[] = "退款数量超过订单数量: {$item['productId']}";
            }
            
            if ($item['quantity'] <= 0) {
                $errors[] = "退款数量必须大于0: {$item['productId']}";
            }
        }
        
        return $errors;
    }
    
    public function calculateRefundAmount(array $aftersalesProducts): float
    {
        $amount = 0;
        foreach ($aftersalesProducts as $product) {
            $amount += $product->getPaidPrice() * $product->getRefundQuantity();
        }
        
        // 应用手续费
        $feeRate = (float)($_ENV['AFTERSALES_FEE_RATE'] ?? 0);
        if ($feeRate > 0) {
            $amount = $amount * (1 - $feeRate);
        }
        
        return round($amount, 2);
    }
}
```

## 5. 集成设计

### 5.1 Symfony Bundle 配置

```php
// config/services.php
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();
    
    $services->load('Tourze\\OrderRefundBundle\\', '../src/')
        ->exclude('../src/{Entity,DTO,Enum,Exception}');
    
    // 注册接口实现
    $services->alias(
        AftersalesRuleEngineInterface::class,
        RuleEngineService::class
    );
    
    $services->alias(
        AftersalesWorkflowInterface::class,
        WorkflowService::class
    );
};
```

### 5.2 外部集成示例

```php
// 在订单模块中集成售后功能
use Tourze\OrderRefundBundle\Service\AftersalesService;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;

class OrderService
{
    public function __construct(
        private readonly AftersalesService $aftersalesService
    ) {}
    
    public function createAftersales($order, array $refundItems): void
    {
        // 转换订单实体为DTO
        $orderData = new OrderDataDTO(
            orderNumber: $order->getOrderNo(),
            orderStatus: $order->getStatus(),
            orderCreatedAt: $order->getCreatedAt(),
            userId: $order->getUserId(),
            totalAmount: $order->getTotalAmount()
        );
        
        // 转换商品实体为DTO
        $products = [];
        foreach ($order->getOrderProducts() as $orderProduct) {
            $products[] = new ProductDataDTO(
                productId: $orderProduct->getProductId(),
                skuId: $orderProduct->getSkuId(),
                productName: $orderProduct->getProductName(),
                skuName: $orderProduct->getSkuName(),
                originalPrice: $orderProduct->getOriginalPrice(),
                paidPrice: $orderProduct->getPaidPrice(),
                discountAmount: $orderProduct->getDiscountAmount(),
                orderQuantity: $orderProduct->getQuantity()
            );
        }
        
        // 创建售后
        $this->aftersalesService->create(
            $orderData,
            $products,
            $refundItems,
            'refund_only',
            '用户申请退款'
        );
    }
}
```

## 6. 测试策略

### 6.1 单元测试

```php
namespace Tourze\OrderRefundBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Tourze\OrderRefundBundle\Service\AftersalesService;
use Tourze\OrderRefundBundle\DTO\OrderDataDTO;
use Tourze\OrderRefundBundle\DTO\ProductDataDTO;

class AftersalesServiceTest extends TestCase
{
    public function testCreateAftersalesWithValidData(): void
    {
        $orderData = new OrderDataDTO(
            orderNumber: 'TEST001',
            orderStatus: 'paid',
            orderCreatedAt: new \DateTime(),
            userId: 'user_001',
            totalAmount: 100.00
        );
        
        $products = [
            new ProductDataDTO(
                productId: 'prod_001',
                skuId: 'sku_001',
                productName: '测试商品',
                skuName: '默认规格',
                originalPrice: 100.00,
                paidPrice: 90.00,
                discountAmount: 10.00,
                orderQuantity: 1
            )
        ];
        
        $refundItems = [
            ['productId' => 'prod_001', 'quantity' => 1]
        ];
        
        $aftersales = $this->service->create(
            $orderData,
            $products,
            $refundItems,
            'refund_only',
            '测试原因'
        );
        
        $this->assertNotNull($aftersales->getId());
        $this->assertEquals('TEST001', $aftersales->getReferenceNumber());
        $this->assertEquals(90.00, $aftersales->getTotalRefundAmount());
    }
    
    public function testDataSnapshotIntegrity(): void
    {
        // 测试数据快照的完整性
        $aftersales = $this->createTestAftersales();
        
        // 验证订单快照
        $orderSnapshot = $aftersales->getAftersalesOrder();
        $this->assertNotNull($orderSnapshot);
        $this->assertEquals('TEST001', $orderSnapshot->getOrderNumber());
        $this->assertEquals('paid', $orderSnapshot->getOrderStatus());
        
        // 验证商品快照
        $productSnapshot = $aftersales->getAftersalesProducts()->first();
        $this->assertNotNull($productSnapshot);
        $this->assertEquals('prod_001', $productSnapshot->getProductId());
        $this->assertEquals(90.00, $productSnapshot->getPaidPrice());
    }
}
```

## 7. 设计质量检查

### 7.1 需求映射验证

| 需求编号 | 技术解决方案 | 验证 |
|---------|-------------|------|
| R-001 | AftersalesProduct 实体 + 快照存储 | ✅ |
| R-002 | 完整的价格字段设计 | ✅ |
| R-003 | referenceNumber 字段关联 | ✅ |
| R-004 | 完全移除 order-core-bundle 依赖 | ✅ |
| R-005 | 纯数据传递（DTO） | ✅ |
| R-006 | OrderDataDTO 标准接口 | ✅ |
| R-007 | ProductDataDTO 标准接口 | ✅ |
| R-015 | AftersalesOrder 实体 | ✅ |

### 7.2 架构合规检查

- ✅ **使用** 扁平化 Service 层
- ✅ **实体** 是贫血模型（只有 getter/setter）
- ✅ **不创建** Configuration 类（使用 $_ENV）
- ✅ **不主动创建** HTTP API 端点
- ✅ **不依赖** 任何外部实体
- ✅ **使用** 纯数据传递（DTO）
- ✅ **实现** 完整的数据快照

## 8. 关键设计决策总结

1. **完全独立**: 不依赖 `OrderCoreBundle\Entity\Contract`
2. **纯数据传递**: 使用 DTO 传递所有外部数据
3. **三层快照**: Aftersales + AftersalesProduct + AftersalesOrder
4. **灵活集成**: 支持 DTO 和数组两种创建方式
5. **扩展机制**: 接口抽象 + 事件系统
6. **配置管理**: 环境变量 $_ENV
7. **数据完整性**: 创建时快照，事后不可变

**order-refund-bundle** 的技术设计已完成。

关键设计决策：
- 架构模式：扁平化 Service 层 + 纯数据快照
- 公共API：2个DTO + 1个核心服务
- 扩展机制：接口抽象 + 事件系统
- 框架支持：Symfony Bundle 自动配置

准备使用 `/spec:tasks package/order-refund-bundle` 进行任务分解吗？