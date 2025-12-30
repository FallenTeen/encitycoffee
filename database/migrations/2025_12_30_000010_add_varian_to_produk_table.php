<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->string('kelompok_nama')->nullable()->after('nama');
            $table->string('varian')->nullable()->after('kelompok_nama');
            $table->index('kelompok_nama');
        });

        DB::table('produk')
            ->whereNull('kelompok_nama')
            ->update(['kelompok_nama' => DB::raw('nama')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropIndex(['kelompok_nama']);
            $table->dropColumn(['kelompok_nama', 'varian']);
        });
    }
};

