<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    /**
     * 获取登录日志列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = LoginLog::query();

        // 用户名搜索
        if ($request->filled('username')) {
            $query->where('username', 'like', '%' . $request->username . '%');
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 时间范围
        if ($request->filled('start_time')) {
            $query->where('login_time', '>=', $request->start_time . ' 00:00:00');
        }
        if ($request->filled('end_time')) {
            $query->where('login_time', '<=', $request->end_time . ' 23:59:59');
        }

        // 排序和分页
        $query->orderBy('id', 'desc');

        $pageSize = $request->input('page_size', 20);
        $data = $query->paginate($pageSize);

        return $this->success([
            'list' => $data->items(),
            'total' => $data->total(),
            'page' => $data->currentPage(),
            'page_size' => $data->perPage(),
        ]);
    }

    /**
     * 获取登录日志详情
     */
    public function show(int $id): JsonResponse
    {
        $log = LoginLog::find($id);

        if (!$log) {
            return $this->error('日志不存在', 404);
        }

        return $this->success($log);
    }

    /**
     * 删除登录日志
     */
    public function destroy(int $id): JsonResponse
    {
        $log = LoginLog::find($id);

        if (!$log) {
            return $this->error('日志不存在', 404);
        }

        $log->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 批量删除登录日志
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return $this->error('请选择要删除的日志');
        }

        LoginLog::whereIn('id', $ids)->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 清理指定天数前的日志
     */
    public function clean(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);

        if ($days < 1) {
            return $this->error('天数必须大于0');
        }

        $count = LoginLog::where('login_time', '<', now()->subDays($days))->delete();

        return $this->success(['deleted_count' => $count], "已清理 {$count} 条日志");
    }

    /**
     * 导出登录日志
     */
    public function export(Request $request): JsonResponse
    {
        $query = LoginLog::query();

        // 用户名搜索
        if ($request->filled('username')) {
            $query->where('username', 'like', '%' . $request->username . '%');
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 时间范围
        if ($request->filled('start_time')) {
            $query->where('login_time', '>=', $request->start_time . ' 00:00:00');
        }
        if ($request->filled('end_time')) {
            $query->where('login_time', '<=', $request->end_time . ' 23:59:59');
        }

        $data = $query->orderBy('id', 'desc')->limit(10000)->get();

        $exportData = $data->map(function ($item) {
            return [
                'ID' => $item->id,
                '用户ID' => $item->user_id,
                '用户名' => $item->username,
                'IP地址' => $item->ip,
                '浏览器' => $item->user_agent,
                '登录时间' => $item->login_time?->format('Y-m-d H:i:s'),
                '状态' => $item->status === 1 ? '成功' : '失败',
                '消息' => $item->message,
            ];
        });

        return $this->success($exportData);
    }
}
