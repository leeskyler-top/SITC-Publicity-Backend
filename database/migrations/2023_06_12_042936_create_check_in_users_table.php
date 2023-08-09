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
        Schema::create('check_in_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_in_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->enum('status', ['signed', 'unsigned'])->default('unsigned');
            $table->text('image_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_in_users');
    }
};
