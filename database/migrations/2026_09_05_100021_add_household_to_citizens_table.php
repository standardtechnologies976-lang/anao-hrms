<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            if (! Schema::hasColumn('citizens', 'household_id')) {
                $table->foreignId('household_id')->nullable()->after('barangay_id')->constrained('households')->nullOnDelete();
            }
            if (! Schema::hasColumn('citizens', 'relationship_to_head')) {
                $table->string('relationship_to_head')->nullable()->after('household_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            if (Schema::hasColumn('citizens', 'household_id')) {
                $table->dropConstrainedForeignId('household_id');
            }
            if (Schema::hasColumn('citizens', 'relationship_to_head')) {
                $table->dropColumn('relationship_to_head');
            }
        });
    }
};
