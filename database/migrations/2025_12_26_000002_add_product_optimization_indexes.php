<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->index(['aktif', 'tipe']);
            $table->index('sku');
            $table->index('nama');
            $table->index('kategori_id');
        });

        Schema::table('stok_etalase', function (Blueprint $table) {
            $table->index(['produk_id', 'cabang_id']);
            $table->index('cabang_id');
        });

        Schema::table('cabang', function (Blueprint $table) {
            $table->index('aktif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropIndex(['aktif', 'tipe']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['nama']);
            $table->dropIndex(['kategori_id']);
        });

        Schema::table('stok_etalase', function (Blueprint $table) {
            $table->dropIndex(['produk_id', 'cabang_id']);
            $table->dropIndex(['cabang_id']);
        });

        Schema::table('cabang', function (Blueprint $table) {
            $table->dropIndex(['aktif']);
        });
    }
};