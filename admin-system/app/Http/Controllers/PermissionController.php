<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * 权限列表(树形)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        // 权限名称筛选
        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        // 显示名称筛选
        if ($request->filled('display_name')) {
            $query->where('display_name', 'like', "%{$request->display_name}%");
        }

        // 类型筛选
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $permissions = $query->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        // 是否返回树形结构
        if ($request->input('tree', true)) {
            $tree = Permission::buildTree($permissions->toArray());
            return $this->success($tree);
        }

        return $this->success($permissions);
    }

    /**
     * 获取所有启用的权限(树形，用于分配权限)
     */
    public function all(): JsonResponse
    {
        $permissions = Permission::where('status', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();

        $tree = Permission::buildTree($permissions);

        return $this->success($tree);
    }

    /**
     * 创建权限
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:t_permission,name',
            'display_name' => 'required|string|max:100',
            'type' => 'required|in:menu,button,api',
            'parent_id' => 'nullable|integer|exists:t_permission,id',
            'path' => 'nullable|string|max:255',
            'api_path' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'] ?? 0,
            'path' => $validated['path'] ?? null,
            'api_path' => $validated['api_path'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 1,
        ]);

        return $this->success([
            'id' => $permission->id,
            'name' => $permission->name,
        ], '创建成功');
    }

    /**
     * 权限详情
     */
    public function show(int $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->error('权限不存在', 404);
        }

        return $this->success($permission);
    }

    /**
     * 更新权限
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->error('权限不存在', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:t_permission,name,' . $id,
            'display_name' => 'sometimes|required|string|max:100',
            'type' => 'sometimes|required|in:menu,button,api',
            'parent_id' => 'nullable|integer|exists:t_permission,id',
            'path' => 'nullable|string|max:255',
            'api_path' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'status' => 'sometimes|required|integer|in:0,1',
        ]);

        // 不能将自己设为父级
        if (isset($validated['parent_id']) && $validated['parent_id'] == $id) {
            return $this->error('不能将自己设为父级', 400);
        }

        $permission->update($validated);

        return $this->success(null, '更新成功');
    }

    /**
     * 删除权限
     */
    public function destroy(int $id): JsonResponse
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->error('权限不存在', 404);
        }

        // 检查是否有子权限
        $childrenCount = Permission::where('parent_id', $id)->count();
        if ($childrenCount > 0) {
            return $this->error('该权限下存在子权限，无法删除', 400);
        }

        // 检查是否有角色使用该权限
        if ($permission->roles()->exists()) {
            return $this->error('该权限已被角色使用，无法删除', 400);
        }

        $permission->delete();

        return $this->success(null, '删除成功');
    }
}
