<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Bind the offline sync queue to the user that enqueued it.
 *
 * id_perangkat is client supplied and was never checked against the caller, so any
 * authenticated POS user could read and drain another device's queue. The processor
 * applies writes, which makes that an arbitrary-write path, not just a read leak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian_sinkronisasi', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id_perangkat')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['user_id', 'status'], 'antrian_sinkronisasi_user_status_index');
        });

        // Pre-existing rows cannot be attributed to a user, so they stay NULL and are
        // never processed. Guessing an owner would be worse than leaving them inert.
        $yatim = DB::table('antrian_sinkronisasi')->whereNull('user_id')->count();

        if ($yatim > 0) {
            Log::warning('Antrean sinkronisasi lama tidak dapat diatribusikan ke user', [
                'jumlah' => $yatim,
                'catatan' => 'Item ini tidak akan diproses; perlu ditinjau manual.',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('antrian_sinkronisasi', function (Blueprint $table) {
            $table->dropIndex('antrian_sinkronisasi_user_status_index');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
