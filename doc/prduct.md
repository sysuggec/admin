# PHP 运营后台系统方案

## 一、系统概述

基于 PHP 构建的运营后台管理系统，支持登录认证和细粒度权限管理（精确到按钮级别），包含完整的操作日志记录功能。

---

## 二、技术选型

| 层级 | 技术方案 | 说明 |
|------|----------|------|
| 后端框架 | PHP 8.x + Laravel 11 | 使用 Eloquent ORM 操作数据库 |
| 数据库(开发) | SQLite | 本地开发环境，零配置 |
| 数据库(生产) | MySQL 8.0 | 生产环境，支持事务、高并发 |
| 缓存 | Redis | 存储会话、权限缓存 |
| 前端框架 | Vue 3 + TypeScript | 组合式 API，类型安全 |
| UI 组件库 | Element Plus | 现代化 Vue 3 UI 组件库 |
| 构建工具 | Vite | 快速开发构建 |
| 状态管理 | Pinia | Vue 3 官方推荐状态管理 |
| 路由 | Vue Router 4 | 前端路由管理 |
| HTTP 客户端 | Axios | API 请求封装 |
| 认证 | JWT Token | 无状态认证，支持多端 |

### 2.1 数据库切换配置

通过 Laravel 的 `.env` 环境变量实现开发与生产环境的快速切换：

```env
# 开发环境 (.env.local)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# 生产环境 (.env.production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_system
DB_USERNAME=root
DB_PASSWORD=secret
```

> **说明**：由于使用 Eloquent ORM，代码层面无需修改，只需调整环境配置即可完成数据库切换。

---

## 三、数据库设计

> **设计规范**：所有表名以 `t_` 开头，不使用外键约束，通过应用层维护数据一致性。

### 3.1 用户表 (t_user)

```sql
CREATE TABLE t_user (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码(bcrypt加密)',
    email VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
    phone VARCHAR(20) DEFAULT NULL COMMENT '手机号',
    avatar VARCHAR(255) DEFAULT NULL COMMENT '头像URL',
    status TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL COMMENT '软删除时间',
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
```

### 3.2 角色表 (t_role)

```sql
CREATE TABLE t_role (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE COMMENT '角色标识',
    display_name VARCHAR(100) NOT NULL COMMENT '角色名称',
    description VARCHAR(255) DEFAULT NULL COMMENT '角色描述',
    sort_order INT DEFAULT 0 COMMENT '排序',
    status TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';
```

### 3.3 权限表 (t_permission)

```sql
CREATE TABLE t_permission (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE COMMENT '权限标识',
    display_name VARCHAR(100) NOT NULL COMMENT '权限名称',
    type ENUM('menu', 'button', 'api') NOT NULL COMMENT '权限类型',
    parent_id INT UNSIGNED DEFAULT 0 COMMENT '父级ID(菜单层级)',
    path VARCHAR(255) DEFAULT NULL COMMENT '前端路由路径',
    api_path VARCHAR(255) DEFAULT NULL COMMENT 'API路径',
    icon VARCHAR(100) DEFAULT NULL COMMENT '菜单图标',
    sort_order INT DEFAULT 0 COMMENT '排序',
    status TINYINT DEFAULT 1 COMMENT '状态: 0禁用 1启用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限表';
```

### 3.4 用户角色关联表 (t_user_role)

```sql
CREATE TABLE t_user_role (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    role_id INT UNSIGNED NOT NULL COMMENT '角色ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_role (user_id, role_id),
    INDEX idx_user (user_id),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户角色关联表';
```

### 3.5 角色权限关联表 (t_role_permission)

```sql
CREATE TABLE t_role_permission (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL COMMENT '角色ID',
    permission_id INT UNSIGNED NOT NULL COMMENT '权限ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_role_permission (role_id, permission_id),
    INDEX idx_role (role_id),
    INDEX idx_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';
```

### 3.6 登录日志表 (t_login_log)

```sql
CREATE TABLE t_login_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    username VARCHAR(50) NOT NULL COMMENT '用户名',
    ip VARCHAR(45) NOT NULL COMMENT '登录IP',
    user_agent VARCHAR(500) DEFAULT NULL COMMENT '浏览器信息',
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '登录时间',
    status TINYINT DEFAULT 1 COMMENT '状态: 0失败 1成功',
    message VARCHAR(255) DEFAULT NULL COMMENT '登录消息',
    INDEX idx_user (user_id),
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';
```

### 3.7 操作日志表 (t_operation_log)

```sql
CREATE TABLE t_operation_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '操作用户ID',
    username VARCHAR(50) NOT NULL COMMENT '操作用户名',
    module VARCHAR(50) NOT NULL COMMENT '操作模块',
    action VARCHAR(50) NOT NULL COMMENT '操作类型',
    title VARCHAR(200) NOT NULL COMMENT '操作标题',
    method VARCHAR(10) NOT NULL COMMENT '请求方法',
    url VARCHAR(500) NOT NULL COMMENT '请求URL',
    params TEXT DEFAULT NULL COMMENT '请求参数',
    response TEXT DEFAULT NULL COMMENT '响应结果',
    ip VARCHAR(45) NOT NULL COMMENT '操作IP',
    user_agent VARCHAR(500) DEFAULT NULL COMMENT '浏览器信息',
    status TINYINT DEFAULT 1 COMMENT '状态: 0失败 1成功',
    error_msg TEXT DEFAULT NULL COMMENT '错误信息',
    duration INT UNSIGNED DEFAULT 0 COMMENT '执行时长(毫秒)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
    INDEX idx_user (user_id),
    INDEX idx_module (module),
    INDEX idx_action (action),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';
```

---

## 四、数据库初始化工具

> **设计原则**：数据库初始化通过独立脚本工具执行，不在接口中处理，确保安全性和可维护性。

### 4.1 目录结构

```
database/
├── migrations/              # 数据库迁移文件
│   ├── 2026_03_02_000001_create_user_table.php
│   ├── 2026_03_02_000002_create_role_table.php
│   ├── 2026_03_02_000003_create_permission_table.php
│   ├── 2026_03_02_000004_create_user_role_table.php
│   ├── 2026_03_02_000005_create_role_permission_table.php
│   ├── 2026_03_02_000006_create_login_log_table.php
│   └── 2026_03_02_000007_create_operation_log_table.php
├── seeders/                 # 数据填充文件
│   ├── RoleSeeder.php       # 默认角色
│   ├── PermissionSeeder.php # 默认权限
│   └── AdminUserSeeder.php  # 默认管理员
└── database.sqlite          # SQLite 数据库文件(开发环境)
```

### 4.2 初始化命令

```bash
# 创建 SQLite 数据库文件(开发环境)
touch database/database.sqlite

# 执行数据库迁移(创建表结构)
php artisan migrate

# 回滚并重新迁移
php artisan migrate:fresh

# 填充初始数据
php artisan db:seed

# 执行迁移并填充数据(推荐)
php artisan migrate:fresh --seed

# 单独填充指定 Seeder
php artisan db:seed --class=AdminUserSeeder

# 生产环境执行迁移(带确认)
php artisan migrate --force
```

### 4.3 迁移文件示例

```php
<?php
// database/migrations/2026_03_02_000001_create_user_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_user', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique()->comment('用户名');
            $table->string('password', 255)->comment('密码');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->string('avatar', 255)->nullable()->comment('头像URL');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1启用');
            $table->timestamp('deleted_at')->nullable()->comment('软删除时间');
            $table->timestamps();

            $table->index('username');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_user');
    }
};
```

### 4.4 Seeder 文件示例

```php
<?php
// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('t_role')->insert([
            [
                'name' => 'super_admin',
                'display_name' => '超级管理员',
                'description' => '拥有所有权限',
                'sort_order' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'admin',
                'display_name' => '管理员',
                'description' => '拥有大部分权限',
                'sort_order' => 2,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'operator',
                'display_name' => '运营人员',
                'description' => '拥有业务操作权限',
                'sort_order' => 3,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
```

```php
<?php
// database/seeders/AdminUserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 创建超级管理员
        $userId = DB::table('t_user')->insertGetId([
            'username' => 'admin',
            'password' => Hash::make('Admin@123456'),
            'email' => 'admin@example.com',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 关联超级管理员角色
        DB::table('t_user_role')->insert([
            'user_id' => $userId,
            'role_id' => 1, // super_admin
            'created_at' => $now,
        ]);
    }
}
```

```php
<?php
// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
```

### 4.5 初始化脚本(生产环境)

```bash
#!/bin/bash
# scripts/init-database.sh

set -e

echo "=== 数据库初始化脚本 ==="

# 检查环境配置
if [ ! -f .env ]; then
    echo "错误: .env 文件不存在"
    exit 1
fi

# 确认执行
read -p "此操作将清空并重建数据库，是否继续? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "操作已取消"
    exit 0
fi

# 执行迁移
echo ">>> 执行数据库迁移..."
php artisan migrate --force

# 填充初始数据
echo ">>> 填充初始数据..."
php artisan db:seed --force

# 清除缓存
echo ">>> 清除缓存..."
php artisan cache:clear
php artisan config:clear

echo "=== 初始化完成 ==="
```

---

## 五、权限模型设计

### 4.1 RBAC 权限模型

```
用户(User) --N:N--> 角色(Role) --N:N--> 权限(Permission)
```

### 4.2 权限层级结构

```
权限类型:
├── menu (菜单权限)     - 控制菜单显示/隐藏
├── button (按钮权限)   - 控制页面内按钮显示/隐藏
└── api (接口权限)      - 控制后端接口访问

权限树示例:
├── 系统管理 (menu)
│   ├── 用户管理 (menu)
│   │   ├── 用户列表查看 (button: user:list)
│   │   ├── 新增用户 (button: user:create)
│   │   ├── 编辑用户 (button: user:edit)
│   │   └── 删除用户 (button: user:delete)
│   ├── 角色管理 (menu)
│   │   ├── 角色列表查看 (button: role:list)
│   │   ├── 新增角色 (button: role:create)
│   │   ├── 编辑角色 (button: role:edit)
│   │   └── 删除角色 (button: role:delete)
│   └── 操作日志 (menu)
│       ├── 日志列表查看 (button: log:list)
│       └── 日志详情查看 (button: log:detail)
```

### 4.3 权限标识命名规范

```
格式: {模块}:{资源}:{操作}

示例:
- user:list      - 用户列表
- user:create    - 创建用户
- user:edit      - 编辑用户
- user:delete    - 删除用户
- user:export    - 导出用户
- user:import    - 导入用户
```

---

## 五、API 接口设计

### 5.1 认证模块

| 方法 | 路径 | 说明 | 权限 |
|------|------|------|------|
| POST | /api/auth/login | 用户登录 | 公开 |
| POST | /api/auth/logout | 用户登出 | 需登录 |
| POST | /api/auth/refresh | 刷新Token | 需登录 |
| GET | /api/auth/me | 获取当前用户信息 | 需登录 |

### 5.2 用户管理模块

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | /api/users | 用户列表 | user:list |
| POST | /api/users | 创建用户 | user:create |
| GET | /api/users/{id} | 用户详情 | user:list |
| PUT | /api/users/{id} | 更新用户 | user:edit |
| DELETE | /api/users/{id} | 删除用户 | user:delete |
| PUT | /api/users/{id}/status | 切换用户状态 | user:edit |

### 5.3 角色管理模块

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | /api/roles | 角色列表 | role:list |
| POST | /api/roles | 创建角色 | role:create |
| GET | /api/roles/{id} | 角色详情 | role:list |
| PUT | /api/roles/{id} | 更新角色 | role:edit |
| DELETE | /api/roles/{id} | 删除角色 | role:delete |
| GET | /api/roles/{id}/permissions | 获取角色权限 | role:list |
| PUT | /api/roles/{id}/permissions | 设置角色权限 | role:edit |

### 5.4 权限管理模块

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | /api/permissions | 权限列表(树形) | permission:list |
| POST | /api/permissions | 创建权限 | permission:create |
| PUT | /api/permissions/{id} | 更新权限 | permission:edit |
| DELETE | /api/permissions/{id} | 删除权限 | permission:delete |

### 5.5 操作日志模块

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | /api/operation-logs | 操作日志列表 | log:list |
| GET | /api/operation-logs/{id} | 操作日志详情 | log:detail |
| DELETE | /api/operation-logs/{id} | 删除操作日志 | log:delete |
| DELETE | /api/operation-logs/batch | 批量删除操作日志 | log:delete |
| GET | /api/operation-logs/export | 导出操作日志 | log:export |

---

## 六、后端核心实现

### 6.1 目录结构

```
app/
├── Controllers/
│   ├── AuthController.php           # 认证控制器
│   ├── UserController.php           # 用户控制器
│   ├── RoleController.php           # 角色控制器
│   ├── PermissionController.php     # 权限控制器
│   └── OperationLogController.php   # 操作日志控制器
├── Models/
│   ├── User.php                     # 用户模型
│   ├── Role.php                     # 角色模型
│   ├── Permission.php               # 权限模型
│   ├── LoginLog.php                 # 登录日志模型
│   └── OperationLog.php             # 操作日志模型
├── Middleware/
│   ├── AuthMiddleware.php           # 认证中间件
│   ├── PermissionMiddleware.php     # 权限中间件
│   └── OperationLogMiddleware.php   # 操作日志中间件
├── Services/
│   ├── AuthService.php              # 认证服务
│   ├── PermissionService.php        # 权限服务
│   └── OperationLogService.php      # 操作日志服务
├── Enums/
│   └── OperationAction.php          # 操作类型枚举
└── Helpers/
    └── Response.php                 # 响应辅助类
```

### 6.2 权限中间件核心逻辑

```php
<?php
// PermissionMiddleware.php

class PermissionMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        // 超级管理员跳过权限检查
        if ($user->is_super_admin) {
            return $next($request);
        }

        // 获取当前路由需要的权限
        $requiredPermission = $this->getRequiredPermission($request);

        if (!$requiredPermission) {
            return $next($request);
        }

        // 检查用户是否拥有该权限
        $userPermissions = $this->getUserPermissions($user->id);

        if (!in_array($requiredPermission, $userPermissions)) {
            return response()->json([
                'code' => 403,
                'message' => '无权限访问'
            ], 403);
        }

        return $next($request);
    }

    /**
     * 获取用户所有权限(带缓存)
     */
    private function getUserPermissions($userId)
    {
        $cacheKey = "user:permissions:{$userId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            return Permission::whereHas('roles.users', function ($query) use ($userId) {
                $query->where('t_user.id', $userId);
            })->pluck('name')->toArray();
        });
    }

    /**
     * 清除用户权限缓存
     */
    public static function clearUserPermissionCache($userId)
    {
        Cache::forget("user:permissions:{$userId}");
    }
}
```

### 6.3 权限服务类

```php
<?php
// PermissionService.php

class PermissionService
{
    /**
     * 获取用户的菜单树
     */
    public function getUserMenuTree($userId)
    {
        $permissions = $this->getUserPermissions($userId);

        $menus = Permission::whereIn('name', $permissions)
            ->where('type', 'menu')
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        return $this->buildTree($menus);
    }

    /**
     * 获取用户的按钮权限列表
     */
    public function getUserButtonPermissions($userId)
    {
        $permissions = $this->getUserPermissions($userId);

        return Permission::whereIn('name', $permissions)
            ->where('type', 'button')
            ->where('status', 1)
            ->pluck('name')
            ->toArray();
    }

    /**
     * 构建权限树
     */
    private function buildTree($items, $parentId = 0)
    {
        $tree = [];

        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($items, $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }

        return $tree;
    }
}
```

### 6.4 操作日志中间件

```php
<?php
// OperationLogMiddleware.php

class OperationLogMiddleware
{
    /**
     * 需要记录日志的方法
     */
    protected $logMethods = ['POST', 'PUT', 'DELETE'];

    /**
     * 不需要记录日志的路由
     */
    protected $exceptRoutes = [
        'api/auth/login',
        'api/auth/logout',
        'api/auth/refresh',
        'api/operation-logs',
    ];

    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        // 异步记录操作日志
        if ($this->shouldLog($request)) {
            $this->logOperation($request, $response, $startTime);
        }

        return $response;
    }

    /**
     * 判断是否需要记录日志
     */
    protected function shouldLog($request)
    {
        // 只记录指定方法
        if (!in_array($request->method(), $this->logMethods)) {
            return false;
        }

        // 排除指定路由
        $path = $request->path();
        foreach ($this->exceptRoutes as $except) {
            if (strpos($path, $except) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 记录操作日志
     */
    protected function logOperation($request, $response, $startTime)
    {
        $user = $request->user();
        $duration = round((microtime(true) - $startTime) * 1000);

        $data = [
            'user_id' => $user->id ?? 0,
            'username' => $user->username ?? 'unknown',
            'module' => $this->getModule($request),
            'action' => $this->getAction($request),
            'title' => $this->getTitle($request),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'params' => json_encode($this->filterSensitiveParams($request->all())),
            'response' => $response->getContent(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $response->status() === 200 ? 1 : 0,
            'error_msg' => $response->status() !== 200 ? $response->getContent() : null,
            'duration' => $duration,
        ];

        // 异步写入日志
        OperationLog::create($data);
    }

    /**
     * 获取操作模块
     */
    protected function getModule($request)
    {
        $path = $request->path();
        $segments = explode('/', $path);

        $moduleMap = [
            'users' => '用户管理',
            'roles' => '角色管理',
            'permissions' => '权限管理',
            'operation-logs' => '操作日志',
        ];

        return $moduleMap[$segments[1] ?? ''] ?? '其他';
    }

    /**
     * 获取操作类型
     */
    protected function getAction($request)
    {
        $method = $request->method();
        $path = $request->path();

        $actionMap = [
            'POST' => 'create',
            'PUT' => 'update',
            'DELETE' => 'delete',
        ];

        // 特殊操作判断
        if (strpos($path, '/status') !== false) {
            return 'status';
        }
        if (strpos($path, '/permissions') !== false && $method === 'PUT') {
            return 'assign_permission';
        }

        return $actionMap[$method] ?? 'unknown';
    }

    /**
     * 获取操作标题
     */
    protected function getTitle($request)
    {
        $module = $this->getModule($request);
        $action = $this->getAction($request);

        $actionMap = [
            'create' => '新增',
            'update' => '编辑',
            'delete' => '删除',
            'status' => '切换状态',
            'assign_permission' => '分配权限',
        ];

        return $module . $actionMap[$action] ?? '未知操作';
    }

    /**
     * 过滤敏感参数
     */
    protected function filterSensitiveParams($params)
    {
        $sensitiveKeys = ['password', 'password_confirm', 'old_password', 'token'];

        foreach ($sensitiveKeys as $key) {
            if (isset($params[$key])) {
                $params[$key] = '******';
            }
        }

        return $params;
    }
}
```

### 6.5 操作日志服务类

```php
<?php
// OperationLogService.php

class OperationLogService
{
    /**
     * 获取日志列表
     */
    public function getList($params)
    {
        $query = OperationLog::query();

        // 用户名筛选
        if (!empty($params['username'])) {
            $query->where('username', 'like', "%{$params['username']}%");
        }

        // 模块筛选
        if (!empty($params['module'])) {
            $query->where('module', $params['module']);
        }

        // 操作类型筛选
        if (!empty($params['action'])) {
            $query->where('action', $params['action']);
        }

        // 状态筛选
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }

        // 时间范围筛选
        if (!empty($params['start_time'])) {
            $query->where('created_at', '>=', $params['start_time']);
        }
        if (!empty($params['end_time'])) {
            $query->where('created_at', '<=', $params['end_time']);
        }

        // IP 筛选
        if (!empty($params['ip'])) {
            $query->where('ip', $params['ip']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($params['page_size'] ?? 20);
    }

    /**
     * 批量删除日志
     */
    public function batchDelete($ids)
    {
        return OperationLog::whereIn('id', $ids)->delete();
    }

    /**
     * 清理过期日志
     */
    public function cleanExpiredLogs($days = 90)
    {
        $expireDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return OperationLog::where('created_at', '<', $expireDate)->delete();
    }

    /**
     * 导出日志
     */
    public function export($params)
    {
        $logs = $this->getList(array_merge($params, ['page_size' => 10000]));

        $data = [];
        foreach ($logs->items() as $log) {
            $data[] = [
                'ID' => $log->id,
                '操作用户' => $log->username,
                '操作模块' => $log->module,
                '操作类型' => $log->action,
                '操作标题' => $log->title,
                '请求方法' => $log->method,
                '请求URL' => $log->url,
                '操作IP' => $log->ip,
                '状态' => $log->status ? '成功' : '失败',
                '执行时长(ms)' => $log->duration,
                '操作时间' => $log->created_at,
            ];
        }

        return $data;
    }

    /**
     * 获取操作统计
     */
    public function getStatistics($startDate, $endDate)
    {
        return OperationLog::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                module,
                action,
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as fail_count,
                AVG(duration) as avg_duration
            ')
            ->groupBy('module', 'action')
            ->get();
    }
}
```

### 6.6 操作类型枚举

```php
<?php
// Enums/OperationAction.php

enum OperationAction: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case STATUS = 'status';
    case ASSIGN_PERMISSION = 'assign_permission';
    case EXPORT = 'export';
    case IMPORT = 'import';
    case LOGIN = 'login';
    case LOGOUT = 'logout';

    public function label(): string
    {
        return match($this) {
            self::CREATE => '新增',
            self::UPDATE => '编辑',
            self::DELETE => '删除',
            self::STATUS => '切换状态',
            self::ASSIGN_PERMISSION => '分配权限',
            self::EXPORT => '导出',
            self::IMPORT => '导入',
            self::LOGIN => '登录',
            self::LOGOUT => '登出',
        };
    }

    public static function getLabels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
```

---

## 七、前端技术实现

### 7.1 前端目录结构

```
frontend/
├── public/                     # 静态资源
├── src/
│   ├── api/                    # API 接口封装
│   │   ├── request.ts          # Axios 封装、拦截器
│   │   ├── auth.ts             # 认证接口
│   │   ├── user.ts             # 用户接口
│   │   ├── role.ts             # 角色接口
│   │   ├── permission.ts       # 权限接口
│   │   └── operation-log.ts    # 操作日志接口
│   ├── components/             # 公共组件
│   ├── composables/            # 组合式函数
│   ├── directives/             # 自定义指令
│   │   └── permission.ts       # 权限指令
│   ├── layouts/                # 布局组件
│   │   └── MainLayout.vue      # 主布局
│   ├── plugins/                # 插件配置
│   ├── router/                 # 路由配置
│   │   └── index.ts            # 路由定义、守卫
│   ├── stores/                 # Pinia 状态管理
│   │   └── user.ts             # 用户状态
│   ├── styles/                 # 全局样式
│   │   └── index.css           # 样式入口
│   ├── types/                  # TypeScript 类型定义
│   │   └── index.ts            # 类型声明
│   ├── views/                  # 页面组件
│   │   ├── login/              # 登录页
│   │   ├── dashboard/          # 仪表盘
│   │   ├── system/             # 系统管理
│   │   │   ├── user/           # 用户管理
│   │   │   ├── role/           # 角色管理
│   │   │   ├── permission/     # 权限管理
│   │   │   └── log/            # 操作日志
│   │   └── error/              # 错误页面
│   ├── App.vue                 # 根组件
│   └── main.ts                 # 入口文件
├── .env                        # 环境变量
├── vite.config.ts              # Vite 配置
├── tsconfig.json               # TypeScript 配置
└── package.json                # 项目依赖
```

### 7.2 API 请求封装

```typescript
// src/api/request.ts
import axios, { type AxiosInstance, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios'
import { useUserStore } from '@/stores/user'
import router from '@/router'
import { ElMessage } from 'element-plus'
import type { ApiResponse } from '@/types'

// 创建 axios 实例
const request: AxiosInstance = axios.create({
  baseURL: '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// 请求拦截器
request.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const userStore = useUserStore()
    const token = userStore.token

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  (response: AxiosResponse<ApiResponse>) => {
    const { data } = response

    // 业务状态码判断
    if (data.code === 200) {
      return response
    }

    // 其他业务错误
    ElMessage.error(data.message || '请求失败')
    return Promise.reject(new Error(data.message || '请求失败'))
  },
  (error) => {
    const { response } = error

    if (response) {
      switch (response.status) {
        case 401:
          const userStore = useUserStore()
          userStore.logout()
          router.push('/login')
          ElMessage.error('登录已过期，请重新登录')
          break
        case 403:
          ElMessage.error('没有权限访问')
          break
        case 404:
          ElMessage.error('请求的资源不存在')
          break
        case 500:
          ElMessage.error('服务器错误')
          break
        default:
          ElMessage.error(response.data?.message || '请求失败')
      }
    } else {
      ElMessage.error('网络连接失败')
    }

    return Promise.reject(error)
  }
)

export default request
```

### 7.3 路由守卫

```typescript
// src/router/index.ts
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useUserStore } from '@/stores/user'

// 路由配置
const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/login/index.vue'),
    meta: { title: '登录', public: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: '仪表盘' },
      },
      {
        path: 'system',
        name: 'System',
        redirect: '/system/user',
        meta: { title: '系统管理' },
        children: [
          {
            path: 'user',
            name: 'User',
            component: () => import('@/views/system/user/index.vue'),
            meta: { title: '用户管理', permission: 'user' },
          },
          // ... 其他路由
        ],
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 路由守卫
router.beforeEach(async (to, _from, next) => {
  // 设置页面标题
  document.title = to.meta.title ? `${to.meta.title} - AdminSystem` : 'AdminSystem'

  const userStore = useUserStore()
  const isPublic = to.meta.public

  // 公开页面直接放行
  if (isPublic) {
    next()
    return
  }

  // 检查是否登录
  if (!userStore.isLoggedIn) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
    return
  }

  // 获取用户信息
  if (!userStore.userInfo) {
    try {
      await userStore.fetchUserInfo()
    } catch {
      next({ name: 'Login' })
      return
    }
  }

  // 检查路由权限
  const permission = to.meta.permission
  if (permission && !userStore.hasPermission(permission as string)) {
    next({ name: 'Forbidden' })
    return
  }

  next()
})

export default router
```

### 7.4 按钮权限指令

```typescript
// src/directives/permission.ts
import type { Directive, DirectiveBinding } from 'vue'
import { useUserStore } from '@/stores/user'

/**
 * 权限指令
 * 使用方式: v-permission="'user:create'" 或 v-permission="['user:create', 'user:edit']"
 */
export const permission: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding<string | string[]>) {
    const userStore = useUserStore()
    const { value } = binding

    if (!value) {
      return
    }

    let hasPermission = false

    if (typeof value === 'string') {
      hasPermission = userStore.hasPermission(value)
    } else if (Array.isArray(value)) {
      hasPermission = userStore.hasAnyPermission(value)
    }

    if (!hasPermission) {
      el.parentNode?.removeChild(el)
    }
  },
}

// 注册全局指令
export function setupPermissionDirectives(app: import('vue').App) {
  app.directive('permission', permission)
}

// 使用示例
// <el-button v-permission="'user:create'">新增用户</el-button>
// <el-button v-permission="'user:edit'">编辑</el-button>
// <el-button v-permission="'user:delete'">删除</el-button>
```

### 7.5 Pinia 状态管理

```typescript
// src/stores/user.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as loginApi, logout as logoutApi, getUserInfo as getUserInfoApi } from '@/api/auth'
import type { UserInfoResponse } from '@/types'
import router from '@/router'

export const useUserStore = defineStore('user', () => {
  // 状态
  const token = ref<string>(localStorage.getItem('token') || '')
  const userInfo = ref<UserInfoResponse | null>(null)

  // 计算属性
  const isLoggedIn = computed(() => !!token.value)
  const permissions = computed(() => userInfo.value?.permissions || [])
  const menus = computed(() => userInfo.value?.menus || [])
  const roles = computed(() => userInfo.value?.roles || [])

  // 登录
  async function login(username: string, password: string) {
    const { data } = await loginApi({ username, password })
    token.value = data.access_token
    localStorage.setItem('token', data.access_token)
    return data
  }

  // 登出
  async function logout() {
    try {
      await logoutApi()
    } finally {
      token.value = ''
      userInfo.value = null
      localStorage.removeItem('token')
    }
  }

  // 获取用户信息
  async function fetchUserInfo() {
    const { data } = await getUserInfoApi()
    userInfo.value = data
    return data
  }

  // 检查是否有权限
  function hasPermission(permission: string): boolean {
    return permissions.value.includes(permission)
  }

  // 检查是否有任意一个权限
  function hasAnyPermission(permissionList: string[]): boolean {
    return permissionList.some(p => permissions.value.includes(p))
  }

  // 检查是否有所有权限
  function hasAllPermissions(permissionList: string[]): boolean {
    return permissionList.every(p => permissions.value.includes(p))
  }

  return {
    token,
    userInfo,
    isLoggedIn,
    permissions,
    menus,
    roles,
    login,
    logout,
    fetchUserInfo,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
  }
})
```

### 7.6 TypeScript 类型定义

```typescript
// src/types/index.ts

// API 响应类型
export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
}

// 分页响应类型
export interface PaginatedResponse<T> {
  list: T[]
  total: number
  page: number
  page_size: number
}

// 用户类型
export interface User {
  id: number
  username: string
  email: string | null
  phone: string | null
  avatar: string | null
  status: number
  created_at: string
  updated_at: string
  roles?: Role[]
}

// 角色类型
export interface Role {
  id: number
  name: string
  display_name: string
  description: string | null
  sort_order: number
  status: number
  created_at: string
  updated_at: string
  permissions?: Permission[]
}

// 权限类型
export interface Permission {
  id: number
  name: string
  display_name: string
  type: 'menu' | 'button' | 'api'
  parent_id: number
  path: string | null
  api_path: string | null
  icon: string | null
  sort_order: number
  status: number
  created_at: string
  updated_at: string
  children?: Permission[]
}

// 登录响应类型
export interface LoginResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: {
    id: number
    username: string
    email: string
    avatar: string | null
  }
}

// 用户信息响应类型
export interface UserInfoResponse {
  id: number
  username: string
  email: string | null
  phone: string | null
  avatar: string | null
  roles: string[]
  permissions: string[]
  menus: Permission[]
}
```

### 7.7 Vite 配置

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
```

### 7.8 页面组件示例

#### 登录页面

```vue
<!-- src/views/login/index.vue -->
<template>
  <div class="login-container">
    <div class="login-box">
      <div class="login-header">
        <h1>AdminSystem</h1>
        <p>运营后台管理系统</p>
      </div>

      <el-form ref="loginFormRef" :model="loginForm" :rules="loginRules" @keyup.enter="handleLogin">
        <el-form-item prop="username">
          <el-input v-model="loginForm.username" placeholder="用户名" :prefix-icon="User" />
        </el-form-item>

        <el-form-item prop="password">
          <el-input v-model="loginForm.password" type="password" placeholder="密码" show-password :prefix-icon="Lock" />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" :loading="loading" class="login-btn" @click="handleLogin">
            登 录
          </el-button>
        </el-form-item>
      </el-form>

      <div class="login-footer">
        <p>默认账号: admin / Admin@123456</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { User, Lock } from '@element-plus/icons-vue'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const loginFormRef = ref<FormInstance>()
const loading = ref(false)

const loginForm = reactive({
  username: '',
  password: '',
})

const loginRules: FormRules = {
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码长度不能少于6位', trigger: 'blur' },
  ],
}

async function handleLogin() {
  if (!loginFormRef.value) return

  await loginFormRef.value.validate(async (valid) => {
    if (!valid) return

    loading.value = true
    try {
      await userStore.login(loginForm.username, loginForm.password)
      ElMessage.success('登录成功')
      const redirect = (route.query.redirect as string) || '/'
      router.push(redirect)
    } finally {
      loading.value = false
    }
  })
}
</script>
```

### 7.9 权限检查函数（旧版保留）

```javascript
// directives/permission.js

import { useUserStore } from '@/stores/user';

export const permission = {
    mounted(el, binding) {
        const userStore = useUserStore();
        const { value } = binding;

        if (value && typeof value === 'string') {
            const hasPermission = userStore.permissions.includes(value);

            if (!hasPermission) {
                el.parentNode?.removeChild(el);
            }
        }
    }
};

// 注册全局指令
// main.js
app.directive('permission', permission);

// 使用示例
// <el-button v-permission="'user:create'">新增用户</el-button>
// <el-button v-permission="'user:edit'">编辑</el-button>
// <el-button v-permission="'user:delete'">删除</el-button>
```

### 7.9 权限检查函数（旧版保留）

```javascript
// composables/usePermission.js

import { useUserStore } from '@/stores/user';

export function usePermission() {
    const userStore = useUserStore();

    /**
     * 检查是否拥有指定权限
     */
    const hasPermission = (permission) => {
        return userStore.permissions.includes(permission);
    };

    /**
     * 检查是否拥有任意一个权限
     */
    const hasAnyPermission = (permissions) => {
        return permissions.some(p => userStore.permissions.includes(p));
    };

    /**
     * 检查是否拥有所有权限
     */
    const hasAllPermissions = (permissions) => {
        return permissions.every(p => userStore.permissions.includes(p));
    };

    return {
        hasPermission,
        hasAnyPermission,
        hasAllPermissions
    };
}

// 使用示例
// const { hasPermission } = usePermission();
// if (hasPermission('user:create')) { ... }
```

---

## 八、安全设计

### 8.1 密码安全

- 使用 `bcrypt` 或 `password_hash()` 加密存储
- 密码强度要求：至少8位，包含字母和数字
- 登录失败次数限制（5次失败锁定30分钟）

### 8.2 Token 安全

- JWT Token 有效期：2小时
- Refresh Token 有效期：7天
- Token 存储在 Redis，支持主动失效

### 8.3 接口安全

- 所有接口强制 HTTPS
- 敏感操作二次验证
- 接口限流（防止暴力破解）
- SQL 注入防护（使用 PDO 预处理）
- XSS 防护（输出转义）

### 8.4 操作日志安全

- 敏感参数自动脱敏（密码、token 等）
- 日志数据定期归档（保留90天）
- 日志查询权限控制

---

## 九、初始化数据

### 9.1 默认角色

| 角色标识 | 角色名称 | 说明 |
|----------|----------|------|
| super_admin | 超级管理员 | 拥有所有权限 |
| admin | 管理员 | 拥有大部分权限 |
| operator | 运营人员 | 拥有业务操作权限 |

### 9.2 默认权限

```sql
-- 系统管理菜单
INSERT INTO t_permission (name, display_name, type, parent_id, path, icon, sort_order) VALUES
('system', '系统管理', 'menu', 0, '/system', 'Setting', 99),
('user', '用户管理', 'menu', 1, '/system/user', 'User', 1),
('role', '角色管理', 'menu', 1, '/system/role', 'UserFilled', 2),
('permission', '权限管理', 'menu', 1, '/system/permission', 'Lock', 3),
('log', '操作日志', 'menu', 1, '/system/log', 'Document', 4);

-- 用户管理按钮权限
INSERT INTO t_permission (name, display_name, type, parent_id) VALUES
('user:list', '用户列表', 'button', 2),
('user:create', '新增用户', 'button', 2),
('user:edit', '编辑用户', 'button', 2),
('user:delete', '删除用户', 'button', 2);

-- 角色管理按钮权限
INSERT INTO t_permission (name, display_name, type, parent_id) VALUES
('role:list', '角色列表', 'button', 3),
('role:create', '新增角色', 'button', 3),
('role:edit', '编辑角色', 'button', 3),
('role:delete', '删除角色', 'button', 3);

-- 操作日志按钮权限
INSERT INTO t_permission (name, display_name, type, parent_id) VALUES
('log:list', '日志列表', 'button', 5),
('log:detail', '日志详情', 'button', 5),
('log:delete', '删除日志', 'button', 5),
('log:export', '导出日志', 'button', 5);
```

### 9.3 默认管理员

```sql
-- 超级管理员账号
INSERT INTO t_user (username, password, email, status) VALUES
('admin', '$2y$10$...加密密码...', 'admin@example.com', 1);

-- 关联超级管理员角色
INSERT INTO t_user_role (user_id, role_id) VALUES (1, 1);
```

---

## 十、项目实施计划

### 第一阶段：基础框架搭建

- 项目初始化与目录结构
- 数据库设计与创建
- 基础模型与控制器

### 第二阶段：认证模块

- 登录/登出功能
- JWT Token 实现
- 登录日志记录

### 第三阶段：权限模块

- 权限管理 CRUD
- 角色管理 CRUD
- 用户管理 CRUD
- 权限分配功能

### 第四阶段：操作日志模块

- 操作日志中间件
- 日志列表查询
- 日志详情查看
- 日志导出功能
- 日志清理任务

### 第五阶段：前端开发

- 登录页面
- 布局框架
- 用户/角色/权限管理页面
- 操作日志页面
- 权限指令与组件

### 第六阶段：测试与优化

- 功能测试
- 安全测试
- 性能优化

---

## 十一、项目部署

### 11.1 后端部署

```bash
# 克隆项目
git clone <repository-url>
cd admin-system

# 安装依赖
composer install --optimize-autoloader --no-dev

# 环境配置
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 配置数据库连接 (编辑 .env)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_system
DB_USERNAME=root
DB_PASSWORD=your_password

# 执行迁移和填充
php artisan migrate --force
php artisan db:seed --force

# 优化
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 设置权限
chmod -R 775 storage bootstrap/cache
```

### 11.2 前端部署

```bash
# 进入前端目录
cd frontend

# 安装依赖
npm install

# 构建生产版本
npm run build

# 部署到 Web 服务器
# 将 dist 目录内容部署到 Nginx/Apache
```

### 11.3 Nginx 配置示例

```nginx
server {
    listen 80;
    server_name admin.example.com;
    root /var/www/admin-system/public;

    index index.php;

    # 后端 API
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 前端静态资源
    location / {
        root /var/www/admin-system/frontend/dist;
        try_files $uri $uri/ /index.html;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 禁止访问隐藏文件
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 十二、后续扩展

1. **数据权限** - 按部门/组织过滤数据
2. **审计追踪** - 数据变更详细记录
3. **多因素认证** - 支持 TOTP 动态码
4. **SSO 单点登录** - 对接企业统一认证
5. **日志分析报表** - 操作行为分析与可视化

---

## 十三、项目运行

### 13.1 开发环境

```bash
# 后端
cd admin-system
php artisan serve --host=0.0.0.0 --port=8000

# 前端
cd admin-system/frontend
npm run dev -- --host 0.0.0.0 --port 3000
```

### 13.2 访问地址

- 前端页面: http://127.0.0.1:3000
- 后端 API: http://127.0.0.1:8000/api

### 13.3 默认账号

- 用户名: `admin`
- 密码: `Admin@123456`

---

*文档版本: v1.2*
*创建日期: 2026-03-02*
*更新日期: 2026-03-02*
