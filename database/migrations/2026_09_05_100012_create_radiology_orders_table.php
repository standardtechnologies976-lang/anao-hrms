<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('radiology_orders')) {
            Schema::create('radiology_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
                $table->foreignId('imaging_exam_id')->constrained('imaging_exams')->cascadeOnDelete();
                $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('ordered_at');
                $table->text('clinical_indication')->nullable();
                $table->enum('status', ['requested', 'performed', 'reported', 'released'])->default('requested');
                $table->dateTime('performed_at')->nullable();
                $table->text('findings')->nullable();
                $table->text('impression')->nullable();
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('released_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'ordered_at']);
                $table->index('citizen_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('radiology_orders');
    }
};
