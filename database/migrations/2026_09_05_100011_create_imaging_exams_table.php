<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('imaging_exams')) {
            Schema::create('imaging_exams', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('modality')->nullable();
                $table->decimal('price', 8, 2)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('imaging_exams');
    }
};
