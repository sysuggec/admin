<?php

namespace App\Http\Controllers;

use App\Models\OperationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperationLogController extends Controller
{
    /**
     * 操作日志列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = OperationLog::query();

        // 用户名筛选
        if ($request->filled('username')) {
            $query->where('username', 'like', "%{$request->username}%");
        }

        // 模块筛选
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // 操作类型筛选
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // 状态筛选
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 时间范围筛选
        if ($request->filled('start_time')) {
            $query->where('created_at', '>=', $request->start_time);
        }
        if ($request->filled('end_time')) {
            $query->where('created_at', '<=', $request->end_time . ' 23:59:59');
        }

        // IP筛选
        if ($request->filled('ip')) {
            $query->where('ip', $request->ip);
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 20));

        return $this->paginate($paginator);
    }

    /**
     * 操作日志详情
     */
    public function show(int $id): JsonResponse
    {
        $log = OperationLog::find($id);

        if (!$log) {
            return $this->error('日志不存在', 404);
        }

        return $this->success($log);
    }

    /**
     * 删除操作日志
     */
    public function destroy(int $id): JsonResponse
    {
        $log = OperationLog::find($id);

        if (!$log) {
            return $this->error('日志不存在', 404);
        }

        $log->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * 批量删除操作日志
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        OperationLog::whereIn('id', $validated['ids'])->delete();

        return $this->success(null, '批量删除成功');
    }

    /**
     * 清理过期日志
     */
    public function clean(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $days = $validated['days'] ?? 90;
        $expireDate = now()->subDays($days);

        $count = OperationLog::where('created_at', '<', $expireDate)->delete();

        return $this->success([
            'deleted_count' => $count,
        ], "成功清理 {$count} 条日志");
    }

    /**
     * 导出操作日志
     */
    public function export(Request $request): JsonResponse
    {
        $query = OperationLog::query();

        // 应用筛选条件
        if ($request->filled('username')) {
            $query->where('username', 'like', "%{$request->username}%");
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_time')) {
            $query->where('created_at', '>=', $request->start_time);
        }
        if ($request->filled('end_time')) {
            $query->where('created_at', '<=', $request->end_time . ' 23:59:59');
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->limit(10000)
            ->get();

        $data = [];
        foreach ($logs as $log) {
            $data[] = [
                'ID' => $log->id,
                '操作用户' => $log->username,
                '操作模块' => $log->module,
                '操作类型' => $log->action,
                '操作标题' => $log->title,
                '请求方法' => $log->method,
                '请求URL' => $log->url,
                '操作IP' => $log->ip,
                '状态' => $log->status ? '成功' : '失败',
                '执行时长(ms)' => $log->duration,
                '操作时间' => $log->created_at,
            ];
        }

        return $this->success($data);
    }

    /**
     * 获取模块列表(用于筛选下拉)
     */
    public function modules(): JsonResponse
    {
        $modules = OperationLog::distinct()
            ->pluck('module')
            ->filter()
            ->values();

        return $this->success($modules);
    }

    /**
     * 获取操作类型列表(用于筛选下拉)
     */
    public function actions(): JsonResponse
    {
        $actions = OperationLog::distinct()
            ->pluck('action')
            ->filter()
            ->values();

        return $this->success($actions);
    }
}
