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

## 快速启动命令

```bash
# 启动服务
./scripts/start-dev.sh

# 或手动启动
cd /workspace/admin-system
setsid php artisan serve --host=0.0.0.0 --port=8000 &
cd frontend && setsid npx vite --host 0.0.0.0 --port 3000 &

# 查看服务状态
ps aux | grep -E "(php artisan|vite)" | grep -v grep

# 停止服务
pkill -f "php artisan serve"
pkill -f vite
```
