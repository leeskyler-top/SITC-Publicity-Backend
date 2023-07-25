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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->foreign('admin_id')->references('id')->on("users");
            $table->string('title');
            $table->string('place');
            $table->enum('type', ['self-enrollment', 'ase', 'assignment'])->default('self-enrollment');
            $table->enum("is_enrolling", ['1', '0'])->default('1');
            $table->text('note');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
