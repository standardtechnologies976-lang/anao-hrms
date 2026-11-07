<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medicines')) {
            Schema::create('medicines', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('generic_name')->nullable();
                $table->string('form')->nullable();
                $table->string('strength')->nullable();
                $table->string('unit')->nullable();
                $table->unsignedInteger('reorder_level')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
