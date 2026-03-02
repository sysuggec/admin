<?php

namespace App\Http\Middleware;

use App\Models\OperationLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OperationLogMiddleware
{
    /**
     * 需要记录日志的方法
     */
    protected array $logMethods = ['POST', 'PUT', 'DELETE'];

    /**
     * 不需要记录日志的路由
     */
    protected array $exceptRoutes = [
        'api/auth/login',
        'api/auth/logout',
        'api/auth/refresh',
        'api/operation-logs',
    ];

    /**
     * 模块映射
     */
    protected array $moduleMap = [
        'users' => '用户管理',
        'roles' => '角色管理',
        'permissions' => '权限管理',
        'operation-logs' => '操作日志',
    ];

    /**
     * 操作类型映射
     */
    protected array $actionMap = [
        'POST' => 'create',
        'PUT' => 'update',
        'DELETE' => 'delete',
    ];

    /**
     * 操作名称映射
     */
    protected array $actionNameMap = [
        'create' => '新增',
        'update' => '编辑',
        'delete' => '删除',
        'status' => '切换状态',
        'assign_permission' => '分配权限',
    ];

    /**
     * 处理请求
     */
    public function handle(Request $request, Closure $next): Response
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
    protected function shouldLog(Request $request): bool
    {
        // 只记录指定方法
        if (!in_array($request->method(), $this->logMethods)) {
            return false;
        }

        // 排除指定路由
        $path = $request->path();
        foreach ($this->exceptRoutes as $except) {
            if (str_contains($path, $except)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 记录操作日志
     */
    protected function logOperation(Request $request, Response $response, float $startTime): void
    {
        $user = auth('api')->user();
        $duration = round((microtime(true) - $startTime) * 1000);

        $data = [
            'user_id' => $user?->id ?? 0,
            'username' => $user?->username ?? 'unknown',
            'module' => $this->getModule($request),
            'action' => $this->getAction($request),
            'title' => $this->getTitle($request),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'params' => json_encode($this->filterSensitiveParams($request->all())),
            'response' => $response->getContent(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $response->getStatusCode() === 200 ? 1 : 0,
            'error_msg' => $response->getStatusCode() !== 200 ? $response->getContent() : null,
            'duration' => (int) $duration,
            'created_at' => now(),
        ];

        OperationLog::create($data);
    }

    /**
     * 获取操作模块
     */
    protected function getModule(Request $request): string
    {
        $path = $request->path();
        $segments = explode('/', $path);
        $moduleKey = $segments[1] ?? '';

        return $this->moduleMap[$moduleKey] ?? '其他';
    }

    /**
     * 获取操作类型
     */
    protected function getAction(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        // 特殊操作判断
        if (str_contains($path, '/status')) {
            return 'status';
        }
        if (str_contains($path, '/permissions') && $method === 'PUT') {
            return 'assign_permission';
        }

        return $this->actionMap[$method] ?? 'unknown';
    }

    /**
     * 获取操作标题
     */
    protected function getTitle(Request $request): string
    {
        $module = $this->getModule($request);
        $action = $this->getAction($request);
        $actionName = $this->actionNameMap[$action] ?? '未知操作';

        return $module . $actionName;
    }

    /**
     * 过滤敏感参数
     */
    protected function filterSensitiveParams(array $params): array
    {
        $sensitiveKeys = ['password', 'password_confirm', 'old_password', 'token', 'new_password'];

        foreach ($sensitiveKeys as $key) {
            if (isset($params[$key])) {
                $params[$key] = '******';
            }
        }

        return $params;
    }
}
