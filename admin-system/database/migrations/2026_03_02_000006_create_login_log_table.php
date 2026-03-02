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
        Schema::create('t_login_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->string('username', 50)->comment('用户名');
            $table->string('ip', 45)->comment('登录IP');
            $table->string('user_agent', 500)->nullable()->comment('浏览器信息');
            $table->timestamp('login_time')->useCurrent()->comment('登录时间');
            $table->tinyInteger('status')->default(1)->comment('状态: 0失败 1成功');
            $table->string('message', 255)->nullable()->comment('登录消息');

            $table->index('user_id');
            $table->index('login_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_login_log');
    }
};
