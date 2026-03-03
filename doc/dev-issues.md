# 开发问题记录

## 问题1: 登录成功但停留在登录页

### 现象
用户在网页登录时提示"登录成功"，但页面仍然停留在登录页，没有跳转到首页。

### 原因分析
前端 `stores/user.ts` 中对 API 响应数据结构的处理有误。

API 返回结构：
```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "access_token": "...",
    ...
  }
}
```

axios 响应拦截器返回的是 `response` 对象，所以 `const { data }` 解构出来的是整个 API 响应体 `{ code, message, data }`，而不是真正的业务数据。

原代码：
```typescript
const { data } = await loginApi({ username, password })
token.value = data.access_token  // 错误：应该是 data.data.access_token
```

### 解决方案
修改 `frontend/src/stores/user.ts`：

```typescript
// 登录
async function login(username: string, password: string) {
  const { data } = await loginApi({ username, password })
  token.value = data.data.access_token
  localStorage.setItem('token', data.data.access_token)
  return data.data
}

// 获取用户信息
async function fetchUserInfo() {
  const { data } = await getUserInfoApi()
  userInfo.value = data.data
  return data.data
}
```

---

## 问题2: 开发服务频繁异常停止

### 现象
后端 (php artisan serve) 和前端 (vite) 服务在启动后约 2 分钟自动停止，导致 API 请求失败，前端显示"服务器错误"。

### 排查过程
1. 检查 OOM Killer：`dmesg | grep oom` - 无相关记录
2. 检查内存：`free -h` - 内存充足 (231GB 可用)
3. 检查进程状态：后台任务 `Duration: 2m 0s` - 发现正好在 2 分钟时停止

### 原因分析
使用 `run_in_background` 或普通 `&` 启动的进程，会作为当前 shell 会话的子进程。当 shell 会话结束或超时时，子进程会被终止。

### 解决方案
使用 `setsid` 创建新的会话 (session)，使进程独立于当前 shell：

```bash
# 启动后端
setsid php artisan serve --host=0.0.0.0 --port=8000 &

# 启动前端
cd frontend && setsid npx vite --host 0.0.0.0 --port 3000 &
```

已更新启动脚本 `scripts/start-dev.sh`，使用 `setsid` 启动服务。

---

## 问题3: 点击菜单导航报错

### 现象
点击导航栏菜单时，浏览器控制台报错：
```
Vue warn]: Unhandled error during execution of component update
```

### 原因分析
各页面组件（用户管理、角色管理、权限管理、操作日志）中 API 响应数据处理方式不一致，部分使用 `data as any` 而非正确的 `data.data` 结构。

### 解决方案
修复所有页面组件中的 API 响应处理：

**修复前：**
```typescript
tableData.value = (data as any).list
```

**修复后：**
```typescript
tableData.value = data.data.list
```

涉及文件：
- `views/system/user/index.vue`
- `views/system/role/index.vue`
- `views/system/permission/index.vue`
- `views/system/log/index.vue`

---

## 问题4: 下拉框选择后看不到文字

### 现象
搜索表单中的 `el-select` 下拉框，选择选项后输入框内看不到选中的文字。

### 原因分析
搜索表单中的下拉框没有设置固定宽度，Element Plus 默认宽度太窄，导致选中的文字被截断无法显示。

通过浏览器开发者工具检查：
- `span` 元素的 `color: #606266`（正常）
- `font-size: 14px`（正常）
- `opacity: 1`（正常）

问题出在下拉框容器宽度不够，文字虽然存在但被截断。

### 解决方案
在全局样式文件 `frontend/src/styles/index.css` 中添加搜索表单下拉框的默认宽度：

```css
/* 搜索表单中的下拉框默认宽度 */
.search-form .el-select {
  width: 180px;
}
```

同时优化了启动脚本，确保后台进程稳定运行：

```bash
# 启动后端 (使用 setsid 创建新会话，避免进程被终止)
setsid php artisan serve --host=0.0.0.0 --port=8000 </dev/null >/dev/null 2>&1 &

# 启动前端 (需要重定向 stdin 到 /dev/null)
cd frontend && setsid npx vite --host 0.0.0.0 --port 3000 </dev/null >/dev/null 2>&1 &
```

---

## 问题5: 仪表盘统计数据与实际不符

### 现象
仪表盘页面显示的用户总数和操作日志数与数据库实际数据不一致。

### 原因分析
仪表盘页面的统计数据是写死的固定值，没有从后端 API 获取真实数据：

```typescript
onMounted(() => {
  // 这里可以调用 API 获取统计数据
  stats.value = {
    userCount: 1,    // 写死的值
    roleCount: 3,
    permissionCount: 21,
    logCount: 1,     // 写死的值
  }
})
```

### 解决方案

**1. 后端新增统计接口**

创建 `app/Http/Controllers/DashboardController.php`：

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\OperationLog;

class DashboardController extends Controller
{
    public function stats()
    {
        $stats = [
            'userCount' => User::count(),
            'roleCount' => Role::count(),
            'permissionCount' => Permission::count(),
            'logCount' => OperationLog::count(),
        ];

        return $this->success($stats);
    }
}
```

**2. 添加路由**

在 `routes/api.php` 中添加：

```php
Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
```

**3. 前端新增 API 模块**

创建 `frontend/src/api/dashboard.ts`：

```typescript
import request from './request'

export function getDashboardStats() {
  return request.get('/dashboard/stats')
}
```

**4. 修改仪表盘页面**

修改 `frontend/src/views/dashboard/index.vue`，从 API 获取数据：

```typescript
import { getDashboardStats } from '@/api/dashboard'

async function fetchStats() {
  try {
    const { data } = await getDashboardStats()
    stats.value = data.data
  } catch {
    // 错误已在拦截器中处理
  }
}

onMounted(() => {
  fetchStats()
})
```

---

## 问题6: Token 过期时多次弹出登录过期提示

### 现象
当 JWT Token 过期后，页面会连续弹出多个"登录已过期，请重新登录"的提示消息。

### 原因分析
页面加载时会同时发起多个 API 请求（如获取用户信息、菜单、统计数据等）。当 Token 过期时，这些并发请求都会返回 401 错误，响应拦截器对每个 401 错误都进行了处理，导致多次弹出提示消息。

原代码：
```typescript
case 401:
  // Token 过期或未登录
  const userStore = useUserStore()
  userStore.logout()
  router.push('/login')
  ElMessage.error('登录已过期，请重新登录')  // 每个请求都会触发
  break
```

### 解决方案
添加 `isHandling401` 标志位，确保只处理第一次 401 错误，后续的 401 错误会被忽略：

```typescript
// 是否正在处理 401 错误（防止多次弹出提示）
let isHandling401 = false

// 响应拦截器中
case 401:
  // Token 过期或未登录（只处理一次）
  if (!isHandling401) {
    isHandling401 = true
    const userStore = useUserStore()
    userStore.logout()
    ElMessage.error('登录已过期，请重新登录')
    router.push('/login')
    // 延迟重置标志，防止短时间内重复处理
    setTimeout(() => {
      isHandling401 = false
    }, 1000)
  }
  break
```

修改文件：`frontend/src/api/request.ts`

---

## 快速启动命令

```bash
# 启动服务（合并部署，持久运行）
cd /workspace/admin-system
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel.log 2>&1 &

# 停止服务
pkill -f "php artisan serve"

# 查看服务状态
ps aux | grep "php artisan serve" | grep -v grep
```

---

## 问题7: 动态菜单不显示

### 现象
API `/api/auth/me` 返回了完整的菜单数据（包含登录日志菜单），但前端导航栏没有显示对应的菜单入口。更换浏览器测试也无效，排除缓存问题。

### 原因分析
`frontend/src/layouts/MainLayout.vue` 中的菜单是硬编码的，没有使用 API 返回的动态菜单数据：

**原代码：**
```vue
<el-sub-menu index="system">
  <template #title>
    <el-icon><Setting /></el-icon>
    <span>系统管理</span>
  </template>
  <el-menu-item index="/system/user">
    <el-icon><User /></el-icon>
    <template #title>用户管理</template>
  </el-menu-item>
  <!-- 硬编码的菜单项，无法动态更新 -->
</el-sub-menu>
```

虽然 Store 中已有 `menus` 计算属性，但布局组件没有使用它来渲染菜单。

### 解决方案
修改 `MainLayout.vue`，使用 `userStore.menus` 动态渲染菜单：

```vue
<template>
  <!-- 动态菜单渲染 -->
  <template v-for="menu in userStore.menus" :key="menu.id">
    <!-- 有子菜单的情况 -->
    <el-sub-menu v-if="menu.children && menu.children.length > 0" :index="String(menu.id)">
      <template #title>
        <el-icon><component :is="getIcon(menu.icon)" /></el-icon>
        <span>{{ menu.display_name }}</span>
      </template>
      <el-menu-item
        v-for="child in menu.children"
        :key="child.id"
        :index="child.path || ''"
      >
        <el-icon><component :is="getIcon(child.icon)" /></el-icon>
        <template #title>{{ child.display_name }}</template>
      </el-menu-item>
    </el-sub-menu>
    <!-- 没有子菜单的情况 -->
    <el-menu-item v-else :index="menu.path || ''">
      <el-icon><component :is="getIcon(menu.icon)" /></el-icon>
      <template #title>{{ menu.display_name }}</template>
    </el-menu-item>
  </template>
</template>

<script setup lang="ts">
// 图标映射表
const iconMap: Record<string, Component> = {
  Setting,
  User,
  UserFilled,
  Lock,
  Document,
  Odometer,
  Postcard,
}

// 根据图标名称获取图标组件
function getIcon(iconName: string | null) {
  if (!iconName) return Document
  return iconMap[iconName] || Document
}
</script>
```

修改文件：`frontend/src/layouts/MainLayout.vue`

### 添加新菜单步骤
1. 在数据库添加权限记录 (type: `menu`)
2. 将权限分配给角色
3. 在 `router/index.ts` 添加路由
4. 如使用新图标，在 `MainLayout.vue` 的 `iconMap` 中添加映射

---

## 问题8: 服务启动后自动关闭

### 现象
使用 `php artisan serve` 启动后端服务，约 2 分钟后服务自动关闭，无法访问。

### 原因分析
后台 Bash 任务有 2 分钟超时限制，超时后进程会被自动终止。普通的 `&` 后台运行方式仍然受 shell 会话控制。

### 解决方案
使用 `nohup` 命令使进程独立运行：

```bash
# 启动服务（持久运行）
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel.log 2>&1 &

# 停止服务
pkill -f "php artisan serve"
```

已更新文档 `CODEBUDDY.md` 和 `README.md` 中的启动命令。
