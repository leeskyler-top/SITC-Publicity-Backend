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
        Schema::create('equipment_rents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            // 蠢货，字段名字和表明无关是这样写的 明白了吗？
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('audit_id');
            $table->foreign('audit_id')->references('id')->on('users')->cascadeOnDelete();

            $table->text('assigned_url')->nullable();
            $table->text('returned_url')->nullable();
            $table->text('damaged_url')->nullable();
            $table->enum('status', ['applying', 'returned', 'reject', 'assigned', 'damaged', 'missed'])->nullable()->default('returned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_rents');
    }
};
