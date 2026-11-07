<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            if (! Schema::hasColumn('citizens', 'verification_status')) {
                // Default 'verified' so existing/staff-added records are not thrown into the queue.
                $table->string('verification_status')->default('verified')->after('portal_enrolled_at');
            }
            if (! Schema::hasColumn('citizens', 'self_registered')) {
                $table->boolean('self_registered')->default(false)->after('verification_status');
            }
            if (! Schema::hasColumn('citizens', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('self_registered');
            }
            if (! Schema::hasColumn('citizens', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('citizens', 'verification_remarks')) {
                $table->text('verification_remarks')->nullable()->after('verified_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            if (Schema::hasColumn('citizens', 'verified_by')) {
                $table->dropConstrainedForeignId('verified_by');
            }
            foreach (['verification_status', 'self_registered', 'verified_at', 'verification_remarks'] as $col) {
                if (Schema::hasColumn('citizens', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
