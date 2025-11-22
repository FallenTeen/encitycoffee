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
        Schema::create('antrian_sinkronisasi', function (Blueprint $table) {
            $table->id();
            $table->string('id_perangkat');
            $table->enum('tipe_entitas', ['transaksi', 'mutasi_stok', 'shift']);
            $table->unsignedBigInteger('id_entitas');
            $table->text('payload');
            $table->enum('status', ['pending', 'tersinkronisasi', 'gagal'])->default('pending');
            $table->integer('jumlah_percobaan')->default(0);
            $table->timestamp('waktu_sinkronisasi')->nullable();
            $table->timestamps();

            $table->index(['id_perangkat', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antrian_sinkronisasi');
    }
};
