<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        
        Log::info('LaporanCabang called', ['user_id' => Auth::id(), 'role' => Auth::user()->role]);

        $user = Auth::user();
        $user->load('cabang:id,nama,kode,aktif');
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // If user has no assigned branches, return empty data
        if (empty($assignedCabangIds)) {
            return Inertia::render('manager/Reports/Cabang', [
                'cabangs' => [],
                'filters' => [
                    'range' => '30d',
                    'tanggal_mulai' => Carbon::today()->subDays(29)->format('Y-m-d'),
                    'tanggal_selesai' => Carbon::today()->format('Y-m-d'),
                    'cabang_id' => null,
                ],
                'metrics' => [
                    'total_revenue' => 0,
                    'total_transactions' => 0,
                    'total_discount' => 0,
                ],
                'branchComparison' => [],
                'assignedBranches' => [],
                'error' => 'Anda belum memiliki cabang yang diampu. Hubungi administrator untuk menetapkan cabang.',
            ]);
        }

        $validated = $request->validate([
            'range' => ['nullable', 'in:7d,14d,30d,90d,custom'],
            'tanggal_mulai' => ['nullable', 'date', 'before_or_equal:today'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai', 'before_or_equal:today'],
            'cabang_id' => ['nullable', 'integer', 'exists:cabang,id'],
        ]);

        $range = (string) ($validated['range'] ?? '30d');
        $endDate = Carbon::today();
        
        // Calculate date range
        if ($range !== 'custom') {
            $days = (int) rtrim($range, 'd');
            $days = max(1, min(90, $days));
            $startDate = $endDate->copy()->subDays($days - 1);
        } else {
            $startDate = ! empty($validated['tanggal_mulai'])
                ? Carbon::parse($validated['tanggal_mulai'])
                : $endDate->copy()->subDays(29);
            $endDate = ! empty($validated['tanggal_selesai'])
                ? Carbon::parse($validated['tanggal_selesai'])
                : $endDate;

            if ($endDate->lt($startDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
            if ($startDate->diffInDays($endDate) > 89) {
                $startDate = $endDate->copy()->subDays(89);
            }
        }

        $periodStart = $startDate->copy()->startOfDay();
        $periodEnd = $endDate->copy()->endOfDay();

        // Calculate previous period for growth comparison
        $periodDays = $periodStart->diffInDays($periodEnd) + 1;
        $previousEnd = $periodStart->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1);

        // Determine filter branches
        $requestedCabangId = ! empty($validated['cabang_id']) ? (int) $validated['cabang_id'] : null;
        
        if ($requestedCabangId !== null) {
            if (!in_array($requestedCabangId, $assignedCabangIds)) {
                abort(403, 'Tidak memiliki akses ke cabang ini');
            }
            $filterCabangIds = [$requestedCabangId];
        } else {
            $filterCabangIds = $assignedCabangIds;
        }

        // Get branch data
        $branches = Cabang::whereIn('cabang.id', $filterCabangIds)
            ->select('cabang.id', 'cabang.nama', 'cabang.kode', 'cabang.aktif')
            ->orderBy('cabang.nama')
            ->get();

        $branchData = [];
        $totalRevenue = 0;
        $totalTransactions = 0;
        $totalDiscount = 0;

        foreach ($branches as $branch) {
            // Current period transactions
            $currentTransaksi = Transaksi::where('cabang_id', $branch->id)
                ->where('status', 'selesai')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->selectRaw('SUM(total) as total_revenue')
                ->selectRaw('SUM(COALESCE(diskon, 0)) as total_discount')
                ->selectRaw('COUNT(*) as transaction_count')
                ->first();

            // Previous period for growth calculation
            $previousTransaksi = Transaksi::where('cabang_id', $branch->id)
                ->where('status', 'selesai')
                ->whereBetween('created_at', [$previousStart->startOfDay(), $previousEnd->endOfDay()])
                ->selectRaw('SUM(total) as total_revenue')
                ->first();

            $currentRevenue = (float) ($currentTransaksi->total_revenue ?? 0);
            $previousRevenue = (float) ($previousTransaksi->total_revenue ?? 0);
            
            // Calculate growth percentage
            $growthPercent = $previousRevenue > 0 
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                : ($currentRevenue > 0 ? 100 : 0);

            // Average transaction value
            $transactionCount = (int) ($currentTransaksi->transaction_count ?? 0);
            $avgTransaction = $transactionCount > 0 ? $currentRevenue / $transactionCount : 0;

            // Top products
            $topProducts = ItemTransaksi::join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
                ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
                ->where('transaksi.cabang_id', $branch->id)
                ->where('transaksi.status', 'selesai')
                ->whereBetween('transaksi.created_at', [$periodStart, $periodEnd])
                ->select('produk.nama', DB::raw('SUM(item_transaksi.jumlah) as total_qty'), DB::raw('SUM(item_transaksi.subtotal) as total_revenue'))
                ->groupBy('produk.id', 'produk.nama')
                ->orderByDesc('total_qty')
                ->limit(5)
                ->get();

            // Top categories
            $topCategories = DB::table('item_transaksi')
                ->join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
                ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
                ->join('kategori_produk', 'produk.kategori_id', '=', 'kategori_produk.id')
                ->where('transaksi.cabang_id', $branch->id)
                ->where('transaksi.status', 'selesai')
                ->whereBetween('transaksi.created_at', [$periodStart, $periodEnd])
                ->select('kategori_produk.nama as category', DB::raw('SUM(item_transaksi.subtotal) as total_revenue'))
                ->groupBy('kategori_produk.id', 'kategori_produk.nama')
                ->orderByDesc('total_revenue')
                ->limit(3)
                ->get();

            // Daily data for sparkline
            $dailyData = Transaksi::where('cabang_id', $branch->id)
                ->where('status', 'selesai')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(total) as revenue'),
                    DB::raw('COUNT(*) as transactions')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // Shift stats
            $shiftStats = Shift::where('cabang_id', $branch->id)
                ->whereBetween('waktu_buka', [$periodStart, $periodEnd])
                ->selectRaw('COUNT(*) as total_shifts')
                ->first();
            $totalShifts = (int) ($shiftStats->total_shifts ?? 0);
            $avgRevenuePerShift = $totalShifts > 0 ? $currentRevenue / $totalShifts : 0;

            // Stock alerts
            $lowStockCount = 0;
            $expiringCount = 0;
            
            if (Schema::hasTable('stok_etalase')) {
                $lowStockCount = (int) StokEtalase::where('cabang_id', $branch->id)
                    ->whereColumn('jumlah', '<=', 'stok_minimum')
                    ->count();
            }
            
            if (Schema::hasTable('batch_stok') && Schema::hasTable('stok_etalase')) {
                $expiringCount = (int) BatchStok::join('stok_etalase', 'batch_stok.stok_etalase_id', '=', 'stok_etalase.id')
                    ->where('stok_etalase.cabang_id', $branch->id)
                    ->whereDate('batch_stok.tanggal_kadaluarsa', '<=', Carbon::now()->addDays(30))
                    ->count();
            }

            $branchData[] = [
                'id' => $branch->id,
                'nama' => $branch->nama,
                'kode' => $branch->kode,
                'aktif' => $branch->aktif,
                'total_revenue' => $currentRevenue,
                'total_discount' => (float) ($currentTransaksi->total_discount ?? 0),
                'transaction_count' => $transactionCount,
                'avg_transaction' => round($avgTransaction, 0),
                'growth_percent' => $growthPercent,
                'previous_revenue' => $previousRevenue,
                'top_products' => $topProducts->map(fn($p) => [
                    'nama' => $p->nama,
                    'qty' => (int) $p->total_qty,
                    'revenue' => (float) $p->total_revenue,
                ])->all(),
                'top_categories' => $topCategories->map(fn($c) => [
                    'nama' => $c->category,
                    'revenue' => (float) $c->total_revenue,
                ])->all(),
                'daily_data' => $dailyData->map(fn($d) => [
                    'date' => $d->date,
                    'revenue' => (float) $d->revenue,
                    'transactions' => (int) $d->transactions,
                ])->all(),
                'total_shifts' => $totalShifts,
                'avg_revenue_per_shift' => round($avgRevenuePerShift, 0),
                'low_stock_count' => $lowStockCount,
                'expiring_count' => $expiringCount,
            ];

            $totalRevenue += $currentRevenue;
            $totalTransactions += $transactionCount;
            $totalDiscount += (float) ($currentTransaksi->total_discount ?? 0);
        }

        // Sort by revenue descending
        usort($branchData, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        // Branch comparison chart data
        $branchComparison = array_map(fn($b) => [
            'id' => $b['id'],
            'nama' => $b['nama'],
            'total_revenue' => $b['total_revenue'],
            'transaction_count' => $b['transaction_count'],
            'avg_transaction' => $b['avg_transaction'],
        ], $branchData);

        Log::info('LaporanCabang completed', [
            'period' => $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d'),
            'branches_count' => count($branchData),
            'total_revenue' => $totalRevenue,
        ]);

        return Inertia::render('manager/Reports/Cabang', [
            'cabangs' => $branchData,
            'filters' => [
                'range' => $range,
                'tanggal_mulai' => $periodStart->format('Y-m-d'),
                'tanggal_selesai' => $periodEnd->format('Y-m-d'),
                'cabang_id' => $requestedCabangId,
            ],
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'total_discount' => $totalDiscount,
                'avg_transaction' => $totalTransactions > 0 ? round($totalRevenue / $totalTransactions, 0) : 0,
            ],
            'branchComparison' => $branchComparison,
            'assignedBranches' => $user->cabang->map(fn($c) => [
                'id' => $c->id,
                'nama' => $c->nama,
                'kode' => $c->kode,
                'aktif' => $c->aktif,
            ])->all(),
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
