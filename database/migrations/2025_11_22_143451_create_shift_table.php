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
        Schema::create('shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('cabang_id')->constrained('cabang')->onDelete('cascade');
            $table->decimal('saldo_awal', 15, 2);
            $table->decimal('saldo_akhir', 15, 2)->nullable();
            $table->decimal('saldo_diharapkan', 15, 2)->nullable();
            $table->decimal('selisih', 15, 2)->nullable();
            $table->decimal('total_tunai', 15, 2)->default(0);
            $table->decimal('total_qris', 15, 2)->default(0);
            $table->timestamp('waktu_buka');
            $table->timestamp('waktu_tutup')->nullable();
            $table->enum('status', ['buka', 'tutup'])->default('buka');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['cabang_id', 'waktu_buka']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift');
    }
};
