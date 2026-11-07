<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wards')) {
            Schema::create('wards', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('beds')) {
            Schema::create('beds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ward_id')->constrained('wards')->cascadeOnDelete();
                $table->string('label');
                $table->boolean('is_occupied')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
    }
};
