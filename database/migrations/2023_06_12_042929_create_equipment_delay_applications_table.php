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
        Schema::create('equipment_delay_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_rent_id')->constrained()->cascadeOnDelete();
            // 蠢货，字段名字和表明无关是这样写的 明白了吗？
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unsignedBigInteger('audit_id')->nullable();
            $table->foreign('audit_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dateTime("apply_time");
            $table->text('reason');
            $table->enum('status', ['delay-applying', 'delayed', 'rejected'])->nullable()->default('delay-applying');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_delay_applications');
    }
};
