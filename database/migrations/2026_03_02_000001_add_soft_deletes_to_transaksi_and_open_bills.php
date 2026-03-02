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
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            $table->text('delete_reason')->nullable()->after('deleted_by');
            
            $table->index(['deleted_at', 'deleted_by']);
        });

        Schema::table('open_bills', function (Blueprint $table) {
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            $table->text('delete_reason')->nullable()->after('deleted_by');
            
            $table->index(['deleted_at', 'deleted_by']);
        });

        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });

        Schema::table('open_bill_items', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex(['deleted_at', 'deleted_by']);
            $table->dropColumn(['deleted_at', 'deleted_by', 'delete_reason']);
        });

        Schema::table('open_bills', function (Blueprint $table) {
            $table->dropIndex(['deleted_at', 'deleted_by']);
            $table->dropColumn(['deleted_at', 'deleted_by', 'delete_reason']);
        });

        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropColumn('deleted_at');
        });

        Schema::table('open_bill_items', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropColumn('deleted_at');
        });
    }
};