<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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

        // 给超级管理员角色分配所有权限
        $permissions = DB::table('t_permission')->pluck('id');
        foreach ($permissions as $permissionId) {
            DB::table('t_role_permission')->insert([
                'role_id' => 1,
                'permission_id' => $permissionId,
                'created_at' => $now,
            ]);
        }
    }
}
