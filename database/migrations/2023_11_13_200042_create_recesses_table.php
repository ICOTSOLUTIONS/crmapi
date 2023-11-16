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
        Schema::create('recesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id')->nullable();
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();
            $table->string('break_type')->nullable();
            $table->time('total_time')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recesses');
    }
};
