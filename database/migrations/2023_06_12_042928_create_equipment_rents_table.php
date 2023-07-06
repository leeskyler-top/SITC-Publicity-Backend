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
            $table->unsignedBigInteger('audit_id')->nullable();
            $table->foreign('audit_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dateTime("audit_time")->nullable();
            $table->dateTime("apply_time")->nullable();
            $table->dateTime("back_time")->nullable();
            $table->dateTime("report_time")->nullable();
            $table->text('assigned_url')->nullable();
            $table->text('returned_url')->nullable();
            $table->text('damaged_url')->nullable();
            $table->enum('status', ['applying', 'delay-applying', 'delayed', 'returned', 'rejected', 'assigned', 'damaged', 'missed'])->nullable()->default('returned');
            $table->timestamps();
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
