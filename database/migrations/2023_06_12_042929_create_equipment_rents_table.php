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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audit_id')->constrained('users')->cascadeOnDelete();
            $table->text('assigned_url')->nullable();
            $table->text('returned_url')->nullable();
            $table->text('damaged_url')->nullable();
            $table->enum('item_status', ['returned', 'assigned'])->nullable()->default('returned');
            $table->enum('return_status', ['returned', 'assigned', 'damaged' , 'missed'])->nullable()->default('returned');
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
