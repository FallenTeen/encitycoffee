<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use App\Models\Transaksi;
use App\Models\Shift;
use App\Models\MutasiStok;
use App\Models\Kalibrasi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function __construct()
    {

    }
    public function logs(Request $request)
    {
        abort(404);

        abort_unless(Gate::allows('view-admin-dashboard'), 403);

        $validated = $request->validate([
            'tanggal' => 'nullable|date',
            'user_id' => 'nullable|integer|exists:users,id',
            'tipe' => 'nullable|string|in:transaksi,shift,mutasi,kalibrasi',
        ]);

        $tanggal = $validated['tanggal'] ?? null;
        $userId = $validated['user_id'] ?? null;
        $tipe = $validated['tipe'] ?? null;

        $entries = [];


        $fmt = function ($tipe, $payload) {
            return array_merge(['tipe' => $tipe], $payload);
        };

        // Transaksi entries
        if ($tipe === null || $tipe === 'transaksi') {
            $q = Transaksi::with('user')
                ->select('id', 'user_id', 'cabang_id', 'nomor_invoice', 'total', 'status', 'created_at')
                ->orderByDesc('created_at');
            if ($tanggal) { $q->whereDate('created_at', $tanggal); }
            if ($userId) { $q->where('user_id', $userId); }

            $entries = array_merge($entries, $q->limit(200)->get()->map(function ($t) use ($fmt) {
                return $fmt('transaksi', [
                    'id' => $t->id,
                    'user' => optional($t->user)->name,
                    'cabang_id' => $t->cabang_id,
                    'nomor_invoice' => $t->nomor_invoice,
                    'total' => (float) $t->total,
                    'status' => $t->status,
                    'created_at' => $t->created_at,
                ]);
            })->toArray());
        }

        // Shift entries
        if ($tipe === null || $tipe === 'shift') {
            $q = Shift::with(['user', 'cabang'])
                ->select('id', 'user_id', 'cabang_id', 'status', 'waktu_buka', 'waktu_tutup', 'created_at')
                ->orderByDesc('created_at');
            if ($tanggal) { $q->whereDate('waktu_buka', $tanggal); }
            if ($userId) { $q->where('user_id', $userId); }

            $entries = array_merge($entries, $q->limit(200)->get()->map(function ($s) use ($fmt) {
                return $fmt('shift', [
                    'id' => $s->id,
                    'user' => optional($s->user)->name,
                    'cabang' => optional($s->cabang)->nama,
                    'status' => $s->status,
                    'waktu_buka' => $s->waktu_buka,
                    'waktu_tutup' => $s->waktu_tutup,
                    'created_at' => $s->created_at,
                ]);
            })->toArray());
        }

        // Mutasi stok entries
        if ($tipe === null || $tipe === 'mutasi') {
            $q = MutasiStok::with(['user', 'stokEtalase.produk'])
                ->select('id', 'user_id', 'stok_etalase_id', 'tipe', 'jumlah_perubahan', 'catatan', 'created_at')
                ->orderByDesc('created_at');
            if ($tanggal) { $q->whereDate('created_at', $tanggal); }
            if ($userId) { $q->where('user_id', $userId); }

            $entries = array_merge($entries, $q->limit(200)->get()->map(function ($m) use ($fmt) {
                return $fmt('mutasi', [
                    'id' => $m->id,
                    'user' => optional($m->user)->name,
                    'produk' => optional(optional($m->stokEtalase)->produk)->nama,
                    'tipe_mutasi' => $m->tipe,
                    'jumlah_perubahan' => (float) $m->jumlah_perubahan,
                    'catatan' => $m->catatan,
                    'created_at' => $m->created_at,
                ]);
            })->toArray());
        }

        // Kalibrasi entries
        if ($tipe === null || $tipe === 'kalibrasi') {
            $q = Kalibrasi::with(['shift.user', 'produk'])
                ->select('id', 'shift_id', 'produk_id', 'nomor_percobaan', 'berat_beans_gram', 'terpilih', 'created_at')
                ->orderByDesc('created_at');
            if ($tanggal) { $q->whereDate('created_at', $tanggal); }
            if ($userId) { $q->whereHas('shift', fn($qq) => $qq->where('user_id', $userId)); }

            $entries = array_merge($entries, $q->limit(200)->get()->map(function ($k) use ($fmt) {
                return $fmt('kalibrasi', [
                    'id' => $k->id,
                    'user' => optional(optional($k->shift)->user)->name,
                    'produk' => optional($k->produk)->nama,
                    'nomor_percobaan' => $k->nomor_percobaan,
                    'berat_beans_gram' => (float) $k->berat_beans_gram,
                    'terpilih' => (bool) $k->terpilih,
                    'created_at' => $k->created_at,
                ]);
            })->toArray());
        }


        usort($entries, function ($a, $b) {
            $ta = isset($a['created_at']) ? strtotime((string) $a['created_at']) : 0;
            $tb = isset($b['created_at']) ? strtotime((string) $b['created_at']) : 0;
            return $tb <=> $ta;
        });
        $entries = array_slice($entries, 0, 50);

        return Inertia::render('admin/system/Logs', [
            'entries' => $entries,
            'filters' => [
                'tanggal' => $tanggal,
                'user_id' => $userId,
                'tipe' => $tipe,
            ],
        ]);
    }
}
