<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultations')) {
            Schema::create('consultations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->foreignId('triage_assessment_id')->nullable()->constrained('triage_assessments')->nullOnDelete();
                $table->foreignId('physician_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('consulted_at');

                // SOAP
                $table->text('subjective')->nullable();
                $table->text('objective')->nullable();
                $table->text('assessment')->nullable();
                $table->text('plan')->nullable();

                // Diagnosis
                $table->string('diagnosis')->nullable();
                $table->string('icd10_code')->nullable();

                // Disposition
                $table->enum('disposition', ['home', 'follow_up', 'refer', 'admit'])->default('home');
                $table->date('follow_up_date')->nullable();
                $table->string('referred_to')->nullable();
                $table->text('referral_notes')->nullable();

                $table->enum('status', ['open', 'completed'])->default('completed');
                $table->timestamps();

                $table->index(['consulted_at', 'status']);
                $table->index('citizen_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
