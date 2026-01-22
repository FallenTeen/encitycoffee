<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Models\StokEtalase;
use App\Models\BatchStok;
use App\Models\Shift;
use App\Models\Cabang;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ManagerController extends Controller
{
    public function __construct()
    {

    }

    public function dashboard(Request $request)
    {
        Gate::authorize('view-manager-dashboard');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $today = Carbon::today();
        $tanggalMulai7 = Carbon::today()->subDays(6);
        $tanggalAkhir7 = Carbon::today();

        $statistik = [
            'total_cabang' => count($cabangIds),
            'shift_aktif' => (int) Shift::whereIn('cabang_id', $cabangIds)->where('status', 'buka')->count(),
            'penjualan_hari_ini' => (float) Transaksi::whereIn('cabang_id', $cabangIds)
                ->where('status', 'selesai')
                ->whereDate('waktu_selesai', $today)
                ->sum('total'),
            'transaksi_hari_ini' => (int) Transaksi::whereIn('cabang_id', $cabangIds)
                ->where('status', 'selesai')
                ->whereDate('waktu_selesai', $today)
                ->count(),
            'stok_rendah' => (int) StokEtalase::whereIn('cabang_id', $cabangIds)
                ->whereColumn('jumlah', '<=', 'stok_minimum')
                ->count(),
            'kadaluarsa_alert' => (int) BatchStok::whereHas('stokEtalase', fn($q) => $q->whereIn('cabang_id', $cabangIds))
                ->mendekatiKadaluarsa(30)
                ->count(),
        ];

        $grafikPenjualan = Transaksi::where('status', 'selesai')
            ->whereIn('cabang_id', $cabangIds)
            ->whereBetween('waktu_selesai', [$tanggalMulai7, $tanggalAkhir7])
            ->join('cabang', 'transaksi.cabang_id', '=', 'cabang.id')
            ->select(DB::raw('DATE(transaksi.waktu_selesai) as tanggal'), 'cabang.nama as cabang', DB::raw('SUM(transaksi.total) as total_penjualan'))
            ->groupBy(DB::raw('DATE(transaksi.waktu_selesai)'), 'cabang.nama')
            ->orderBy('tanggal')
            ->get();

        $topProduk = ItemTransaksi::join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
            ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->whereIn('transaksi.cabang_id', $cabangIds)
            ->where('transaksi.status', 'selesai')
            ->whereBetween('transaksi.waktu_selesai', [Carbon::now()->subDays(30), Carbon::now()])
            ->select('item_transaksi.produk_id', 'produk.nama', DB::raw('SUM(item_transaksi.jumlah) as total_terjual'), DB::raw('SUM(item_transaksi.subtotal) as pendapatan'))
            ->groupBy('item_transaksi.produk_id', 'produk.nama')
            ->orderByDesc('total_terjual')
            ->limit(10)
            ->get();

        $performaCabang = Transaksi::join('shift', 'transaksi.shift_id', '=', 'shift.id')
            ->join('cabang', 'shift.cabang_id', '=', 'cabang.id')
            ->whereIn('shift.cabang_id', $cabangIds)
            ->whereBetween('transaksi.waktu_selesai', [Carbon::now()->subDays(30), Carbon::now()])
            ->where('transaksi.status', 'selesai')
            ->select('cabang.id as cabang_id', 'cabang.nama as cabang', DB::raw('SUM(transaksi.total) as total_penjualan'), DB::raw('COUNT(transaksi.id) as total_transaksi'), DB::raw('COUNT(DISTINCT shift.id) as total_shift'))
            ->groupBy('cabang.id', 'cabang.nama')
            ->orderByDesc('total_penjualan')
            ->get();

        // Props kompatibilitas dengan komponen Manager.tsx saat ini
        $branchPerformance = $performaCabang->map(fn($row) => [
            'cabang' => $row->cabang,
            'omzet' => (float) ($row->total_penjualan ?? 0),
        ]);

        return Inertia::render('dashboard/Manager', [
            'user' => ['name' => $user->name ?? 'Manager'],
            'activeShifts' => (int) $statistik['shift_aktif'],
            'lowStockCount' => (int) $statistik['stok_rendah'],
            'branchPerformance' => $branchPerformance,
            'statistik' => $statistik,
            'grafik_penjualan' => $grafikPenjualan,
            'top_produk' => $topProduk,
            'performa_cabang' => $performaCabang,
        ]);
    }


    public function laporanCabang(Request $request)
    {
        Gate::authorize('view-laporan');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $validated = $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'cabang_id' => ['nullable', 'integer', Rule::in($cabangIds)],
        ]);

        $tanggalMulai = $validated['tanggal_mulai'] ?? Carbon::now()->subDays(30)->toDateString();
        $tanggalAkhir = $validated['tanggal_selesai'] ?? Carbon::now()->toDateString();
        $cabangIdFilter = $validated['cabang_id'] ?? null;

        $query = Shift::whereIn('cabang_id', $cabangIds)
            ->where('status', 'tutup')
            ->whereBetween('waktu_tutup', [$tanggalMulai, $tanggalAkhir]);
        if ($cabangIdFilter) { $query->where('cabang_id', $cabangIdFilter); }

        $shift = $query->with(['cabang', 'user', 'transaksi' => function ($q) {
            $q->where('status', 'selesai');
        }])->get();

        // Kelompokkan berdasarkan cabang dan hitung metrik
        $kelompok = $shift->groupBy('cabang_id')->map(function ($group) {
            $cabang = optional($group->first()->cabang);
            $totalPenjualan = (float) $group->reduce(function ($carry, $s) {
                return $carry + (float) $s->transaksi->sum('total');
            }, 0);
            $jumlahTransaksi = (int) $group->reduce(function ($carry, $s) {
                return $carry + (int) $s->transaksi->count();
            }, 0);
            $jumlahShift = (int) $group->count();

            return [
                'cabang_id' => $cabang->id,
                'cabang' => $cabang->nama,
                'total_penjualan' => $totalPenjualan,
                'jumlah_transaksi' => $jumlahTransaksi,
                'jumlah_shift' => $jumlahShift,
                'rata_rata_per_shift' => $jumlahShift > 0 ? round($totalPenjualan / $jumlahShift, 2) : 0,
                'rata_rata_per_transaksi' => $jumlahTransaksi > 0 ? round($totalPenjualan / $jumlahTransaksi, 2) : 0,
            ];
        })->values();

        $metrics = [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalAkhir,
            'cabang_id' => $cabangIdFilter,
            'total_penjualan' => (float) $kelompok->sum('total_penjualan'),
            'total_transaksi' => (int) $kelompok->sum('jumlah_transaksi'),
            'total_shift' => (int) $shift->count(),
        ];

        return Inertia::render('manager/Reports/Cabang', [
            'cabangs' => $kelompok,
            'metrics' => $metrics,
        ]);
    }

    public function perfomaShift(Request $request)
    {
        Gate::authorize('view-manager-laporan');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $validated = $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'cabang_id' => ['nullable', 'integer', Rule::in($cabangIds)],
        ]);

        $tanggalMulai = $validated['tanggal_mulai'] ?? Carbon::now()->subDays(30)->toDateString();
        $tanggalAkhir = $validated['tanggal_selesai'] ?? Carbon::now()->toDateString();
        $cabangIdFilter = $validated['cabang_id'] ?? null;

        $query = Shift::select('shift.id', 'shift.user_id', 'shift.cabang_id', 'shift.waktu_buka', 'shift.waktu_tutup')
            ->with(['user', 'cabang'])
            ->whereIn('shift.cabang_id', $cabangIds)
            ->whereBetween('waktu_buka', [$tanggalMulai, $tanggalAkhir]);

        if ($cabangIdFilter) {
            $query->where('shift.cabang_id', $cabangIdFilter);
        }

        $performa = $query->get()->map(function ($s) {
            $penjualan = Transaksi::where('shift_id', $s->id)->where('status', 'selesai')->sum('total');
            $jumlahTransaksi = Transaksi::where('shift_id', $s->id)->where('status', 'selesai')->count();
            return [
                'shift_id' => $s->id,
                'kasir' => optional($s->user)->name,
                'cabang' => optional($s->cabang)->nama,
                'waktu_buka' => $s->waktu_buka,
                'waktu_tutup' => $s->waktu_tutup,
                'total_penjualan' => (float) $penjualan,
                'jumlah_transaksi' => (int) $jumlahTransaksi,
            ];
        });

        $filters = [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalAkhir,
            'cabang_id' => $cabangIdFilter,
        ];

        $cabangOptions = Cabang::whereIn('id', $cabangIds)
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama']);

        return Inertia::render('manager/Reports/PerformaShift', [
            'performance' => $performa,
            'filters' => $filters,
            'cabangOptions' => $cabangOptions,
        ]);
    }
}
