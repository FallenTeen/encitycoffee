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
        DB::statement("ALTER TABLE produk MODIFY COLUMN tipe ENUM('snack', 'minuman', 'beans', 'makanan', 'bundling') NULL");
        
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->decimal('potongan_bundling', 12, 2)->default(0)->after('subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->dropColumn('potongan_bundling');
        });
        
        DB::statement("ALTER TABLE produk MODIFY COLUMN tipe ENUM('snack', 'minuman', 'beans', 'makanan') NULL");
    }
};
