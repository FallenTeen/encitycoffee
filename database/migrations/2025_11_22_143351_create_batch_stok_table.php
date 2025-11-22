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
       Schema::create('batch_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_etalase_id')->constrained('stok_etalase')->onDelete('cascade');
            $table->string('nomor_batch');
            $table->decimal('jumlah', 15, 4);
            $table->date('tanggal_kadaluarsa')->nullable();
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->timestamp('waktu_terima');
            $table->timestamps();

            $table->index('stok_etalase_id');
            $table->index('tanggal_kadaluarsa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_stok');
    }
};
