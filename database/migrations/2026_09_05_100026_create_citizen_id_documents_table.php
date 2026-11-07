<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('citizen_id_documents')) {
            Schema::create('citizen_id_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
                $table->string('id_type');
                $table->string('id_number')->nullable();
                $table->string('file_path');
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->text('remarks')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['citizen_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_id_documents');
    }
};
