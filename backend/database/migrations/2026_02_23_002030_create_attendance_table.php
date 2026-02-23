<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained('workshop_forms')->cascadeOnDelete();
            $table->timestamp('attended_at');
            $table->timestamps();
            $table->unique(['student_id', 'form_id']); // prevent duplicate attendance
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
