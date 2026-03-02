# Admin System - 运营后台管理系统

基于 Laravel 11 构建的运营后台 API 服务。

## 功能特性

- JWT 认证
- RBAC 角色权限管理
- 用户/角色/权限管理
- 操作日志记录
- 仪表盘统计

## 快速开始

```bash
# 安装依赖
composer install

# 初始化数据库
./scripts/init-database.sh

# 启动开发服务
php artisan serve --host=0.0.0.0 --port=8000
```

## API 接口

| 模块 | 路径前缀 | 说明 |
|------|----------|------|
| 认证 | /api/auth | 登录、登出、刷新 Token |
| 用户 | /api/users | 用户 CRUD |
| 角色 | /api/roles | 角色 CRUD、权限分配 |
| 权限 | /api/permissions | 权限 CRUD |
| 操作日志 | /api/operation-logs | 日志查询、导出 |
| 仪表盘 | /api/dashboard | 统计数据 |

## 默认账号

- 用户名: `admin`
- 密码: `Admin@123456`

## 技术栈

- PHP 8.2+
- Laravel 11
- JWT Auth (tymon/jwt-auth)
- SQLite / MySQL
