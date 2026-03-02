# admin

运营后台管理系统

## 项目简介

基于 Laravel 11 + Vue 3 + TypeScript 构建的运营后台管理系统，支持用户认证（JWT）、角色权限管理（RBAC）、操作日志记录等功能。

## 技术栈

- **后端**: PHP 8.2 + Laravel 11 + JWT Auth
- **前端**: Vue 3 + TypeScript + Vite + Element Plus + Pinia
- **数据库**: SQLite (开发) / MySQL (生产)

## 快速开始

```bash
# 启动开发服务
cd admin-system && ./scripts/start-dev.sh

# 访问地址
# 前端: http://localhost:3000
# 后端: http://localhost:8000

# 默认账号
# 用户名: admin
# 密码: Admin@123456
```

## 文档

- [产品方案文档](doc/prduct.md)
- [开发问题记录](doc/dev-issues.md)
- [CodeBuddy 指南](CODEBUDDY.md)

## 功能模块

- 用户管理
- 角色管理
- 权限管理
- 操作日志
- 仪表盘统计
