# 售后系统产品需求文档（PRD）

## 一、产品概述

### 1.1 产品定位
售后系统（order-refund-bundle）是电商平台订单系统的核心组成部分，负责处理用户购买商品后的退款、退货、换货等售后服务需求。通过规范化的售后流程和自动化的处理机制，提升用户售后体验，降低客服工作量，保障商家和用户的权益。

### 1.2 产品目标
- **用户体验优化**：提供便捷、透明的售后申请和处理流程
- **效率提升**：通过自动化审核和处理机制，提高售后处理效率
- **风险控制**：建立完善的售后规则体系，防止恶意售后行为
- **数据追溯**：完整记录售后全流程数据，便于问题追溯和服务改进

### 1.3 与现有系统的关系
- **依赖 order-core-bundle**：复用订单核心实体（Contract、OrderProduct、Aftersales等）
- **依赖 order-checkout-bundle**：复用价格计算服务，用于退款金额计算
- **扩展现有售后实体**：基于现有的 Aftersales 实体进行功能扩展和完善

## 二、业务流程设计

### 2.1 售后类型

#### 2.1.1 取消订单（CANCEL）
- **适用场景**：订单待支付状态
- **处理方式**：直接取消订单，释放库存

#### 2.1.2 仅退款（REFUND_ONLY）
- **适用场景**：
  - 待发货状态：商品未发出
  - 待收货状态：虚拟商品、服务类商品
- **处理方式**：仅退还货款，不涉及实物退回

#### 2.1.3 退货退款（RETURN_REFUND）
- **适用场景**：已收货状态，需要退回商品并退款
- **处理方式**：用户退回商品，商家确认收货后退款

#### 2.1.4 换货（EXCHANGE）
- **适用场景**：商品质量问题、发错货等需要更换商品
- **处理方式**：用户退回原商品，商家发出新商品

#### 2.1.5 补发（RESEND）
- **适用场景**：商品破损、缺件等需要补发
- **处理方式**：商家直接补发商品，无需用户退回

### 2.2 售后状态流转

```
初始状态
  │
  ├─> PENDING_APPROVAL (待审核)
  │     ├─> APPROVED (已同意)
  │     │     ├─> PENDING_RETURN (待买家退货)
  │     │     │     ├─> PENDING_RECEIVE (待商家收货)
  │     │     │     │     ├─> PENDING_REFUND (待退款)
  │     │     │     │     │     └─> COMPLETED (已完成)
  │     │     │     │     └─> REJECTED (已拒绝)
  │     │     │     └─> CANCELLED (已取消)
  │     │     └─> PENDING_REFUND (待退款)
  │     │           └─> COMPLETED (已完成)
  │     └─> REJECTED (已拒绝)
  │           └─> PENDING_MODIFICATION (待修改)
  │                 └─> PENDING_APPROVAL (重新申请)
  └─> CANCELLED (已取消)
```

### 2.3 核心业务规则

#### 2.3.1 申请时限规则
- **七天无理由退换货**：签收后7天内可申请（实际给予10天，考虑物流时效）
- **质量问题退换货**：确认收货后15天内可申请
- **自动确认收货**：发货后10天自动确认收货

#### 2.3.2 审核规则
- **自动审核通过条件**：
  - 支付后30分钟内申请退款
  - 未发货状态申请退款
  - 七天无理由退换货（符合条件的商品）
- **人工审核条件**：
  - 已发货状态申请退款
  - 质量问题申请
  - 涉及高价值商品（金额>1000元）

#### 2.3.3 超时处理规则

超时处理通过定时 Command 自动执行，运行命令：`php bin/console aftersales:process-timeout`

**超时规则**：
- **待审核超时**：72小时未处理，自动审核通过
- **待退货超时**：7天未填写物流单号，自动取消申请
- **待收货确认超时**：72小时未确认收货，自动确认并进入退款流程

**Command 特性**：
- 支持分批处理（`--batch-size`参数）
- 支持预览模式（`--dry-run`参数）
- 详细的处理日志和错误追踪
- 可配置为定时任务（cron）自动执行

**推荐定时配置**：
```bash
# 每小时执行一次
0 * * * * /path/to/php /path/to/bin/console aftersales:process-timeout
```

#### 2.3.4 限制规则
- **修改申请次数**：最多3次，超过需客服介入
- **售后申请次数**：同一商品最多3次售后申请
- **金额冻结**：售后期间冻结整个订单金额，商家不可结算

## 三、数据模型设计

### 3.1 核心实体扩展

#### 3.1.1 RefundOrder（退款单）
```php
class RefundOrder {
    private string $id;                    // 退款单号
    private Aftersales $aftersales;        // 关联售后单
    private RefundType $type;              // 退款类型
    private float $refundAmount;           // 退款金额
    private float $refundFreight;          // 退运费金额
    private float $refundPoints;           // 退还积分
    private array $refundCoupons;          // 退还优惠券
    private RefundStatus $status;          // 退款状态
    private string $refundChannel;         // 退款渠道
    private string $refundTradeNo;         // 第三方退款单号
    private DateTime $refundTime;          // 退款时间
    private string $refundReason;          // 退款原因
    private array $refundDetail;           // 退款明细
}
```

#### 3.1.2 ReturnOrder（退货单）
```php
class ReturnOrder {
    private string $id;                    // 退货单号
    private Aftersales $aftersales;        // 关联售后单
    private ReturnType $type;              // 退货类型（退货/换货）
    private array $returnItems;            // 退货商品明细
    private Address $returnAddress;        // 退货地址
    private string $expressCompany;        // 快递公司
    private string $expressNo;             // 快递单号
    private ReturnStatus $status;          // 退货状态
    private DateTime $sendTime;            // 寄出时间
    private DateTime $receiveTime;         // 收货时间
    private string $receiveNote;           // 收货备注
    private array $receiveImages;          // 收货凭证
}
```

#### 3.1.3 ExchangeOrder（换货单）
```php
class ExchangeOrder {
    private string $id;                    // 换货单号
    private Aftersales $aftersales;        // 关联售后单
    private array $oldItems;               // 原商品明细
    private array $newItems;               // 新商品明细
    private Address $receiveAddress;       // 收货地址
    private string $outExpressCompany;     // 发出快递公司
    private string $outExpressNo;          // 发出快递单号
    private ExchangeStatus $status;        // 换货状态
    private DateTime $outTime;             // 发出时间
    private string $exchangeReason;        // 换货原因
}
```

### 3.2 枚举类型扩展

#### 3.2.1 RefundStatus（退款状态）
```php
enum RefundStatus {
    case PENDING = 'pending';              // 待退款
    case PROCESSING = 'processing';        // 退款中
    case SUCCESS = 'success';              // 退款成功
    case FAILED = 'failed';                // 退款失败
    case PARTIAL = 'partial';              // 部分退款
}
```

#### 3.2.2 RefundReason（退款原因）
```php
enum RefundReason {
    case SEVEN_DAYS = 'seven_days';        // 七天无理由
    case QUALITY = 'quality';              // 质量问题
    case DESCRIPTION = 'description';      // 描述不符
    case WRONG_PRODUCT = 'wrong_product';  // 发错货
    case DAMAGED = 'damaged';              // 商品破损
    case MISSING = 'missing';              // 缺件漏发
    case FAKE = 'fake';                    // 假冒伪劣
    case OTHER = 'other';                  // 其他原因
}
```

#### 3.2.3 AftersalesStage（售后阶段）
```php
enum AftersalesStage {
    case APPLY = 'apply';                  // 申请阶段
    case AUDIT = 'audit';                  // 审核阶段
    case RETURN = 'return';                // 退货阶段
    case RECEIVE = 'receive';              // 收货阶段
    case REFUND = 'refund';                // 退款阶段
    case EXCHANGE = 'exchange';            // 换货阶段
    case COMPLETE = 'complete';            // 完成阶段
}
```

## 四、核心功能设计

### 4.1 售后申请功能

#### 4.1.1 申请入口
- 订单列表页：每个订单显示"申请售后"按钮
- 订单详情页：每个商品显示"申请售后"按钮
- 售后列表页：显示"再次申请"按钮（条件限制）

#### 4.1.2 申请流程
1. **选择售后商品**：支持多选，显示商品信息和购买数量
2. **选择售后类型**：根据订单状态显示可选类型
3. **填写售后信息**：
   - 售后原因（必选）
   - 问题描述（选填，最多500字）
   - 上传凭证（选填，最多9张图片）
   - 期望处理方式（退款/换货）
   - 退货地址（退货类型必填）
4. **提交申请**：生成售后单，进入审核流程

### 4.2 售后审核功能

#### 4.2.1 自动审核
```php
class AutoAuditService {
    public function shouldAutoApprove(Aftersales $aftersales): bool {
        // 未发货订单自动同意
        if ($order->getStatus() === OrderStatus::PENDING_SHIPMENT) {
            return true;
        }
        
        // 支付后30分钟内自动同意
        if ($this->isWithinMinutes($order->getPayTime(), 30)) {
            return true;
        }
        
        // 七天无理由且符合条件自动同意
        if ($this->isSevenDaysReturn($aftersales)) {
            return true;
        }
        
        return false;
    }
}
```

#### 4.2.2 人工审核
- **审核界面**：显示售后详情、订单信息、用户历史售后记录
- **审核操作**：
  - 同意：选择退货地址（退货类型）
  - 拒绝：填写拒绝原因
  - 协商：修改退款金额、联系用户

### 4.3 退货管理功能

#### 4.3.1 用户退货
- **填写物流信息**：选择快递公司、填写运单号
- **上传凭证**：快递单照片（可选）
- **查看退货地址**：显示商家退货地址和联系方式

#### 4.3.2 商家收货
- **确认收货**：检查商品完整性、配件齐全性
- **拒绝收货**：商品损坏、缺件、不符合退货条件
- **部分收货**：部分商品符合条件

### 4.4 退款处理功能

#### 4.4.1 退款金额计算
```php
class RefundCalculationService {
    public function calculate(Aftersales $aftersales): RefundAmount {
        $result = new RefundAmount();
        
        // 计算商品退款金额
        $productAmount = $this->calculateProductAmount($aftersales);
        $result->setProductAmount($productAmount);
        
        // 计算运费退款
        $freightAmount = $this->calculateFreightAmount($aftersales);
        $result->setFreightAmount($freightAmount);
        
        // 计算积分退还
        $points = $this->calculateRefundPoints($aftersales);
        $result->setPoints($points);
        
        // 扣除已使用优惠
        $deduction = $this->calculateDeduction($aftersales);
        $result->setDeduction($deduction);
        
        return $result;
    }
    
    private function calculateProductAmount(Aftersales $aftersales): float {
        // 按实付金额比例退款
        $items = $aftersales->getItems();
        $totalAmount = 0;
        
        foreach ($items as $item) {
            $unitPrice = $item->getActualPrice(); // 实付单价
            $quantity = $item->getRefundQuantity();
            $totalAmount += $unitPrice * $quantity;
        }
        
        return $totalAmount;
    }
}
```

#### 4.4.2 退款执行
- **原路退回**：优先原支付渠道退款
- **余额退款**：退至用户账户余额
- **银行卡退款**：需要用户提供银行卡信息

### 4.5 换货处理功能

#### 4.5.1 换货流程
1. 用户申请换货 → 商家审核
2. 商家同意 → 用户寄回原商品
3. 商家收货确认 → 仓库发出新商品
4. 用户确认收货 → 换货完成

#### 4.5.2 换货规则
- 只能换相同SKU的商品
- 不支持换货后再次换货
- 换货不涉及金额变动

### 4.6 客服介入功能

#### 4.6.1 介入场景
- 售后申请被拒绝3次
- 商家超时未处理
- 用户投诉升级
- 退货商品争议

#### 4.6.2 客服权限
- 修改退款金额
- 强制通过/拒绝售后
- 补偿优惠券/积分
- 协调双方达成一致

## 五、接口设计

### 5.1 用户端接口

```php
// 售后申请相关
POST   /api/aftersales/apply              // 提交售后申请
GET    /api/aftersales/reasons            // 获取售后原因列表
GET    /api/aftersales/check/{orderId}    // 检查订单售后状态
POST   /api/aftersales/cancel/{id}        // 取消售后申请
POST   /api/aftersales/modify/{id}        // 修改售后申请

// 退货相关
POST   /api/return/express/{id}           // 填写退货物流
GET    /api/return/address/{id}           // 获取退货地址
GET    /api/return/express-companies      // 获取快递公司列表

// 查询相关
GET    /api/aftersales/list               // 售后列表
GET    /api/aftersales/detail/{id}        // 售后详情
GET    /api/aftersales/progress/{id}      // 售后进度
```

### 5.2 商家端接口

```php
// 审核相关
POST   /api/merchant/aftersales/approve/{id}    // 同意售后
POST   /api/merchant/aftersales/reject/{id}     // 拒绝售后
POST   /api/merchant/aftersales/negotiate/{id}  // 协商处理

// 收货相关
POST   /api/merchant/return/receive/{id}        // 确认收货
POST   /api/merchant/return/reject/{id}         // 拒绝收货
POST   /api/merchant/return/partial/{id}        // 部分收货

// 退款相关
POST   /api/merchant/refund/confirm/{id}        // 确认退款
POST   /api/merchant/refund/modify/{id}         // 修改退款金额

// 换货相关
POST   /api/merchant/exchange/ship/{id}         // 换货发货
GET    /api/merchant/exchange/list              // 换货列表
```

### 5.3 系统内部接口

```php
// 自动处理
POST   /internal/aftersales/auto-audit          // 自动审核
POST   /internal/aftersales/timeout-handle      // 超时处理
POST   /internal/refund/execute                 // 执行退款

// 通知相关
POST   /internal/notify/aftersales-status       // 售后状态通知
POST   /internal/notify/refund-result           // 退款结果通知
```

## 六、异常处理

### 6.1 退款失败处理
- **原因**：余额不足、账户异常、渠道故障
- **处理**：
  1. 记录失败原因
  2. 通知财务人工处理
  3. 重试机制（最多3次）

### 6.2 库存异常处理
- **场景**：退货商品已下架、SKU已删除
- **处理**：
  1. 标记为特殊退货
  2. 不恢复库存
  3. 正常退款

### 6.3 重复申请处理
- **检测**：同一订单同一商品进行中的售后
- **处理**：提示用户等待当前售后完成

## 七、性能要求

### 7.1 响应时间
- 售后申请提交：< 1秒
- 售后列表查询：< 2秒
- 自动审核处理：< 3秒
- 退款执行：< 5秒

### 7.2 并发要求
- 支持 1000 QPS 售后申请
- 支持 5000 QPS 售后查询
- 支持 100 个并发退款处理

### 7.3 数据要求
- 售后数据保留 3 年
- 支持千万级售后单查询
- 退款对账准确率 100%

## 八、监控与统计

### 8.1 业务监控
- 售后申请量趋势
- 售后通过率
- 平均处理时长
- 超时处理率

### 8.2 异常监控
- 退款失败率
- 自动审核异常
- 接口调用失败
- 数据一致性检查

### 8.3 统计报表
- 售后原因分布
- 商品售后率排行
- 用户售后行为分析
- 客服处理效率统计

## 九、实施计划

### 第一阶段：基础功能（2周）
- 售后申请功能
- 基础审核流程
- 退款金额计算
- 售后状态管理

### 第二阶段：退货退款（2周）
- 退货流程
- 物流对接
- 退款执行
- 自动审核规则

### 第三阶段：换货补发（1周）
- 换货流程
- 补发功能
- 库存联动

### 第四阶段：优化完善（1周）
- 客服介入
- 超时处理
- 监控报表
- 性能优化

## 十、风险与对策

### 10.1 恶意售后
- **风险**：用户恶意申请售后骗取退款
- **对策**：
  - 建立用户信用体系
  - 设置售后次数限制
  - 高风险订单人工审核

### 10.2 资金风险
- **风险**：重复退款、超额退款
- **对策**：
  - 退款前校验
  - 退款后对账
  - 异常实时告警

### 10.3 体验风险
- **风险**：售后流程复杂、处理缓慢
- **对策**：
  - 简化申请流程
  - 扩大自动审核范围
  - 设置处理时限

## 附录：参考资料

1. 《电商法》售后相关条款
2. 淘宝、京东售后流程分析
3. 现有系统接口文档
4. 业务需求调研报告