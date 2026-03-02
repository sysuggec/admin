<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('t_role')->insert([
            [
                'name' => 'super_admin',
                'display_name' => '超级管理员',
                'description' => '拥有所有权限',
                'sort_order' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'admin',
                'display_name' => '管理员',
                'description' => '拥有大部分权限',
                'sort_order' => 2,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'operator',
                'display_name' => '运营人员',
                'description' => '拥有业务操作权限',
                'sort_order' => 3,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
