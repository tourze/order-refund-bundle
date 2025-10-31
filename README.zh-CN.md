# Order Refund Bundle

[English](README.md) | [中文](README.zh-CN.md)

订单售后退款模块，提供完整的售后流程管理。

## 功能特性

- **多种售后类型**：取消订单、仅退款、退货退款、换货、重新发货
- **状态流转管理**：完整的售后生命周期状态跟踪
- **自动审核**：支持符合条件的售后申请自动审核
- **退款计算**：智能计算退款金额，支持部分退款
- **JsonRpc 接口**：提供标准化的 API 接口

## 核心实体

### Aftersales
售后申请主实体，包含：
- 售后类型、状态、阶段
- 申请原因、金额计算
- 时间限制和修改次数控制

### AftersalesItem
售后商品明细，支持：
- 单品退款金额计算
- 退积分处理
- 处理状态跟踪

## 状态流转

```
待审核 → 审核通过 → [退货中 → 已收货] → 退款中 → 已完成
      ↓
   审核拒绝 ← 可修改申请（最多3次）
```

## JsonRpc 接口

### 申请售后
```php
// aftersales.apply
$response = $this->client->call('aftersales.apply', [
    'contractId' => $contractId,
    'type' => 'REFUND_ONLY',
    'reason' => 'SEVEN_DAYS',
    'items' => [
        ['orderProductId' => 1, 'quantity' => 1]
    ],
    'description' => '申请理由说明'
]);
```

### 获取售后列表
```php
// aftersales.list
$response = $this->client->call('aftersales.list', [
    'page' => 1,
    'size' => 20,
    'filters' => ['state' => 'PENDING']
]);
```

## 业务规则

### 申请时限
- 七天无理由退换：确认收货后10天内
- 质量问题：确认收货后15天内
- 商品破损：确认收货后7天内

### 自动审核条件
- 七天无理由退换且金额 ≤ 200元
- 商家责任问题
- 用户信用等级良好

## 命令行工具

### 超时处理命令

自动处理超时的售后申请：

```bash
# 基本使用
php bin/console aftersales:process-timeout

# 预览模式（不执行实际操作）
php bin/console aftersales:process-timeout --dry-run

# 指定批次大小
php bin/console aftersales:process-timeout --batch-size=200
```

### 初始化快递公司命令

初始化系统默认的快递公司基础数据：

```bash
# 基本使用
php bin/console order-refund:init-express-companies

# 强制重新初始化（覆盖现有数据）
php bin/console order-refund:init-express-companies --force

# 将初始化的快递公司设为非活跃状态
php bin/console order-refund:init-express-companies --inactive
```

**命令功能**：
- 自动创建15个主要快递公司的基础数据，包括顺丰、申通、韵达、中通、圆通等
- 包含快递公司代码、名称、物流查询URL模板等信息
- 支持强制更新现有数据或跳过已存在的记录
- 可选择将新增快递公司设为非活跃状态

### 初始化寄回地址命令

初始化系统默认的寄回地址基础数据：

```bash
# 基本使用
php bin/console order-refund:init-return-addresses

# 强制重新初始化
php bin/console order-refund:init-return-addresses --force

# 将初始化的地址设为非活跃状态
php bin/console order-refund:init-return-addresses --inactive

# 不设置默认地址
php bin/console order-refund:init-return-addresses --no-default
```

**命令功能**：
- 创建3个覆盖华南、华北、华东区域的默认寄回地址
- 包含联系人、电话、详细地址、营业时间、特殊说明等完整信息
- 支持设置默认地址和活跃状态控制
- 避免重复创建已存在的地址记录

### 定时任务配置

建议配置 cron 定时执行：

```bash
# 每小时执行一次
0 * * * * cd /path/to/project && php bin/console aftersales:process-timeout >> /var/log/aftersales.log 2>&1
```

详细配置说明请参考：[定时任务配置指南](docs/cron-setup.md)

## 安装配置

1. 添加到 Symfony bundles：
```php
// config/bundles.php
return [
    // ...
    Tourze\OrderRefundBundle\OrderRefundBundle::class => ['all' => true],
];
```

2. 运行数据库迁移：
```bash
php bin/console doctrine:migrations:migrate
```

## 依赖包

- `tourze/order-core-bundle` - 订单核心模块
- `tourze/order-checkout-bundle` - 订单结算模块
- `tourze/common-bundle` - 通用基础组件