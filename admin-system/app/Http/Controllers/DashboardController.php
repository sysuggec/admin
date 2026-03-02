<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\OperationLog;

class DashboardController extends Controller
{
    /**
     * 获取仪表盘统计数据
     */
    public function stats()
    {
        $stats = [
            'userCount' => User::count(),
            'roleCount' => Role::count(),
            'permissionCount' => Permission::count(),
            'logCount' => OperationLog::count(),
        ];

        return $this->success($stats);
    }
}
