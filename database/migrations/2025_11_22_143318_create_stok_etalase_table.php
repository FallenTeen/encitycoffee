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
        Schema::create('stok_etalase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('cabang')->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('produk')->onDelete('cascade');
            $table->enum('tipe_stok', ['produksi_minuman', 'penjualan_retail']);
            $table->decimal('jumlah', 15, 4)->default(0);
            $table->decimal('stok_minimum', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['cabang_id', 'produk_id', 'tipe_stok']);
            $table->index(['cabang_id', 'tipe_stok']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_etalase');
    }
};
