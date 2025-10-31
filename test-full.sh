#!/bin/bash
# 完整测试脚本 - 包含集成测试（20-30分钟）

echo "======================================"
echo "order-refund-bundle 完整测试"
echo "======================================"
echo ""
echo "警告: 此测试包含集成测试，运行时间较长"
echo "预计时间: 20-30 分钟"
echo "测试数量: ~2322 个"
echo ""
echo "建议: 日常开发使用 ./test-quick.sh"
echo "      提交前或CI使用此脚本"
echo ""

read -p "是否继续? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    echo "测试已取消"
    exit 1
fi

vendor/bin/phpunit packages/order-refund-bundle --no-coverage

echo ""
echo "======================================"
echo "完整测试完成"
echo "======================================"
