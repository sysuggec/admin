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
        Schema::create('t_role', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('角色标识');
            $table->string('display_name', 100)->comment('角色名称');
            $table->string('description', 255)->nullable()->comment('角色描述');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态: 0禁用 1启用');
            $table->timestamps();

            $table->index('name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_role');
    }
};
