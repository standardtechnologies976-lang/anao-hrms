<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('medicine_id')->constrained('medicines')->cascadeOnDelete();
                $table->enum('type', ['in', 'out', 'adjustment']);
                $table->integer('quantity'); // signed for adjustments; positive for in/out
                $table->string('batch_no')->nullable();
                $table->date('expiry_date')->nullable();
                $table->decimal('unit_cost', 10, 2)->nullable();
                $table->string('reference')->nullable();
                $table->foreignId('dispense_id')->nullable();
                $table->dateTime('moved_at');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('medicine_id');
                $table->index(['medicine_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
