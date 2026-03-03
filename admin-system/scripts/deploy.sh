#!/bin/bash
# 生产环境部署脚本
# 自动化构建前后端并优化 Laravel

set -e

echo "=========================================="
echo "   Admin System 生产环境部署脚本"
echo "=========================================="

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 打印函数
info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

# 检查命令是否存在
check_command() {
    if ! command -v "$1" &> /dev/null; then
        error "$1 未安装，请先安装 $1"
    fi
}

# 解析参数
SKIP_FRONTEND=false
SKIP_BACKEND=false
PRODUCTION=true

while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-frontend)
            SKIP_FRONTEND=true
            shift
            ;;
        --skip-backend)
            SKIP_BACKEND=true
            shift
            ;;
        --dev)
            PRODUCTION=false
            shift
            ;;
        --help)
            echo "用法: $0 [选项]"
            echo ""
            echo "选项:"
            echo "  --skip-frontend  跳过前端构建"
            echo "  --skip-backend   跳过后端依赖安装"
            echo "  --dev            开发模式构建（不优化）"
            echo "  --help           显示帮助信息"
            exit 0
            ;;
        *)
            warn "未知参数: $1"
            shift
            ;;
    esac
done

cd "$PROJECT_DIR"

# ============================================
# 1. 环境检查
# ============================================
echo ""
info "步骤 1/5: 环境检查..."

check_command "php"
check_command "composer"

PHP_VERSION=$(php -r "echo PHP_VERSION;")
info "PHP 版本: $PHP_VERSION"

if [ "$SKIP_FRONTEND" = false ]; then
    check_command "node"
    check_command "npm"
    NODE_VERSION=$(node -v)
    NPM_VERSION=$(npm -v)
    info "Node 版本: $NODE_VERSION"
    info "NPM 版本: $NPM_VERSION"
fi

# ============================================
# 2. 后端依赖安装
# ============================================
if [ "$SKIP_BACKEND" = false ]; then
    echo ""
    info "步骤 2/5: 安装后端依赖..."

    if [ "$PRODUCTION" = true ]; then
        composer install --optimize-autoloader --no-dev
    else
        composer install
    fi
else
    echo ""
    info "步骤 2/5: 跳过后端依赖安装"
fi

# ============================================
# 3. 前端构建
# ============================================
if [ "$SKIP_FRONTEND" = false ]; then
    echo ""
    info "步骤 3/5: 构建前端..."

    FRONTEND_DIR="$PROJECT_DIR/frontend"

    if [ ! -d "$FRONTEND_DIR" ]; then
        error "前端目录不存在: $FRONTEND_DIR"
    fi

    cd "$FRONTEND_DIR"

    # 安装前端依赖
    if [ ! -d "node_modules" ] || [ package.json -nt node_modules ]; then
        info "安装前端依赖..."
        npm install
    fi

    # 清理旧的构建产物
    info "清理旧的构建产物..."
    rm -rf "$PROJECT_DIR/public/assets"
    rm -rf "$PROJECT_DIR/public/.vite"
    rm -f "$PROJECT_DIR/public/index.html"
    rm -f "$PROJECT_DIR/public/vite.svg"

    # 构建前端
    info "构建前端资源..."
    if [ "$PRODUCTION" = true ]; then
        npm run build
    else
        npm run build -- --mode development
    fi

    cd "$PROJECT_DIR"
else
    echo ""
    info "步骤 3/5: 跳过前端构建"
fi

# ============================================
# 4. Laravel 优化
# ============================================
echo ""
info "步骤 4/5: Laravel 优化..."

# 清除旧缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

if [ "$PRODUCTION" = true ]; then
    # 缓存配置
    info "缓存配置..."
    php artisan config:cache

    info "缓存路由..."
    php artisan route:cache

    info "缓存视图..."
    php artisan view:cache

    # 链接存储目录
    if [ ! -L "$PROJECT_DIR/public/storage" ]; then
        info "创建存储链接..."
        php artisan storage:link
    fi
fi

# ============================================
# 5. 权限设置
# ============================================
echo ""
info "步骤 5/5: 设置文件权限..."

# 设置 storage 和 bootstrap/cache 目录权限
chmod -R 775 "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/bootstrap/cache"

# 如果 www-data 用户存在，设置所有者
if id "www-data" &>/dev/null; then
    info "设置文件所有者为 www-data..."
    chown -R www-data:www-data "$PROJECT_DIR/storage"
    chown -R www-data:www-data "$PROJECT_DIR/bootstrap/cache"
fi

# ============================================
# 完成
# ============================================
echo ""
echo "=========================================="
echo -e "${GREEN}   部署完成!${NC}"
echo "=========================================="
echo ""

if [ "$PRODUCTION" = true ]; then
    info "生产环境已就绪"
    echo ""
    echo "后续步骤:"
    echo "  1. 确保 .env 文件已正确配置"
    echo "  2. 配置 Nginx (参考 nginx.conf.example)"
    echo "  3. 重启 PHP-FPM: sudo systemctl restart php8.2-fpm"
    echo "  4. 访问应用检查是否正常运行"
else
    info "开发环境构建完成"
fi

echo ""
echo "默认管理员账号:"
echo "  用户名: admin"
echo "  密码: Admin@123456"
