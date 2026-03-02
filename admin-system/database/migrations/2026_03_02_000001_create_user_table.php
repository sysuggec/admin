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
        Schema::create('t_user', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique()->comment('用户名');
            $table->string('password', 255)->comment('密码');
            $table->string('email', 100)->nullable()->comment('邮箱');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->string('avatar', 255)->nullable()->comment('头像URL');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1启用');
            $table->timestamp('deleted_at')->nullable()->comment('软删除时间');
            $table->timestamps();

            $table->index('username');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_user');
    }
};
