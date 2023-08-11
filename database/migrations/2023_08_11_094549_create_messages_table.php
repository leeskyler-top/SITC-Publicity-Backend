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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // 通知者
            $table->unsignedBigInteger('msg_user_id')->nullable();
            $table->foreign('msg_user_id')->references('id')->on('users')->cascadeOnDelete();
            // 如果类型为private，user_id必须存在，否则是一个无效的消息，类型为all或admin时user_id无效
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type', ['private', 'all', 'admin']);
            $table->enum('status', ['unnecessary', 'unread', 'read'])->default('unread');
            $table->text('title');
            $table->text('msg');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
