<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // 系统管理菜单
        $permissions = [
            // 系统管理
            ['id' => 1, 'name' => 'system', 'display_name' => '系统管理', 'type' => 'menu', 'parent_id' => 0, 'path' => '/system', 'icon' => 'Setting', 'sort_order' => 99],
            // 用户管理
            ['id' => 2, 'name' => 'user', 'display_name' => '用户管理', 'type' => 'menu', 'parent_id' => 1, 'path' => '/system/user', 'icon' => 'User', 'sort_order' => 1],
            ['id' => 3, 'name' => 'user:list', 'display_name' => '用户列表', 'type' => 'button', 'parent_id' => 2, 'sort_order' => 1],
            ['id' => 4, 'name' => 'user:create', 'display_name' => '新增用户', 'type' => 'button', 'parent_id' => 2, 'sort_order' => 2],
            ['id' => 5, 'name' => 'user:edit', 'display_name' => '编辑用户', 'type' => 'button', 'parent_id' => 2, 'sort_order' => 3],
            ['id' => 6, 'name' => 'user:delete', 'display_name' => '删除用户', 'type' => 'button', 'parent_id' => 2, 'sort_order' => 4],
            // 角色管理
            ['id' => 7, 'name' => 'role', 'display_name' => '角色管理', 'type' => 'menu', 'parent_id' => 1, 'path' => '/system/role', 'icon' => 'UserFilled', 'sort_order' => 2],
            ['id' => 8, 'name' => 'role:list', 'display_name' => '角色列表', 'type' => 'button', 'parent_id' => 7, 'sort_order' => 1],
            ['id' => 9, 'name' => 'role:create', 'display_name' => '新增角色', 'type' => 'button', 'parent_id' => 7, 'sort_order' => 2],
            ['id' => 10, 'name' => 'role:edit', 'display_name' => '编辑角色', 'type' => 'button', 'parent_id' => 7, 'sort_order' => 3],
            ['id' => 11, 'name' => 'role:delete', 'display_name' => '删除角色', 'type' => 'button', 'parent_id' => 7, 'sort_order' => 4],
            // 权限管理
            ['id' => 12, 'name' => 'permission', 'display_name' => '权限管理', 'type' => 'menu', 'parent_id' => 1, 'path' => '/system/permission', 'icon' => 'Lock', 'sort_order' => 3],
            ['id' => 13, 'name' => 'permission:list', 'display_name' => '权限列表', 'type' => 'button', 'parent_id' => 12, 'sort_order' => 1],
            ['id' => 14, 'name' => 'permission:create', 'display_name' => '新增权限', 'type' => 'button', 'parent_id' => 12, 'sort_order' => 2],
            ['id' => 15, 'name' => 'permission:edit', 'display_name' => '编辑权限', 'type' => 'button', 'parent_id' => 12, 'sort_order' => 3],
            ['id' => 16, 'name' => 'permission:delete', 'display_name' => '删除权限', 'type' => 'button', 'parent_id' => 12, 'sort_order' => 4],
            // 操作日志
            ['id' => 17, 'name' => 'log', 'display_name' => '操作日志', 'type' => 'menu', 'parent_id' => 1, 'path' => '/system/log', 'icon' => 'Document', 'sort_order' => 4],
            ['id' => 18, 'name' => 'log:list', 'display_name' => '日志列表', 'type' => 'button', 'parent_id' => 17, 'sort_order' => 1],
            ['id' => 19, 'name' => 'log:detail', 'display_name' => '日志详情', 'type' => 'button', 'parent_id' => 17, 'sort_order' => 2],
            ['id' => 20, 'name' => 'log:delete', 'display_name' => '删除日志', 'type' => 'button', 'parent_id' => 17, 'sort_order' => 3],
            ['id' => 21, 'name' => 'log:export', 'display_name' => '导出日志', 'type' => 'button', 'parent_id' => 17, 'sort_order' => 4],
        ];

        foreach ($permissions as $permission) {
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
            $permission['status'] = 1;
            DB::table('t_permission')->insert($permission);
        }
    }
}
