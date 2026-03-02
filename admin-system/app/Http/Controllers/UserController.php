<?php

namespace App\Http\Controllers;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * 用户列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');

        // 用户名筛选
        if ($request->filled('username')) {
            $query->where('username', 'like', "%{$request->username}%");
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 邮箱筛选
        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        // 手机号筛选
        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%");
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 20));

        // 隐藏密码字段
        $paginator->getCollection()->makeHidden(['password']);

        return $this->paginate($paginator);
    }

    /**
     * 创建用户
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:t_user,username',
            'password' => 'required|string|min:6|max:50',
            'email' => 'nullable|email|max:100|unique:t_user,email',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
            'status' => 'nullable|integer|in:0,1',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:t_role,id',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'avatar' => $validated['avatar'] ?? null,
            'status' => $validated['status'] ?? 1,
        ]);

        // 分配角色
        if (!empty($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        // 清除权限缓存
        PermissionMiddleware::clearUserPermissionCache($user->id);

        return $this->success([
            'id' => $user->id,
            'username' => $user->username,
        ], '创建成功');
    }

    /**
     * 用户详情
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('roles')->find($id);

        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $user->makeHidden(['password']);

        return $this->success($user);
    }

    /**
     * 更新用户
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $validated = $request->validate([
            'username' => 'sometimes|required|string|max:50|unique:t_user,username,' . $id,
            'password' => 'nullable|string|min:6|max:50',
            'email' => 'nullable|email|max:100|unique:t_user,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string|max:255',
            'status' => 'sometimes|required|integer|in:0,1',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:t_role,id',
        ]);

        $updateData = [];

        if (isset($validated['username'])) {
            $updateData['username'] = $validated['username'];
        }
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }
        if (isset($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }
        if (isset($validated['phone'])) {
            $updateData['phone'] = $validated['phone'];
        }
        if (isset($validated['avatar'])) {
            $updateData['avatar'] = $validated['avatar'];
        }
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        // 更新角色
        if (isset($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        // 清除权限缓存
        PermissionMiddleware::clearUserPermissionCache($user->id);

        return $this->success(null, '更新成功');
    }

    /**
     * 删除用户
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // 不能删除自己
        if ($user->id === auth('api')->id()) {
            return $this->error('不能删除自己', 400);
        }

        $user->delete();

        // 清除权限缓存
        PermissionMiddleware::clearUserPermissionCache($user->id);

        return $this->success(null, '删除成功');
    }

    /**
     * 切换用户状态
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // 不能禁用自己
        if ($user->id === auth('api')->id()) {
            return $this->error('不能禁用自己', 400);
        }

        $user->status = $user->status === 1 ? 0 : 1;
        $user->save();

        // 清除权限缓存
        PermissionMiddleware::clearUserPermissionCache($user->id);

        return $this->success([
            'status' => $user->status,
        ], '状态切换成功');
    }

    /**
     * 修改密码
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|max:50|different:old_password',
        ]);

        $user = auth('api')->user();

        if (!Hash::check($validated['old_password'], $user->password)) {
            return $this->error('原密码错误', 400);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return $this->success(null, '密码修改成功');
    }
}
