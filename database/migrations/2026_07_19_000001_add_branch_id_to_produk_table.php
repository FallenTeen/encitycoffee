<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan kolom cabang_id ke tabel produk untuk menandai cabang tempat produk dibuat.
     * Ini memungkinkan isolasi produk per cabang - manager cabang hanya bisa mengelola
     * produk yang dibuat di cabangnya sendiri.
     * 
     * Catatan: Menggunakan nama kolom 'cabang_id' untuk konsisten dengan tabel lain
     * seperti stok_etalase, transaksi, shift, dll yang sudah menggunakan nama ini.
     */
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            // cabang_id untuk menandai cabang pembuat produk
            // NULL berarti produk belum di-assign ke cabang tertentu (legacy data)
            $table->foreignId('cabang_id')
                ->nullable()
                ->after('perlu_kalibrasi')
                ->constrained('cabang')
                ->onDelete('restrict');
            
            // Index untuk query filtering berdasarkan cabang
            $table->index('cabang_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropIndex(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
    }
};
