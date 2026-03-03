<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Routes
|--------------------------------------------------------------------------
|
| 所有非 API 请求都返回 SPA 入口页面，由 Vue Router 处理前端路由。
| API 请求由 routes/api.php 处理。
|
*/

// SPA 入口路由 - 匹配所有非 API、非静态文件的请求
Route::view('/{any}', 'spa')
    ->where('any', '^(?!api|assets|\.vite|favicon\.ico|robots\.txt|index\.php).*$');
