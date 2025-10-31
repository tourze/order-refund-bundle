# 售后系统定时任务配置指南

## 概述

order-refund-bundle 提供了自动处理超时售后申请的 Command，需要通过 cron 定时任务来定期执行，确保售后流程的自动化处理。

## 主要 Command

### aftersales:process-timeout

**功能**：处理超时的售后申请

**完整命令**：
```bash
php bin/console aftersales:process-timeout
```

**参数选项**：
- `--batch-size|-b`：每批处理的数量（默认：100）
- `--dry-run|-d`：预览模式，不执行实际操作

**使用示例**：
```bash
# 正常执行
php bin/console aftersales:process-timeout

# 分批处理，每次50个
php bin/console aftersales:process-timeout --batch-size=50

# 预览模式，查看会处理哪些数据
php bin/console aftersales:process-timeout --dry-run
```

## Cron 配置

### 基础配置

**编辑 crontab**：
```bash
crontab -e
```

**添加定时任务**：
```bash
# 每小时执行一次超时处理
0 * * * * cd /path/to/your/project && php bin/console aftersales:process-timeout >> /var/log/aftersales-timeout.log 2>&1

# 每天凌晨2点执行一次，处理大批量数据
0 2 * * * cd /path/to/your/project && php bin/console aftersales:process-timeout --batch-size=500 >> /var/log/aftersales-timeout-daily.log 2>&1
```

### 高可用配置

**使用 flock 防止重复执行**：
```bash
# 防止并发执行的安全配置
0 * * * * /usr/bin/flock -n /tmp/aftersales-timeout.lock -c "cd /path/to/your/project && php bin/console aftersales:process-timeout" >> /var/log/aftersales-timeout.log 2>&1
```

**超时控制**：
```bash
# 设置命令超时时间为5分钟
0 * * * * cd /path/to/your/project && timeout 300 php bin/console aftersales:process-timeout >> /var/log/aftersales-timeout.log 2>&1
```

## 日志管理

### 日志配置

**Symfony monolog 配置** (`config/packages/monolog.yaml`)：
```yaml
monolog:
    channels: ['aftersales']
    handlers:
        aftersales_file:
            type: rotating_file
            path: '%kernel.logs_dir%/aftersales.log'
            level: info
            channels: ['aftersales']
            max_files: 30
```

**PHP-FPM/Nginx 日志轮转**：
```bash
# /etc/logrotate.d/aftersales
/var/log/aftersales-*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 0640 www-data www-data
    postrotate
        # 可选：重启相关服务
    endscript
}
```

### 日志监控

**监控超时处理失败**：
```bash
# 检查错误日志
grep -i "error\|exception\|failed" /var/log/aftersales-timeout.log

# 统计处理量
grep -c "处理完成" /var/log/aftersales-timeout.log
```

## 性能优化建议

### 1. 批次大小调优

```bash
# 小批次（推荐用于频繁执行）
--batch-size=50

# 大批次（推荐用于非高峰期）
--batch-size=500
```

### 2. 执行频率优化

**高频场景**：
```bash
# 每15分钟执行一次
*/15 * * * * cd /path/to/your/project && php bin/console aftersales:process-timeout --batch-size=100
```

**低频场景**：
```bash
# 每3小时执行一次
0 */3 * * * cd /path/to/your/project && php bin/console aftersales:process-timeout --batch-size=300
```

### 3. 数据库优化

**MySQL 配置优化**：
- 确保 `auto_process_time` 字段有索引
- 定期分析表统计信息：`ANALYZE TABLE order_aftersales;`

**查询优化**：
- Command 使用分页查询避免内存溢出
- 支持批量处理减少数据库连接开销

## 监控与告警

### 1. 进程监控

**使用 Supervisor 管理**：
```ini
[program:aftersales-timeout]
command=php bin/console aftersales:process-timeout
directory=/path/to/your/project
autostart=false
autorestart=false
user=www-data
stdout_logfile=/var/log/aftersales-timeout.log
stderr_logfile=/var/log/aftersales-timeout.error.log
```

### 2. 性能监控

**关键指标**：
- 处理成功率
- 平均处理时间
- 错误率
- 处理数量

**监控脚本示例**：
```bash
#!/bin/bash
# check-aftersales-timeout.sh

LOG_FILE="/var/log/aftersales-timeout.log"
ERROR_COUNT=$(grep -c "ERROR\|CRITICAL" "$LOG_FILE" | tail -100)

if [ "$ERROR_COUNT" -gt 10 ]; then
    echo "CRITICAL: 超时处理错误过多 ($ERROR_COUNT)"
    exit 2
elif [ "$ERROR_COUNT" -gt 5 ]; then
    echo "WARNING: 超时处理存在错误 ($ERROR_COUNT)"
    exit 1
else
    echo "OK: 超时处理正常"
    exit 0
fi
```

### 3. 业务监控

**数据库查询监控**：
```sql
-- 检查长期待处理的申请
SELECT COUNT(*) as timeout_count
FROM order_aftersales 
WHERE auto_process_time IS NOT NULL 
  AND auto_process_time <= NOW() 
  AND state IN ('PENDING_APPROVAL', 'PENDING_RETURN', 'PENDING_RECEIVE');

-- 检查处理成功率
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total,
    SUM(CASE WHEN state NOT IN ('PENDING_APPROVAL', 'PENDING_RETURN', 'PENDING_RECEIVE') THEN 1 ELSE 0 END) as processed
FROM order_aftersales 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

## 故障排查

### 1. 常见问题

**Command 不执行**：
- 检查 cron 服务状态：`systemctl status cron`
- 检查 crontab 语法：`crontab -l`
- 检查文件路径和权限

**处理失败**：
- 查看详细日志：`tail -f /var/log/aftersales-timeout.log`
- 使用预览模式调试：`--dry-run`
- 检查数据库连接

**性能问题**：
- 减小批次大小
- 增加执行频率
- 检查数据库索引

### 2. 调试工具

**手动执行测试**：
```bash
# 手动执行并查看详细输出
php bin/console aftersales:process-timeout -v

# 预览模式查看待处理数据
php bin/console aftersales:process-timeout --dry-run -v
```

**数据库直接查询**：
```sql
-- 查看超时的售后申请
SELECT id, state, auto_process_time, created_at
FROM order_aftersales 
WHERE auto_process_time <= NOW()
  AND state IN ('PENDING_APPROVAL', 'PENDING_RETURN', 'PENDING_RECEIVE')
LIMIT 10;
```

## 安全考虑

### 1. 权限控制

- 确保 cron 任务以适当用户身份运行
- 限制日志文件访问权限
- 定期清理旧日志文件

### 2. 错误处理

- Command 内置异常处理机制
- 失败任务不会影响其他任务
- 详细的错误日志记录

### 3. 数据完整性

- 使用数据库事务确保数据一致性
- 支持分批处理避免长时间锁定
- 处理过程中的状态变更记录

## 扩展性考虑

### 1. 分布式部署

- 使用分布式锁避免重复处理
- 支持多实例并行处理
- 负载均衡考虑

### 2. 消息队列集成

- 可选择使用 Symfony Messenger
- 异步处理提升性能
- 重试机制处理失败任务

---

> **重要提示**：在生产环境部署前，请务必在测试环境充分验证定时任务的执行效果和性能表现。