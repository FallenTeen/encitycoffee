<?php
// database/migrations/2025_11_23_000001_add_handover_request_to_shift_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shift', function (Blueprint $table) {
            $table->text('handover_request')->nullable()->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('shift', function (Blueprint $table) {
            $table->dropColumn('handover_request');
        });
    }
};
