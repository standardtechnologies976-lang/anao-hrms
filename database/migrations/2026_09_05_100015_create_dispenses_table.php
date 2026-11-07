<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dispenses')) {
            Schema::create('dispenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
                $table->dateTime('dispensed_at');
                $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('citizen_id');
            });
        }
        if (! Schema::hasTable('dispense_items')) {
            Schema::create('dispense_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dispense_id')->constrained('dispenses')->cascadeOnDelete();
                $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
                $table->unsignedInteger('quantity');
                $table->string('instructions')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispense_items');
        Schema::dropIfExists('dispenses');
    }
};
