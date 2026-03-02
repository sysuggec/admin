<?php

namespace App\Http\Controllers;

use App\Http\Middleware\PermissionMiddleware;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * 角色列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');

        // 角色名称筛选
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        // 显示名称筛选
        if ($request->filled('display_name')) {
            $query->where('display_name', 'like', "%{$request->display_name}%");
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 20));

        return $this->paginate($paginator);
    }

    /**
     * 获取所有启用的角色(下拉选择用)
     */
    public function all(): JsonResponse
    {
        $roles = Role::where('status', 1)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'display_name']);

        return $this->success($roles);
    }

    /**
     * 创建角色
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:t_role,name',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer|in:0,1',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:t_permission,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 1,
        ]);

        // 分配权限
        if (!empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
        ], '创建成功');
    }

    /**
     * 角色详情
     */
    public function show(int $id): JsonResponse
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        return $this->success($role);
    }

    /**
     * 更新角色
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        // 超级管理员角色不能修改
        if ($role->name === 'super_admin') {
            return $this->error('超级管理员角色不能修改', 400);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:50|unique:t_role,name,' . $id,
            'display_name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'sometimes|required|integer|in:0,1',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:t_permission,id',
        ]);

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['display_name'])) {
            $updateData['display_name'] = $validated['display_name'];
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['sort_order'])) {
            $updateData['sort_order'] = $validated['sort_order'];
        }
        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if (!empty($updateData)) {
            $role->update($updateData);
        }

        // 更新权限
        if (isset($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
            // 清除该角色下所有用户的权限缓存
            $this->clearRoleUsersPermissionCache($role);
        }

        return $this->success(null, '更新成功');
    }

    /**
     * 删除角色
     */
    public function destroy(int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        // 超级管理员角色不能删除
        if ($role->name === 'super_admin') {
            return $this->error('超级管理员角色不能删除', 400);
        }

        // 检查是否有用户使用该角色
        if ($role->users()->exists()) {
            return $this->error('该角色下存在用户，无法删除', 400);
        }

        $role->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 获取角色权限
     */
    public function permissions(int $id): JsonResponse
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $permissionIds = $role->permissions->pluck('id')->toArray();

        return $this->success($permissionIds);
    }

    /**
     * 设置角色权限
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        // 超级管理员角色不能修改权限
        if ($role->name === 'super_admin') {
            return $this->error('超级管理员角色不能修改权限', 400);
        }

        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'integer|exists:t_permission,id',
        ]);

        $role->permissions()->sync($validated['permission_ids']);

        // 清除该角色下所有用户的权限缓存
        $this->clearRoleUsersPermissionCache($role);

        return $this->success(null, '权限设置成功');
    }

    /**
     * 清除角色下所有用户的权限缓存
     */
    private function clearRoleUsersPermissionCache(Role $role): void
    {
        $userIds = $role->users()->pluck('id')->toArray();
        foreach ($userIds as $userId) {
            PermissionMiddleware::clearUserPermissionCache($userId);
        }
    }
}
