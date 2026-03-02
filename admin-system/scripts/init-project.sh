#!/bin/bash
# 项目初始化脚本
# 用于首次安装项目时自动配置环境

set -e

echo "=== 项目初始化脚本 ==="

# 进入后端目录
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKEND_DIR="$(dirname "$SCRIPT_DIR")"

cd "$BACKEND_DIR"

# 1. 检查并创建 .env 文件
if [ ! -f .env ]; then
    echo ">>> 创建 .env 文件..."
    cp .env.example .env
    echo "    已从 .env.example 复制"
fi

# 2. 生成 APP_KEY
if ! grep -q "APP_KEY=base64:" .env || grep -q "APP_KEY=$" .env; then
    echo ">>> 生成 APP_KEY..."
    php artisan key:generate
fi

# 3. 生成 JWT_SECRET
if ! grep -q "JWT_SECRET=.\+" .env || grep -q "JWT_SECRET=$" .env; then
    echo ">>> 生成 JWT_SECRET..."
    php artisan jwt:secret --force
fi

# 4. 创建 SQLite 数据库文件（如果使用 SQLite）
if grep -q "DB_CONNECTION=sqlite" .env; then
    echo ">>> 创建 SQLite 数据库文件..."
    touch database/database.sqlite
fi

# 5. 执行数据库迁移
echo ">>> 执行数据库迁移..."
php artisan migrate --force

# 6. 填充初始数据
echo ">>> 填充初始数据..."
php artisan db:seed --force

# 7. 清除缓存
echo ">>> 清除缓存..."
php artisan cache:clear
php artisan config:clear

# 8. 前端环境配置
FRONTEND_DIR="$BACKEND_DIR/frontend"
if [ -d "$FRONTEND_DIR" ]; then
    cd "$FRONTEND_DIR"
    if [ ! -f .env ]; then
        echo ">>> 创建前端 .env 文件..."
        cp .env.example .env
    fi
fi

echo ""
echo "=== 初始化完成 ==="
echo ""
echo "后端服务启动命令:"
echo "  cd admin-system && php artisan serve --host=0.0.0.0 --port=8000"
echo ""
echo "前端服务启动命令:"
echo "  cd admin-system/frontend && npm run dev"
echo ""
echo "默认管理员账号:"
echo "  用户名: admin"
echo "  密码: Admin@123456"
