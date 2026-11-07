<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admissions')) {
            Schema::create('admissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
                $table->foreignId('ward_id')->nullable()->constrained('wards')->nullOnDelete();
                $table->foreignId('bed_id')->nullable()->constrained('beds')->nullOnDelete();
                $table->dateTime('admitted_at');
                $table->string('admitting_diagnosis')->nullable();
                $table->string('attending_physician')->nullable();
                $table->enum('status', ['admitted', 'discharged'])->default('admitted');
                $table->dateTime('discharged_at')->nullable();
                $table->string('discharge_diagnosis')->nullable();
                $table->enum('outcome', ['improved', 'recovered', 'referred', 'transferred', 'expired', 'hama'])->nullable();
                $table->text('discharge_notes')->nullable();
                $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'admitted_at']);
                $table->index('citizen_id');
            });
        }
        if (! Schema::hasTable('nursing_notes')) {
            Schema::create('nursing_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
                $table->dateTime('noted_at');
                $table->text('note')->nullable();
                $table->decimal('temperature', 4, 1)->nullable();
                $table->unsignedSmallInteger('systolic')->nullable();
                $table->unsignedSmallInteger('diastolic')->nullable();
                $table->unsignedSmallInteger('pulse')->nullable();
                $table->unsignedSmallInteger('respiratory_rate')->nullable();
                $table->unsignedSmallInteger('oxygen_saturation')->nullable();
                $table->foreignId('nurse_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('admission_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_notes');
        Schema::dropIfExists('admissions');
    }
};
