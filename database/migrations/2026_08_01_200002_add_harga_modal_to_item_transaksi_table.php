<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->decimal('harga_modal', 15, 2)->default(0)->after('harga_satuan');
        });

        // Copy existing harga_modal from produk to item_transaksi for historical consistency
        DB::statement('UPDATE item_transaksi it JOIN produk p ON it.produk_id = p.id SET it.harga_modal = p.harga_modal');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->dropColumn('harga_modal');
        });
    }
};
