#!/bin/bash
# 启动开发服务脚本

cd "$(dirname "$0")/.."

echo "=== 启动开发服务 ==="

# 杀掉已有进程
pkill -f "php artisan serve" 2>/dev/null
pkill -f "vite" 2>/dev/null
sleep 1

# 启动后端 (使用 setsid 创建新会话，避免进程被终止)
echo ">>> 启动后端服务 (端口 8000)..."
setsid php artisan serve --host=0.0.0.0 --port=8000 </dev/null >/dev/null 2>&1 &

# 启动前端 (需要重定向 stdin 到 /dev/null)
echo ">>> 启动前端服务 (端口 3000)..."
cd frontend && setsid npx vite --host 0.0.0.0 --port 3000 </dev/null >/dev/null 2>&1 &

sleep 3
echo ""
echo "=== 服务已启动 ==="
echo "前端: http://localhost:3000"
echo "后端: http://localhost:8000"
echo ""
echo "默认账号: admin / Admin@123456"
echo ""
echo "查看服务状态: ps aux | grep -E '(php artisan|vite)' | grep -v grep"
echo "停止服务: pkill -f 'php artisan serve'; pkill -f vite"
