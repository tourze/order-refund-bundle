# OMS 售后信息同步 API 文档

## 接口概述

该接口用于外部 OMS 系统向本地系统同步售后申请信息，支持退款、退货退款、换货等多种售后类型。

## 接口信息

- **方法名**: `SyncAftersalesFromOms`
- **协议**: JSON-RPC 2.0
- **IP 限制**: 只允许配置的白名单 IP 地址访问

## 请求格式

### JSON-RPC 请求示例

```json
{
    "jsonrpc": "2.0",
    "method": "SyncAftersalesFromOms",
    "params": {
        "aftersalesNo": "AS-20240101-001",
        "aftersalesType": "return",
        "orderNo": "ORDER-20240101-001",
        "reason": "质量问题",
        "description": "商品存在质量缺陷，无法正常使用",
        "proofImages": [
            "https://oss.example.com/proof1.jpg",
            "https://oss.example.com/proof2.jpg"
        ],
        "status": "pending",
        "refundAmount": 10000,
        "applicantName": "张三",
        "applicantPhone": "13800138000",
        "applyTime": "2024-01-01 10:00:00",
        "auditor": "客服小王",
        "auditTime": "2024-01-01 11:00:00",
        "auditRemark": "审核通过，请寄回商品",
        "products": [
            {
                "productCode": "SKU001",
                "productName": "iPhone 15 Pro Max",
                "quantity": 1,
                "amount": 10000,
                "reason": "屏幕有划痕"
            }
        ],
        "returnLogistics": {
            "company": "顺丰快递",
            "trackingNumber": "SF1234567890",
            "returnTime": "2024-01-02 10:00:00"
        },
        "exchangeAddress": {
            "name": "张三",
            "phone": "13800138000",
            "province": "上海市",
            "city": "上海市",
            "district": "浦东新区",
            "address": "陆家嘴环路1000号",
            "zipCode": "200120"
        }
    },
    "id": 1
}
```

## 参数说明

### 必填参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| aftersalesNo | string | 售后单号，系统内唯一 |
| aftersalesType | string | 售后类型: refund(仅退款), return(退货退款), exchange(换货) |
| orderNo | string | 关联订单号 |
| reason | string | 申请原因 |
| status | string | 售后状态 |
| refundAmount | integer | 申请金额(分) |
| applicantName | string | 申请人姓名 |
| applicantPhone | string | 申请人电话 |
| applyTime | string | 申请时间，格式: YYYY-MM-DD HH:mm:ss |
| products | array | 售后商品列表 |

### 可选参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| description | string | 问题描述 |
| proofImages | array | 凭证图片URL列表，最多9张 |
| auditor | string | 审核人 |
| auditTime | string | 审核时间 |
| auditRemark | string | 审核备注 |
| returnLogistics | object | 退货物流信息(退货类型使用) |
| exchangeAddress | object | 换货收货地址(换货类型必填) |

### products 数组项结构

| 字段名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| productCode | string | 是 | 商品编码 |
| productName | string | 是 | 商品名称 |
| quantity | integer | 是 | 数量，必须大于0 |
| amount | integer | 是 | 金额(分)，不能为负数 |
| reason | string | 否 | 商品退货原因 |

### returnLogistics 对象结构

| 字段名 | 类型 | 说明 |
|--------|------|------|
| company | string | 快递公司 |
| trackingNumber | string | 快递单号 |
| returnTime | string | 退货发货时间 |

### exchangeAddress 对象结构

| 字段名 | 类型 | 说明 |
|--------|------|------|
| name | string | 收货人姓名 |
| phone | string | 收货人电话 |
| province | string | 省份 |
| city | string | 城市 |
| district | string | 区县 |
| address | string | 详细地址 |
| zipCode | string | 邮编 |

## 售后状态映射

| OMS状态 | 系统状态 | 说明 |
|---------|---------|------|
| pending, submitted | PENDING_APPROVAL | 待审核 |
| approved, processing | APPROVED | 已批准 |
| rejected, refused | REJECTED | 已拒绝 |
| completed, finished | COMPLETED | 已完成 |
| cancelled, closed | CANCELLED | 已取消 |

## 响应格式

### 成功响应

```json
{
    "jsonrpc": "2.0",
    "result": {
        "success": true,
        "message": "售后信息同步成功",
        "aftersalesId": "1234567890"
    },
    "id": 1
}
```

### 错误响应

```json
{
    "jsonrpc": "2.0",
    "error": {
        "code": -32603,
        "message": "无效的售后类型: invalid_type"
    },
    "id": 1
}
```

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| -32603 | 内部错误，具体信息见 message |
| -32602 | 参数无效 |
| -32001 | IP 地址不在白名单中，访问被拒绝 |

## 安全限制

### IP 白名单验证

本接口启用了严格的 IP 白名单验证机制：

- ✅ **允许访问**: 只有在系统配置中添加到白名单的 IP 地址才能调用此接口
- ❌ **拒绝访问**: 未在白名单中的 IP 地址将收到 `-32001` 错误响应
- 🔒 **安全保障**: 防止未授权的外部系统访问敏感的售后同步接口

#### IP 白名单配置

请联系系统管理员将您的服务器 IP 地址添加到白名单中。需要提供：
- 服务器公网 IP 地址
- 申请原因和用途说明
- 负责人联系方式

#### IP 被拒绝的错误响应示例

```json
{
    "jsonrpc": "2.0",
    "error": {
        "code": -32001,
        "message": "IP 地址 192.168.1.100 不在白名单中，访问被拒绝"
    },
    "id": 1
}
```

## 业务规则

1. **售后单号唯一性**: 同一个售后单号可以多次同步，系统会根据状态进行更新
2. **售后类型验证**: 只接受 refund、return、exchange 三种类型
3. **商品数量验证**: 所有商品数量必须大于0
4. **金额验证**: 申请金额和商品金额不能为负数
5. **换货地址**: 换货类型必须提供收货地址
6. **状态流转**: 系统会记录所有状态变更历史
7. **用户关联**: 系统会尝试通过手机号关联已注册用户
8. **IP 访问限制**: 必须从白名单 IP 地址发起请求

## 调用示例

### PHP 示例

```php
<?php

$client = new \JsonRpc\Client('https://your-api-endpoint.com/json-rpc');

// 退款申请示例
$refundParams = [
    'aftersalesNo' => 'AS-' . date('Ymd') . '-' . uniqid(),
    'aftersalesType' => 'refund',
    'orderNo' => 'ORDER-001',
    'reason' => '质量问题',
    'description' => '商品存在质量缺陷',
    'proofImages' => [
        'https://oss.example.com/proof1.jpg',
    ],
    'status' => 'pending',
    'refundAmount' => 10000,
    'applicantName' => '张三',
    'applicantPhone' => '13800138000',
    'applyTime' => date('Y-m-d H:i:s'),
    'products' => [
        [
            'productCode' => 'SKU001',
            'productName' => '测试商品',
            'quantity' => 1,
            'amount' => 10000,
        ]
    ]
];

try {
    $result = $client->call('SyncAftersalesFromOms', $refundParams);
    echo "同步成功，售后单ID: " . $result['aftersalesId'] . PHP_EOL;
} catch (\Exception $e) {
    echo "同步失败: " . $e->getMessage() . PHP_EOL;
}

// 换货申请示例
$exchangeParams = [
    'aftersalesNo' => 'AS-EX-' . date('Ymd') . '-' . uniqid(),
    'aftersalesType' => 'exchange',
    'orderNo' => 'ORDER-002',
    'reason' => '发错货',
    'status' => 'approved',
    'refundAmount' => 0,
    'applicantName' => '李四',
    'applicantPhone' => '13900139000',
    'applyTime' => date('Y-m-d H:i:s'),
    'products' => [
        [
            'productCode' => 'SKU002',
            'productName' => '换货商品',
            'quantity' => 1,
            'amount' => 0,
        ]
    ],
    'exchangeAddress' => [
        'name' => '李四',
        'phone' => '13900139000',
        'province' => '上海市',
        'city' => '上海市',
        'district' => '浦东新区',
        'address' => '测试地址123号',
        'zipCode' => '200120',
    ]
];

$result = $client->call('SyncAftersalesFromOms', $exchangeParams);
```

### CURL 示例

```bash
curl -X POST https://your-api-endpoint.com/json-rpc \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "jsonrpc": "2.0",
    "method": "SyncAftersalesFromOms",
    "params": {
        "aftersalesNo": "AS-20240101-001",
        "aftersalesType": "return",
        "orderNo": "ORDER-001",
        "reason": "质量问题",
        "status": "pending",
        "refundAmount": 10000,
        "applicantName": "张三",
        "applicantPhone": "13800138000",
        "applyTime": "2024-01-01 10:00:00",
        "products": [
            {
                "productCode": "SKU001",
                "productName": "商品名称",
                "quantity": 1,
                "amount": 10000
            }
        ]
    },
    "id": 1
}'
```

## 注意事项

### 安全相关
1. **IP 白名单**: 确保服务器 IP 地址已添加到白名单，否则无法访问接口
2. **HTTPS 协议**: 建议使用 HTTPS 协议确保数据传输安全
3. **定期检查**: 定期检查和更新 IP 白名单配置

### 数据处理
4. **数据一致性**: 建议使用事务确保数据完整性
5. **状态同步**: 定期同步售后状态变更，保持两个系统数据一致
6. **图片处理**: 凭证图片URL应该是可访问的公网地址
7. **时间格式**: 所有时间字段使用 Asia/Shanghai 时区
8. **金额单位**: 所有金额字段单位为分(人民币)

### 开发相关
9. **重试机制**: 建议实现重试机制处理网络异常
10. **日志记录**: 所有同步操作都会记录在售后日志中
11. **批量同步**: 如需批量同步，建议分批处理，每批不超过100条
12. **测试环境**: 在开发和测试环境中，确保测试服务器 IP 也在白名单中

### 故障排除
- 如果收到 `-32001` 错误，请检查请求来源 IP 是否在白名单中
- 联系系统管理员确认当前 IP 白名单配置
- 如有 IP 地址变更，需要及时更新白名单配置
- 售后类型必须严格按照文档要求传入，区分大小写