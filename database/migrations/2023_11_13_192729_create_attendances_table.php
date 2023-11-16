<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->time('expected_time_out')->nullable();
            $table->time('working_time')->nullable();
            $table->date('date')->nullable();
            $table->enum('status', ['present', 'late', 'late_and_short', 'absent', 'short_day', 'half_day', 'discrepancy', 'holiday', 'leave'])->default('present');
            $table->timestamps();
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
