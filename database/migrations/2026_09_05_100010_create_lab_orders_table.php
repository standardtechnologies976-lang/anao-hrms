<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lab_orders')) {
            Schema::create('lab_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
                $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('ordered_at');
                $table->enum('status', ['requested', 'in_process', 'completed', 'released'])->default('requested');
                $table->text('remarks')->nullable();
                $table->dateTime('released_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'ordered_at']);
                $table->index('citizen_id');
            });
        }
        if (! Schema::hasTable('lab_order_items')) {
            Schema::create('lab_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
                $table->foreignId('lab_test_id')->constrained('lab_tests')->cascadeOnDelete();
                $table->string('result')->nullable();
                $table->enum('flag', ['normal', 'low', 'high', 'abnormal'])->nullable();
                $table->string('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
    }
};
