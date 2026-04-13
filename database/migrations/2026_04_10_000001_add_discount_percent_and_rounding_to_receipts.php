<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi', 'diskon_persen')) {
                $table->decimal('diskon_persen', 10, 4)->nullable()->after('diskon');
            }
            if (! Schema::hasColumn('transaksi', 'diskon_rounding_mode')) {
                $table->string('diskon_rounding_mode', 20)->nullable()->after('diskon_persen');
            }
            if (! Schema::hasColumn('transaksi', 'diskon_rounding_unit')) {
                $table->unsignedInteger('diskon_rounding_unit')->nullable()->after('diskon_rounding_mode');
            }
            if (! Schema::hasColumn('transaksi', 'diskon_rounding_delta')) {
                $table->decimal('diskon_rounding_delta', 15, 2)->nullable()->default(0)->after('diskon_rounding_unit');
            }
        });

        Schema::table('open_bills', function (Blueprint $table) {
            if (! Schema::hasColumn('open_bills', 'diskon_persen')) {
                $table->decimal('diskon_persen', 10, 4)->nullable()->after('diskon');
            }
            if (! Schema::hasColumn('open_bills', 'diskon_rounding_mode')) {
                $table->string('diskon_rounding_mode', 20)->nullable()->after('diskon_persen');
            }
            if (! Schema::hasColumn('open_bills', 'diskon_rounding_unit')) {
                $table->unsignedInteger('diskon_rounding_unit')->nullable()->after('diskon_rounding_mode');
            }
            if (! Schema::hasColumn('open_bills', 'diskon_rounding_delta')) {
                $table->decimal('diskon_rounding_delta', 15, 2)->nullable()->default(0)->after('diskon_rounding_unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $cols = ['diskon_persen', 'diskon_rounding_mode', 'diskon_rounding_unit', 'diskon_rounding_delta'];
            $existing = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('transaksi', $c)));
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('open_bills', function (Blueprint $table) {
            $cols = ['diskon_persen', 'diskon_rounding_mode', 'diskon_rounding_unit', 'diskon_rounding_delta'];
            $existing = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('open_bills', $c)));
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};

