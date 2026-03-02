<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('t_operation_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('操作用户ID');
            $table->string('username', 50)->comment('操作用户名');
            $table->string('module', 50)->comment('操作模块');
            $table->string('action', 50)->comment('操作类型');
            $table->string('title', 200)->comment('操作标题');
            $table->string('method', 10)->comment('请求方法');
            $table->string('url', 500)->comment('请求URL');
            $table->text('params')->nullable()->comment('请求参数');
            $table->text('response')->nullable()->comment('响应结果');
            $table->string('ip', 45)->comment('操作IP');
            $table->string('user_agent', 500)->nullable()->comment('浏览器信息');
            $table->tinyInteger('status')->default(1)->comment('状态: 0失败 1成功');
            $table->text('error_msg')->nullable()->comment('错误信息');
            $table->unsignedInteger('duration')->default(0)->comment('执行时长(毫秒)');
            $table->timestamp('created_at')->useCurrent()->comment('操作时间');

            $table->index('user_id');
            $table->index('module');
            $table->index('action');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_operation_log');
    }
};
