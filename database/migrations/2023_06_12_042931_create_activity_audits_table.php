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
        Schema::create('activity_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->unsignedBigInteger('audit_id')->nullable();
            $table->foreign('audit_id')->references('id')->on('users')->cascadeOnDelete();
            $table->enum('status', ['applying', 'reject', 'agree'])->default('applying');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_audits');
    }
};
