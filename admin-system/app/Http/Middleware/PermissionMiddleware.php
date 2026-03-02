<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * 处理请求
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '未登录',
                'data' => null,
            ], 401);
        }

        // 超级管理员跳过权限检查
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // 如果没有指定权限，尝试从路由获取
        if (!$permission) {
            $permission = $this->getPermissionFromRoute($request);
        }

        if (!$permission) {
            return $next($request);
        }

        // 检查用户是否拥有该权限
        $userPermissions = $this->getUserPermissions($user->id);

        if (!in_array($permission, $userPermissions)) {
            return response()->json([
                'code' => 403,
                'message' => '无权限访问',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }

    /**
     * 从路由获取权限标识
     */
    private function getPermissionFromRoute(Request $request): ?string
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        // 尝试从路由参数获取
        $permission = $route->parameter('permission');
        if ($permission) {
            return $permission;
        }

        // 尝试从路由 action 获取
        $action = $route->getActionName();
        if ($action) {
            // 根据 action 推断权限
            $method = $request->method();
            $permissionMap = [
                'GET' => 'list',
                'POST' => 'create',
                'PUT' => 'edit',
                'PATCH' => 'edit',
                'DELETE' => 'delete',
            ];

            $actionType = $permissionMap[$method] ?? null;
            if ($actionType) {
                // 从路由 URI 推断模块名
                $uri = $route->uri();
                if (preg_match('#api/(\w+)#', $uri, $matches)) {
                    return $matches[1] . ':' . $actionType;
                }
            }
        }

        return null;
    }

    /**
     * 获取用户所有权限(带缓存)
     */
    private function getUserPermissions(int $userId): array
    {
        $cacheKey = "user:permissions:{$userId}";

        return Cache::remember($cacheKey, 3600, function () use ($userId) {
            $user = \App\Models\User::with('roles.permissions')->find($userId);
            return $user ? $user->getPermissions() : [];
        });
    }

    /**
     * 清除用户权限缓存
     */
    public static function clearUserPermissionCache(int $userId): void
    {
        Cache::forget("user:permissions:{$userId}");
    }
}
