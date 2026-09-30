<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel audit untuk mencatat setiap kali backend melakukan penyesuaian
     * nominal pembayaran (overpay correction) — terutama untuk QRIS/Transfer.
     */
    public function up(): void
    {
        Schema::create('payment_adjustment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->onDelete('cascade');
            $table->string('metode_pembayaran');                     // tunai / qris / transfer
            $table->decimal('nominal_input', 15, 2);                // nilai yang dikirim dari Flutter
            $table->decimal('nominal_tercatat', 15, 2);             // nilai yang benar-benar disimpan setelah koreksi
            $table->decimal('potongan', 15, 2);                     // selisih yang dipotong
            $table->string('alasan')->nullable();                   // keterangan singkat alasan pemotongan
            $table->timestamps();

            $table->index('transaksi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_adjustment_logs');
    }
};
