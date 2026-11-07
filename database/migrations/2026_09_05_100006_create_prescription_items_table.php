<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prescription_items')) {
            Schema::create('prescription_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
                $table->string('drug_name');
                $table->string('dosage')->nullable();      // e.g., 500 mg
                $table->string('frequency')->nullable();   // e.g., TID
                $table->string('duration')->nullable();    // e.g., 7 days
                $table->unsignedInteger('quantity')->nullable();
                $table->string('instructions')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
