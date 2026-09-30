<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Enforce the Phase 2 invariant: user + branch => at most one active shift.
 *
 * MySQL has no partial (filtered) unique index, so a virtual generated column
 * carries the constraint instead: it is non-NULL only while the row is an open,
 * non soft-deleted shift, and MySQL ignores NULLs in a UNIQUE index. Every other
 * row compares as NULL, so only the "buka" rows actually collide.
 */
return new class extends Migration
{
    private const INDEX = 'shift_active_branch_unique';

    private const COLUMN = 'active_branch_key';

    public function up(): void
    {
        // A pre-existing duplicate would make the unique index fail, so close the
        // extra rows first. Nothing is deleted: each duplicate is kept as a closed
        // shift with an audit note, and the affected pairs are logged.
        $this->closeDuplicateOpenShifts();

        Schema::table('shift', function ($table) {
            $table->string(self::COLUMN, 64)
                ->nullable()
                ->virtualAs("CASE WHEN `status` = 'buka' AND `deleted_at` IS NULL THEN CONCAT(`user_id`, ':', `cabang_id`) ELSE NULL END");
            $table->unique(self::COLUMN, self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('shift', function ($table) {
            $table->dropUnique(self::INDEX);
            $table->dropColumn(self::COLUMN);
        });
    }

    /**
     * Keep the newest open shift per (user, cabang) and close the older ones.
     */
    private function closeDuplicateOpenShifts(): void
    {
        $duplicates = DB::table('shift')
            ->selectRaw('user_id, cabang_id, COUNT(*) as total')
            ->where('status', 'buka')
            ->whereNull('deleted_at')
            ->groupBy('user_id', 'cabang_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $pair) {
            $rows = DB::table('shift')
                ->where('status', 'buka')
                ->whereNull('deleted_at')
                ->where('user_id', $pair->user_id)
                ->where('cabang_id', $pair->cabang_id)
                ->orderByDesc('waktu_buka')
                ->orderByDesc('id')
                ->get();

            // The first row stays open; the rest are closed, newest kept.
            $toClose = $rows->slice(1);

            foreach ($toClose as $row) {
                DB::table('shift')
                    ->where('id', $row->id)
                    ->update([
                        'status' => 'tutup',
                        'waktu_tutup' => now(),
                        'catatan' => trim(($row->catatan ?? '').' [Ditutup otomatis oleh migrasi active-shift uniqueness: duplikat shift untuk user+cabang yang sama]'),
                        'updated_at' => now(),
                    ]);
            }

            Log::warning('Migration closed duplicate open shifts', [
                'user_id' => (int) $pair->user_id,
                'cabang_id' => (int) $pair->cabang_id,
                'closed_shift_ids' => $toClose->pluck('id')->all(),
            ]);
        }
    }
};
