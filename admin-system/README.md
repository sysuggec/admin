# Admin System - 运营后台管理系统

基于 Laravel 11 + Vue 3 构建的运营后台管理系统，提供 JWT 认证、RBAC 权限管理、操作日志等功能。

## 功能特性

- 🔐 **JWT 认证** - 基于 Token 的无状态认证
- 👥 **RBAC 权限管理** - 用户-角色-权限三级管理
- 📝 **操作日志** - 自动记录用户操作行为
- 📊 **仪表盘统计** - 实时数据统计展示
- 🌳 **权限树结构** - 支持多级权限配置

## 技术栈

### 后端
- PHP 8.2+
- Laravel 11
- JWT Auth (tymon/jwt-auth)
- SQLite / MySQL

### 前端
- Vue 3
- TypeScript
- Vite
- Pinia
- Vue Router
- Element Plus
- Axios

## 环境要求

- PHP >= 8.2
- Composer
- Node.js >= 18
- npm 或 yarn

## 快速开始

### 1. 克隆项目

```bash
git clone <repository-url>
cd admin-system
```

### 2. 后端安装

```bash
# 安装 PHP 依赖
composer install

# 初始化项目（自动创建 .env、生成密钥、初始化数据库）
./scripts/init-project.sh
```

或者手动初始化：

```bash
# 复制环境配置
cp .env.example .env

# 生成应用密钥
php artisan key:generate

# 生成 JWT 密钥
php artisan jwt:secret

# 创建 SQLite 数据库文件
touch database/database.sqlite

# 执行数据库迁移和填充
php artisan migrate --seed
```

### 3. 前端安装与构建

```bash
cd frontend

# 安装依赖
npm install

# 构建前端（输出到 ../public/ 目录）
npm run build
```

### 4. 启动服务

```bash
# 返回项目根目录
cd ..

# 启动服务（持久运行）
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel.log 2>&1 &

# 停止服务
pkill -f "php artisan serve"
```

### 5. 访问系统

- 系统地址: http://localhost:8000
- API 地址: http://localhost:8000/api

默认管理员账号：
- 用户名: `admin`
- 密码: `Admin@123456`

## 常用命令

### 后端

```bash
# 启动开发服务器（持久运行）
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel.log 2>&1 &

# 停止服务
pkill -f "php artisan serve"

# 运行测试
php artisan test

# 重置数据库
./scripts/init-database.sh

# 清除缓存
php artisan cache:clear
php artisan config:clear

# 代码格式化
./vendor/bin/pint
```

### 前端

```bash
cd frontend

# 构建生产版本（输出到 ../public/）
npm run build

# 预览生产版本
npm run preview
```

## 环境配置说明

### 后端 .env 主要配置

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| APP_NAME | 应用名称 | AdminSystem |
| APP_ENV | 运行环境 | local |
| APP_DEBUG | 调试模式 | true |
| APP_TIMEZONE | 时区 | Asia/Shanghai |
| DB_CONNECTION | 数据库类型 | sqlite |
| JWT_TTL | Token 有效期(分钟) | 60 |
| JWT_REFRESH_TTL | 刷新 Token 有效期(分钟) | 20160 |

## API 接口

| 模块 | 路径前缀 | 说明 |
|------|----------|------|
| 认证 | /api/auth | 登录、登出、刷新 Token |
| 用户 | /api/users | 用户 CRUD |
| 角色 | /api/roles | 角色 CRUD、权限分配 |
| 权限 | /api/permissions | 权限 CRUD |
| 操作日志 | /api/operation-logs | 日志查询、导出 |
| 登录日志 | /api/login-logs | 登录日志查询 |
| 仪表盘 | /api/dashboard | 统计数据 |

## 项目结构

```
admin-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # 控制器
│   │   └── Middleware/      # 中间件
│   ├── Models/              # 模型
│   └── Providers/           # 服务提供者
├── database/
│   ├── migrations/          # 数据库迁移
│   └── seeders/             # 数据填充
├── routes/
│   └── api.php              # API 路由
├── scripts/
│   ├── init-project.sh      # 项目初始化脚本
│   └── init-database.sh     # 数据库重置脚本
├── frontend/                # Vue 前端项目
│   ├── src/
│   │   ├── api/             # API 模块
│   │   ├── components/      # 公共组件
│   │   ├── layouts/         # 布局组件（动态菜单渲染）
│   │   ├── router/          # 路由配置
│   │   ├── stores/          # Pinia 状态管理
│   │   ├── styles/          # 全局样式
│   │   └── views/           # 页面组件
│   │       └── system/
│   │           ├── user/    # 用户管理
│   │           ├── role/    # 角色管理
│   │           ├── permission/ # 权限管理
│   │           ├── log/     # 操作日志
│   │           └── login-log/ # 登录日志
│   └── vite.config.ts       # Vite 配置
├── .env.example             # 后端环境配置模板
└── README.md
```

## 权限说明

权限格式为 `resource:action`，例如：
- `user:list` - 用户列表
- `user:create` - 创建用户
- `user:edit` - 编辑用户
- `user:delete` - 删除用户

角色 `super_admin` 拥有所有权限，无需单独配置。

## 动态菜单系统

导航菜单根据用户权限动态渲染：

1. API `/api/auth/me` 返回用户的 `menus` 数组
2. 前端 `MainLayout.vue` 遍历 `userStore.menus` 渲染菜单
3. 菜单图标通过 `iconMap` 映射

添加新菜单步骤：
1. 在数据库添加权限记录 (type: `menu`)
2. 将权限分配给角色
3. 在 `router/index.ts` 添加路由
4. 如使用新图标，在 `MainLayout.vue` 的 `iconMap` 中添加映射

## License

MIT
