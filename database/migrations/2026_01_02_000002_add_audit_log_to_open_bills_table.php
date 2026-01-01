<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('open_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('open_bills', 'audit_log')) {
                $table->json('audit_log')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('open_bills', function (Blueprint $table) {
            if (Schema::hasColumn('open_bills', 'audit_log')) {
                $table->dropColumn('audit_log');
            }
        });
    }
};

