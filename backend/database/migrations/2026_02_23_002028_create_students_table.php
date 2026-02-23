<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('workshop_forms')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments');
            $table->string('name');
            $table->enum('gender', ['male', 'female']);
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->tinyInteger('stage'); // 1-4
            $table->text('description')->nullable();
            $table->string('qr_token')->unique(); // encrypted UUID
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
