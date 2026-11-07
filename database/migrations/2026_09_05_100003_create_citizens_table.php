<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('citizens')) {
            Schema::create('citizens', function (Blueprint $table) {
                $table->id();
                $table->string('citizen_no')->nullable()->unique();

                // Identity
                $table->string('last_name');
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('suffix')->nullable();
                $table->enum('sex', ['male', 'female'])->nullable();
                $table->date('birth_date')->nullable();
                $table->string('birth_place')->nullable();
                $table->string('civil_status')->nullable();
                $table->string('contact_no')->nullable();

                // Location
                $table->foreignId('barangay_id')->nullable()->constrained('barangays')->nullOnDelete();
                $table->string('purok')->nullable();

                // Government IDs
                $table->string('philhealth_no')->nullable();
                $table->string('philsys_no')->nullable();

                // Profile
                $table->string('occupation')->nullable();
                $table->string('blood_type')->nullable();
                $table->string('religion')->nullable();
                $table->string('indigenous_group')->nullable();

                // Social service / RHU flags
                $table->boolean('is_pwd')->default(false);
                $table->boolean('is_senior')->default(false);
                $table->boolean('is_4ps')->default(false);
                $table->boolean('is_solo_parent')->default(false);
                $table->boolean('is_pregnant')->default(false);

                // Status
                $table->boolean('deceased')->default(false);
                $table->date('date_of_death')->nullable();
                $table->text('remarks')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['last_name', 'first_name']);
                $table->index('barangay_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};
