<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('surveys')) {
            Schema::create('surveys', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
                $table->boolean('is_public')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('survey_questions')) {
            Schema::create('survey_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('label');
                $table->string('type')->default('text'); // text, paragraph, number, date, yesno, radio, checkbox, rating
                $table->json('options')->nullable();
                $table->boolean('required')->default(false);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('survey_responses')) {
            Schema::create('survey_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();
                $table->foreignId('citizen_id')->nullable()->constrained('citizens')->nullOnDelete();
                $table->foreignId('barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
                $table->string('respondent_name')->nullable();
                $table->enum('submitted_via', ['public', 'bhw', 'staff'])->default('public');
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('submitted_at');
                $table->timestamps();

                $table->index(['survey_id', 'submitted_at']);
            });
        }
        if (! Schema::hasTable('survey_answers')) {
            Schema::create('survey_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('survey_response_id')->constrained('survey_responses')->cascadeOnDelete();
                $table->foreignId('survey_question_id')->constrained('survey_questions')->cascadeOnDelete();
                $table->text('answer')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('surveys');
    }
};
