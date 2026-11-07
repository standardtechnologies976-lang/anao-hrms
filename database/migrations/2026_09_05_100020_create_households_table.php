<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('households')) {
            Schema::create('households', function (Blueprint $table) {
                $table->id();
                $table->string('household_no')->nullable()->unique();
                $table->foreignId('barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
                $table->string('purok')->nullable();
                $table->string('address')->nullable();
                $table->foreignId('head_citizen_id')->nullable()->constrained('citizens')->nullOnDelete();

                // Housing
                $table->string('house_ownership')->nullable();   // owned, rented, shared
                $table->string('house_type')->nullable();        // concrete, semi-concrete, light
                // WASH
                $table->string('water_source')->nullable();
                $table->string('toilet_facility')->nullable();
                $table->boolean('has_electricity')->default(false);
                $table->string('waste_disposal')->nullable();
                // Socioeconomic
                $table->string('income_bracket')->nullable();

                $table->date('surveyed_at')->nullable();
                $table->foreignId('surveyed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index('barangay_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
