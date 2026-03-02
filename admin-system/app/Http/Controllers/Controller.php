<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * 成功响应
     */
    protected function success(mixed $data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 错误响应
     */
    protected function error(string $message = '操作失败', int $code = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code >= 400 && $code < 500 ? $code : 400);
    }

    /**
     * 分页响应
     */
    protected function paginate($paginator, string $message = '获取成功'): JsonResponse
    {
        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => [
                'list' => $paginator->items(),
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'page_size' => $paginator->perPage(),
            ],
        ]);
    }
}
