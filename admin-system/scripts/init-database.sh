#!/bin/bash
# 数据库初始化脚本
# 用于重置数据库（会清空所有数据）

set -e

echo "=== 数据库初始化脚本 ==="

# 进入项目目录
cd "$(dirname "$0")/.."

# 检查环境配置
if [ ! -f .env ]; then
    echo ">>> .env 文件不存在，从 .env.example 复制..."
    cp .env.example .env
    
    # 生成必要的密钥
    echo ">>> 生成 APP_KEY..."
    php artisan key:generate
    
    echo ">>> 生成 JWT_SECRET..."
    php artisan jwt:secret --force
fi

# 确认执行
read -p "此操作将清空并重建数据库，是否继续? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "操作已取消"
    exit 0
fi

# 创建 SQLite 数据库文件(如果使用 SQLite)
if grep -q "DB_CONNECTION=sqlite" .env; then
    echo ">>> 创建 SQLite 数据库文件..."
    touch database/database.sqlite
fi

# 执行迁移
echo ">>> 执行数据库迁移..."
php artisan migrate:fresh --force

# 填充初始数据
echo ">>> 填充初始数据..."
php artisan db:seed --force

# 清除缓存
echo ">>> 清除缓存..."
php artisan cache:clear
php artisan config:clear

echo "=== 初始化完成 ==="
echo ""
echo "默认管理员账号:"
echo "  用户名: admin"
echo "  密码: Admin@123456"
