<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assistance_requests')) {
            Schema::create('assistance_requests', function (Blueprint $table) {
                $table->id();
                $table->string('reference_no')->nullable()->unique();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->foreignId('assistance_type_id')->constrained('assistance_types')->cascadeOnDelete();
                $table->date('request_date');
                $table->text('purpose')->nullable();
                $table->decimal('amount_requested', 12, 2)->nullable();
                $table->decimal('amount_granted', 12, 2)->nullable();
                $table->enum('status', ['pending', 'approved', 'released', 'denied'])->default('pending');
                $table->text('remarks')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('released_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'request_date']);
                $table->index('citizen_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_requests');
    }
};
