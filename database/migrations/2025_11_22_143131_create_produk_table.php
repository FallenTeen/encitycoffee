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
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori_produk')->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->enum('tipe', ['beans', 'minuman', 'snack']);
            $table->string('satuan_dasar')->default('pcs');
            $table->decimal('harga_modal', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2);
            $table->boolean('aktif')->default(true);
            $table->boolean('perlu_kalibrasi')->default(false);
            $table->timestamps();

            $table->index('tipe');
            $table->index('aktif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
