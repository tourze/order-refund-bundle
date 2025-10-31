#!/bin/bash
# 快速测试脚本 - 运行非集成测试（<2分钟）

echo "======================================"
echo "order-refund-bundle 快速测试"
echo "======================================"
echo ""
echo "运行范围: Entity, Service, DTO, Enum, Exception, Event, DependencyInjection"
echo "预计时间: < 2 分钟"
echo "测试数量: ~1157 个"
echo ""

vendor/bin/phpunit \
  packages/order-refund-bundle/tests/Entity \
  packages/order-refund-bundle/tests/Service \
  packages/order-refund-bundle/tests/DTO \
  packages/order-refund-bundle/tests/Enum \
  packages/order-refund-bundle/tests/Exception \
  packages/order-refund-bundle/tests/Event \
  packages/order-refund-bundle/tests/DependencyInjection \
  --no-coverage

echo ""
echo "======================================"
echo "快速测试完成"
echo "======================================"
