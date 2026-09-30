<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom client_transaction_id (UUID idempotency key) agar
     * server dapat menolak duplikat request yang dikirim 2x akibat jaringan lambat.
     */
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('client_transaction_id', 36)
                ->nullable()
                ->unique()
                ->after('nomor_invoice')
                ->comment('UUID v4 yang di-generate Flutter saat halaman pembayaran dibuka. Digunakan sebagai idempotency key.');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropUnique(['client_transaction_id']);
            $table->dropColumn('client_transaction_id');
        });
    }
};
