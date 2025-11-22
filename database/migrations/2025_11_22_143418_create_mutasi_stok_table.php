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
        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_etalase_id')->constrained('stok_etalase')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('tipe', ['masuk', 'keluar', 'penyesuaian', 'kalibrasi', 'tidak_teralokasi']);
            $table->decimal('jumlah_sebelum', 15, 4);
            $table->decimal('jumlah_sesudah', 15, 4);
            $table->decimal('jumlah_perubahan', 15, 4);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('stok_etalase_id');
            $table->index('tipe');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};
