<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * 用户登录
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $credentials['username'])->first();

        // 记录登录日志
        $logData = [
            'user_id' => $user?->id ?? 0,
            'username' => $credentials['username'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_time' => now(),
        ];

        if (!$user) {
            $logData['status'] = 0;
            $logData['message'] = '用户不存在';
            LoginLog::create($logData);

            return $this->error('用户名或密码错误', 401);
        }

        if ($user->status != 1) {
            $logData['status'] = 0;
            $logData['message'] = '账号已被禁用';
            LoginLog::create($logData);

            return $this->error('账号已被禁用', 401);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            $logData['status'] = 0;
            $logData['message'] = '密码错误';
            LoginLog::create($logData);

            return $this->error('用户名或密码错误', 401);
        }

        // 生成 Token
        $token = auth('api')->login($user);

        $logData['status'] = 1;
        $logData['message'] = '登录成功';
        LoginLog::create($logData);

        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ],
        ], '登录成功');
    }

    /**
     * 用户登出
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return $this->success(null, '登出成功');
    }

    /**
     * 刷新 Token
     */
    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();

        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ]);
    }

    /**
     * 获取当前用户信息
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        // 获取用户权限
        $permissions = $user->getPermissions();

        // 获取菜单树
        $menuTree = $this->getUserMenuTree($permissions);

        return $this->success([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $permissions,
            'menus' => $menuTree,
        ]);
    }

    /**
     * 获取用户菜单树
     */
    private function getUserMenuTree(array $permissions): array
    {
        $menus = \App\Models\Permission::whereIn('name', $permissions)
            ->where('type', 'menu')
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        return \App\Models\Permission::buildTree($menus);
    }
}
