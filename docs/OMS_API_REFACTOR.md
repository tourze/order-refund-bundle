# OMS售后接口重构说明

## 概述

将原本的 `SyncAftersalesFromOms` 综合接口拆分为三个职责单一的专用接口，提高接口的可维护性和可读性。

## 接口拆分方案

### 1. 创建售后单接口 - `CreateAftersalesFromOms`

**接口名称**: `CreateAftersalesFromOms`  
**调用方法**: `CreateAftersalesFromOms`

**职责**: 专门处理从OMS创建新售后单的业务逻辑

**主要参数**:
- `aftersalesNo`: 售后单号
- `aftersalesType`: 售后类型 (refund/return/exchange)
- `orderNo`: 关联订单号
- `reason`: 申请原因
- `refundAmount`: 申请金额
- `applicantName`: 申请人姓名
- `applicantPhone`: 申请人电话
- `products`: 售后商品列表

**特点**:
- 检查售后单是否已存在，如存在则抛出异常
- 严格验证输入参数
- 记录创建日志

### 2. 更新售后状态接口 - `UpdateAftersalesStatusFromOms`

**接口名称**: `UpdateAftersalesStatusFromOms`  
**调用方法**: `UpdateAftersalesStatusFromOms`

**职责**: 专门处理售后单状态变更

**主要参数**:
- `aftersalesNo`: 售后单号
- `status`: 新的售后状态
- `auditor`: 审核人
- `auditTime`: 审核时间
- `auditRemark`: 审核备注
- `approvedAmount`: 批准金额
- `returnLogistics`: 退货物流信息

**特点**:
- 记录状态变更历史
- 支持审核信息更新
- 处理物流信息
- 返回变更前后状态

### 3. 修改售后信息接口 - `UpdateAftersalesInfoFromOms`

**接口名称**: `UpdateAftersalesInfoFromOms`  
**调用方法**: `UpdateAftersalesInfoFromOms`

**职责**: 专门处理售后单信息修改

**主要参数**:
- `aftersalesNo`: 售后单号
- `modifyReason`: 修改原因
- `description`: 问题描述
- `proofImages`: 凭证图片
- `refundAmount`: 申请金额
- `products`: 售后商品列表
- `serviceNote`: 客服备注

**特点**:
- 支持部分字段更新
- 记录修改历史和原因
- 增加修改次数统计
- 返回修改的字段列表

## 服务层改进

### `OmsAftersalesSyncService` 新增方法

```php
// 专门创建售后单
public function createFromOms(array $data): Aftersales

// 专门更新状态  
public function updateStatusFromOms(array $data): array

// 专门修改信息
public function updateInfoFromOms(array $data): Aftersales
```

### 私有方法补充

- `updateExistingAftersalesStatus()`: 处理状态更新逻辑
- `updateExistingAftersalesInfo()`: 处理信息修改逻辑  
- `createModificationLog()`: 创建修改日志
- `updateAftersalesProducts()`: 更新商品信息

## 优势对比

### 重构前
- 单个接口承担多种职责
- 参数冗余，部分场景不需要所有参数
- 逻辑复杂，难以维护
- 错误处理不够精确

### 重构后
- 职责单一，接口语义清晰
- 参数精简，按需传递
- 逻辑分离，易于维护和测试
- 错误处理更加精确
- 支持更细粒度的权限控制

## 向后兼容

原 `SyncAftersalesFromOms` 接口保留，内部实现仍然可用，确保现有系统不受影响。

## 使用建议

**新开发**：推荐使用拆分后的专用接口
- 创建售后: 使用 `CreateAftersalesFromOms`
- 状态变更: 使用 `UpdateAftersalesStatusFromOms`  
- 信息修改: 使用 `UpdateAftersalesInfoFromOms`

**现有系统**：可继续使用 `SyncAftersalesFromOms`，建议逐步迁移到新接口