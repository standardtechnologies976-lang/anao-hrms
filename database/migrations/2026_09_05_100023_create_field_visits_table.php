<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('field_visits')) {
            Schema::create('field_visits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bhw_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
                $table->foreignId('citizen_id')->nullable()->constrained('citizens')->nullOnDelete();
                $table->foreignId('household_id')->nullable()->constrained('households')->nullOnDelete();
                $table->date('visit_date');
                $table->string('visit_type');
                $table->text('findings')->nullable();
                $table->text('action_taken')->nullable();
                $table->boolean('referred')->default(false);
                $table->text('referral_notes')->nullable();
                $table->timestamps();

                $table->index(['bhw_id', 'visit_date']);
                $table->index('barangay_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('field_visits');
    }
};
