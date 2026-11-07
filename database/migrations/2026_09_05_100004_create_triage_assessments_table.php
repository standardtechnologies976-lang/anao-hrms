<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('triage_assessments')) {
            Schema::create('triage_assessments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->dateTime('assessed_at');
                $table->text('chief_complaint')->nullable();

                // Vital signs
                $table->decimal('temperature', 4, 1)->nullable();   // Celsius
                $table->unsignedSmallInteger('systolic')->nullable();
                $table->unsignedSmallInteger('diastolic')->nullable();
                $table->unsignedSmallInteger('pulse')->nullable();          // bpm
                $table->unsignedSmallInteger('respiratory_rate')->nullable();
                $table->unsignedSmallInteger('oxygen_saturation')->nullable(); // %
                $table->decimal('height_cm', 5, 1)->nullable();
                $table->decimal('weight_kg', 5, 1)->nullable();
                $table->decimal('bmi', 4, 1)->nullable();
                $table->unsignedTinyInteger('pain_scale')->nullable();      // 0-10

                $table->enum('priority', ['emergent', 'urgent', 'non_urgent'])->default('non_urgent');
                $table->enum('status', ['waiting', 'seen', 'done'])->default('waiting');
                $table->text('notes')->nullable();

                $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['assessed_at', 'status']);
                $table->index('citizen_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('triage_assessments');
    }
};
