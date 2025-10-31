# Aftersales 实体重构迁移指南

## 概述

本次重构将 `Aftersales` 实体从复杂的一对多关系简化为单商品售后模型，每个售后单只处理一个商品的一次售后申请。

## 数据库结构变更

### 新增字段到 `order_aftersales` 表

```sql
-- 添加商品相关字段到主表
ALTER TABLE order_aftersales ADD COLUMN order_product_id VARCHAR(255) NOT NULL COMMENT '订单商品ID';
ALTER TABLE order_aftersales ADD COLUMN product_id VARCHAR(100) NOT NULL COMMENT '商品ID';
ALTER TABLE order_aftersales ADD COLUMN sku_id VARCHAR(100) NOT NULL COMMENT 'SKU ID';
ALTER TABLE order_aftersales ADD COLUMN product_name VARCHAR(255) NOT NULL COMMENT '商品名称';
ALTER TABLE order_aftersales ADD COLUMN sku_name VARCHAR(255) NOT NULL COMMENT 'SKU名称';
ALTER TABLE order_aftersales ADD COLUMN quantity INT NOT NULL COMMENT '售后数量';
ALTER TABLE order_aftersales ADD COLUMN original_price DECIMAL(10,2) NOT NULL COMMENT '商品原价';
ALTER TABLE order_aftersales ADD COLUMN paid_price DECIMAL(10,2) NOT NULL COMMENT '商品实付价';
ALTER TABLE order_aftersales ADD COLUMN refund_amount DECIMAL(10,2) NOT NULL COMMENT '退款金额';
ALTER TABLE order_aftersales ADD COLUMN product_snapshot JSON NULL COMMENT '商品快照数据';

-- 创建索引
CREATE INDEX idx_aftersales_order_product ON order_aftersales (order_product_id);
CREATE INDEX idx_aftersales_product ON order_aftersales (product_id);
CREATE INDEX idx_aftersales_reference_product ON order_aftersales (reference_number, product_id);
```

### 数据迁移

```sql
-- 将 AftersalesItem 数据迁移到主表
UPDATE order_aftersales a
SET 
    order_product_id = (
        SELECT ai.order_product_id 
        FROM order_aftersales_item ai 
        WHERE ai.aftersales_id = a.id 
        LIMIT 1
    ),
    quantity = (
        SELECT ai.quantity 
        FROM order_aftersales_item ai 
        WHERE ai.aftersales_id = a.id 
        LIMIT 1
    ),
    original_price = (
        SELECT ai.original_price 
        FROM order_aftersales_item ai 
        WHERE ai.aftersales_id = a.id 
        LIMIT 1
    ),
    paid_price = (
        SELECT ai.actual_price 
        FROM order_aftersales_item ai 
        WHERE ai.aftersales_id = a.id 
        LIMIT 1
    ),
    refund_amount = (
        SELECT ai.refund_amount 
        FROM order_aftersales_item ai 
        WHERE ai.aftersales_id = a.id 
        LIMIT 1
    ),
    product_snapshot = (
        SELECT ai.product_snapshot 
        FROM order_aftersales_item ai 
        WHERE ai.aftersales_id = a.id 
        LIMIT 1
    );

-- 将 AftersalesProduct 数据迁移到主表（如果没有从 Item 获取到数据）
UPDATE order_aftersales a
SET 
    product_id = (
        SELECT ap.product_id 
        FROM order_aftersales_product ap 
        WHERE ap.aftersales_id = a.id 
        LIMIT 1
    ),
    sku_id = (
        SELECT ap.sku_id 
        FROM order_aftersales_product ap 
        WHERE ap.aftersales_id = a.id 
        LIMIT 1
    ),
    product_name = (
        SELECT ap.product_name 
        FROM order_aftersales_product ap 
        WHERE ap.aftersales_id = a.id 
        LIMIT 1
    ),
    sku_name = (
        SELECT ap.sku_name 
        FROM order_aftersales_product ap 
        WHERE ap.aftersales_id = a.id 
        LIMIT 1
    )
WHERE product_id IS NULL;
```

### 清理废弃表

```sql
-- 验证数据完整性后，删除废弃的关联表
-- DROP TABLE order_aftersales_item;
-- DROP TABLE order_aftersales_product;
```

## PHP 迁移脚本

```php
<?php

use Doctrine\DBAL\Connection;

class AftersalesMigration
{
    public function __construct(private Connection $connection)
    {
    }

    public function migrate(): void
    {
        $this->addColumns();
        $this->migrateData();
        $this->createIndexes();
        $this->validateData();
    }

    private function addColumns(): void
    {
        $this->connection->executeStatement('
            ALTER TABLE order_aftersales 
            ADD COLUMN order_product_id VARCHAR(255),
            ADD COLUMN product_id VARCHAR(100),
            ADD COLUMN sku_id VARCHAR(100),
            ADD COLUMN product_name VARCHAR(255),
            ADD COLUMN sku_name VARCHAR(255),
            ADD COLUMN quantity INT,
            ADD COLUMN original_price DECIMAL(10,2),
            ADD COLUMN paid_price DECIMAL(10,2),
            ADD COLUMN refund_amount DECIMAL(10,2),
            ADD COLUMN product_snapshot JSON
        ');
    }

    private function migrateData(): void
    {
        // 从 AftersalesItem 迁移数据
        $this->connection->executeStatement('
            UPDATE order_aftersales a
            JOIN order_aftersales_item ai ON ai.aftersales_id = a.id
            SET 
                a.order_product_id = ai.order_product_id,
                a.quantity = ai.quantity,
                a.original_price = ai.original_price,
                a.paid_price = ai.actual_price,
                a.refund_amount = ai.refund_amount,
                a.product_snapshot = ai.product_snapshot
        ');

        // 从 AftersalesProduct 补充产品信息
        $this->connection->executeStatement('
            UPDATE order_aftersales a
            JOIN order_aftersales_product ap ON ap.aftersales_id = a.id
            SET 
                a.product_id = ap.product_id,
                a.sku_id = ap.sku_id,
                a.product_name = ap.product_name,
                a.sku_name = ap.sku_name
            WHERE a.product_id IS NULL
        ');
    }

    private function createIndexes(): void
    {
        $this->connection->executeStatement('
            CREATE INDEX idx_aftersales_order_product ON order_aftersales (order_product_id)
        ');
        $this->connection->executeStatement('
            CREATE INDEX idx_aftersales_product ON order_aftersales (product_id)
        ');
    }

    private function validateData(): void
    {
        $count = $this->connection->fetchOne('
            SELECT COUNT(*) FROM order_aftersales 
            WHERE order_product_id IS NULL OR product_id IS NULL
        ');

        if ($count > 0) {
            throw new \Exception("数据迁移不完整，有 {$count} 条记录缺少必要字段");
        }
    }
}
```

## 破坏性变更

### 已移除的实体和方法

1. **实体关联**：
   - 移除 `Collection<AftersalesItem> $items`
   - 移除 `Collection<AftersalesProduct> $aftersalesProducts`
   - 移除 `Collection<RefundOrder> $refundOrders`
   - 移除 `Collection<ReturnOrder> $returnOrders`
   - 移除 `Collection<ExchangeOrder> $exchangeOrders`

2. **方法**：
   - 移除所有 Collection 管理方法（`addItem`, `removeItem` 等）
   - 修改 `getTotalRefundAmount()` 实现

### API 变更

如果有外部 API 依赖这些字段，需要相应更新：

```php
// 旧方式
$aftersales->getItems()->first()->getOrderProductId();

// 新方式  
$aftersales->getOrderProductId();
```

## 回滚方案

如果需要回滚，可以：

1. 恢复原始的 `Aftersales` 实体结构
2. 重新创建 `AftersalesItem` 和 `AftersalesProduct` 表
3. 将主表数据迁移回关联表

## 验证清单

- [ ] 数据库结构变更完成
- [ ] 数据迁移完成且无数据丢失
- [ ] 所有单元测试通过
- [ ] 集成测试验证业务逻辑正常
- [ ] API 接口测试通过
- [ ] 性能测试验证查询优化效果