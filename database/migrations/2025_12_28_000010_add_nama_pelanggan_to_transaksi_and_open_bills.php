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
        Schema::table('transaksi', function (Blueprint $table) {
            $table->string('nama_pelanggan')->nullable()->after('user_id');
        });

        Schema::table('open_bills', function (Blueprint $table) {
            $table->string('nama_pelanggan')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('nama_pelanggan');
        });

        Schema::table('open_bills', function (Blueprint $table) {
            $table->dropColumn('nama_pelanggan');
        });
    }
};

