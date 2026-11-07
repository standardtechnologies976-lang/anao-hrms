<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            if (! Schema::hasColumn('citizens', 'portal_email')) {
                $table->string('portal_email')->nullable()->unique()->after('remarks');
            }
            if (! Schema::hasColumn('citizens', 'password')) {
                $table->string('password')->nullable()->after('portal_email');
            }
            if (! Schema::hasColumn('citizens', 'remember_token')) {
                $table->string('remember_token', 100)->nullable()->after('password');
            }
            if (! Schema::hasColumn('citizens', 'portal_enrolled_at')) {
                $table->timestamp('portal_enrolled_at')->nullable()->after('remember_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            foreach (['portal_email', 'password', 'remember_token', 'portal_enrolled_at'] as $col) {
                if (Schema::hasColumn('citizens', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
