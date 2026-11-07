<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no')->nullable()->unique();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->string('service');
                $table->date('preferred_date');
                $table->string('preferred_time')->nullable();
                $table->text('reason')->nullable();
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
                $table->dateTime('scheduled_at')->nullable();
                $table->text('remarks')->nullable();
                $table->enum('source', ['portal', 'staff'])->default('portal');
                $table->timestamps();

                $table->index(['status', 'preferred_date']);
                $table->index('citizen_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
