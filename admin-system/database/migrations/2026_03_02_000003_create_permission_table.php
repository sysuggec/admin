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
        Schema::create('t_permission', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('权限标识');
            $table->string('display_name', 100)->comment('权限名称');
            $table->enum('type', ['menu', 'button', 'api'])->comment('权限类型');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父级ID');
            $table->string('path', 255)->nullable()->comment('前端路由路径');
            $table->string('api_path', 255)->nullable()->comment('API路径');
            $table->string('icon', 100)->nullable()->comment('菜单图标');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1启用');
            $table->timestamps();

            $table->index('name');
            $table->index('type');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_permission');
    }
};
