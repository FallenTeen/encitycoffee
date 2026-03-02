<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use App\Models\Transaksi;
use App\Models\OpenBill;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\Shift;
use App\Models\User;
use App\Models\KategoriProduk;
use App\Models\Pembayaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnhancedDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && method_exists($user, 'isItSupport') && $user->isItSupport()) {
            return redirect()->route('dashboard.admin');
        }
        if ($user && method_exists($user, 'isManager') && $user->isManager()) {
            return redirect()->route('dashboard.manager');
        }
        if ($user && method_exists($user, 'isSupervisor') && $user->isSupervisor()) {
            return redirect()->route('dashboard.supervisor');
        }
        if ($user && property_exists($user, 'role') && $user->role === 'kasir') {
            return redirect()->route('dashboard.kasir');
        }
        return redirect()->route('login');
    }

    /**
     * Admin Dashboard - Comprehensive system overview
     */
    public function adminDashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isItSupport() && !$user->isManager()) {
            abort(403, 'Unauthorized access');
        }

        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $thisYear = Carbon::now()->startOfYear();

        // Key Performance Indicators
        $kpis = [
            'total_revenue' => (float) Transaksi::where('status', 'selesai')->sum('total') ?? 0,
            'today_revenue' => (float) Transaksi::where('status', 'selesai')->whereDate('created_at', $today)->sum('total'),
            'month_revenue' => (float) Transaksi::where('status', 'selesai')->whereDate('created_at', '>=', $thisMonth)->sum('total'),
            'total_branches' => (int) Cabang::count(),
            'active_branches' => (int) Cabang::where('aktif', true)->count(),
            'total_users' => (int) User::count(),
            'active_shifts' => (int) Shift::where('status', 'buka')->count(),
            'today_transactions' => (int) Transaksi::where('status', 'selesai')->whereDate('created_at', $today)->count(),
            'open_bills' => (int) OpenBill::where('status', 'open')->count(),
        ];

        // Revenue Trends (Last 30 days)
        $revenueTrends = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = (float) Transaksi::where('status', 'selesai')
                ->whereDate('created_at', $date)
                ->sum('total');

            $revenueTrends[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d M'),
                'revenue' => $revenue,
            ];
        }

        // Top Performing Branches (Last 7 days)
        $topBranches = Cabang::select('cabang.*')
            ->selectRaw('SUM(transaksi.total) as total_revenue')
            ->selectRaw('COUNT(transaksi.id) as transaction_count')
            ->leftJoin('transaksi', function ($join) {
                $join->on('cabang.id', '=', 'transaksi.cabang_id')
                    ->where('transaksi.status', 'selesai')
                    ->whereDate('transaksi.created_at', '>=', Carbon::today()->subDays(7));
            })
            ->groupBy('cabang.id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Transaction Status Distribution
        $transactionStatus = Transaksi::select('status', DB::raw('COUNT(*) as count'))
            ->whereDate('created_at', '>=', $thisMonth)
            ->groupBy('status')
            ->get();

        // Payment Method Distribution
        $paymentMethods = Pembayaran::select('metode_pembayaran', DB::raw('SUM(jumlah) as total'), DB::raw('COUNT(*) as count'))
            ->whereHas('transaksi', function ($query) {
                $query->where('status', 'selesai');
            })
            ->whereDate('created_at', '>=', $thisMonth)
            ->groupBy('metode_pembayaran')
            ->get();

        // Top Products (Last 30 days)
        $topProducts = DB::table('item_transaksi')
            ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.status', 'selesai')
            ->whereDate('transaksi.created_at', '>=', $thisMonth)
            ->select('produk.nama', DB::raw('SUM(item_transaksi.jumlah) as total_sold'), DB::raw('SUM(item_transaksi.subtotal) as total_revenue'))
            ->groupBy('produk.id', 'produk.nama')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Recent Activity
        $recentActivity = Transaksi::with(['cabang', 'user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'nomor_invoice', 'total', 'status', 'created_at', 'cabang_id', 'user_id']);

        // System Health
        $systemHealth = [
            'low_stock_items' => (int) (Schema::hasTable('stok_etalase')
                ? DB::table('stok_etalase')->whereColumn('jumlah', '<=', 'stok_minimum')->count()
                : 0),
            'expiring_products' => (int) (Schema::hasTable('batch_stoks')
                ? DB::table('batch_stoks')->whereDate('tanggal_kadaluarsa', '<=', Carbon::now()->addDays(30))->count()
                : 0),
            'active_users' => (int) User::where('aktif', true)->count(),
            'system_uptime' => '99.9%', // This would typically come from monitoring
        ];

        return Inertia::render('dashboard/EnhancedAdmin', [
            'kpis' => $kpis,
            'revenueTrends' => $revenueTrends,
            'topBranches' => $topBranches,
            'transactionStatus' => $transactionStatus,
            'paymentMethods' => $paymentMethods,
            'topProducts' => $topProducts,
            'recentActivity' => $recentActivity,
            'systemHealth' => $systemHealth,
            'userRole' => $user->role,
        ]);
    }

    /**
     * Manager Dashboard - Branch performance focus
     */
    public function managerDashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isManager()) {
            abort(403, 'Unauthorized access');
        }

        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // Key Performance Indicators
        $kpis = [
            'today_revenue' => (float) Transaksi::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->sum('total'),
            'month_revenue' => (float) Transaksi::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', '>=', $thisMonth)
                ->sum('total'),
            'today_transactions' => (int) Transaksi::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->count(),
            'active_shifts' => (int) Shift::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'buka')
                ->count(),
            'low_stock_items' => (int) (Schema::hasTable('stok_etalase')
                ? DB::table('stok_etalase')->whereIn('cabang_id', $assignedCabangIds)->whereColumn('jumlah', '<=', 'stok_minimum')->count()
                : 0),
            'expiring_products' => (int) (Schema::hasTable('batch_stoks')
                ? DB::table('batch_stoks')->whereHas('stokEtalase', function ($q) use ($assignedCabangIds) {
                    $q->whereIn('cabang_id', $assignedCabangIds);
                })->whereDate('tanggal_kadaluarsa', '<=', Carbon::now()->addDays(30))->count()
                : 0),
        ];

        // Daily Performance Trends (Last 14 days)
        $dailyPerformance = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = (float) Transaksi::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', $date)
                ->sum('total');

            $transactions = (int) Transaksi::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', $date)
                ->count();

            $dailyPerformance[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('d M'),
                'revenue' => $revenue,
                'transactions' => $transactions,
            ];
        }

        // Branch Comparison (Last 30 days)
        $branchComparison = Cabang::whereIn('cabang.id', $assignedCabangIds)
            ->select('cabang.*')
            ->selectRaw('SUM(transaksi.total) as total_revenue')
            ->selectRaw('COUNT(transaksi.id) as transaction_count')
            ->selectRaw('AVG(transaksi.total) as avg_transaction')
            ->leftJoin('transaksi', function ($join) {
                $join->on('cabang.id', '=', 'transaksi.cabang_id')
                    ->where('transaksi.status', 'selesai')
                    ->whereDate('transaksi.created_at', '>=', Carbon::today()->subDays(30));
            })
            ->groupBy('cabang.id')
            ->orderByDesc('total_revenue')
            ->get();

        // Category Performance
        $categoryPerformance = DB::table('item_transaksi')
            ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->join('kategori_produk', 'produk.kategori_id', '=', 'kategori_produk.id')
            ->join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
            ->whereIn('transaksi.cabang_id', $assignedCabangIds)
            ->where('transaksi.status', 'selesai')
            ->whereDate('transaksi.created_at', '>=', $thisMonth)
            ->select('kategori_produk.nama as category', DB::raw('SUM(item_transaksi.subtotal) as total_revenue'), DB::raw('SUM(item_transaksi.jumlah) as total_quantity'))
            ->groupBy('kategori_produk.id', 'kategori_produk.nama')
            ->orderByDesc('total_revenue')
            ->get();

        // Staff Performance (Last 30 days)
        $staffPerformance = User::whereIn('users.id', function ($query) use ($assignedCabangIds) {
                $query->select('user_id')
                    ->from('transaksi')
                    ->whereIn('cabang_id', $assignedCabangIds)
                    ->whereDate('created_at', '>=', Carbon::today()->subDays(30))
                    ->distinct();
            })
            ->select('users.*')
            ->selectRaw('SUM(transaksi.total) as total_revenue')
            ->selectRaw('COUNT(transaksi.id) as transaction_count')
            ->selectRaw('AVG(transaksi.total) as avg_transaction')
            ->leftJoin('transaksi', function ($join) use ($assignedCabangIds) {
                $join->on('users.id', '=', 'transaksi.user_id')
                    ->whereIn('transaksi.cabang_id', $assignedCabangIds)
                    ->where('transaksi.status', 'selesai')
                    ->whereDate('transaksi.created_at', '>=', Carbon::today()->subDays(30));
            })
            ->groupBy('users.id')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Operational Alerts
        $operationalAlerts = [
            'low_stock_branches' => Cabang::whereIn('cabang.id', $assignedCabangIds)
                ->whereHas('stokEtalase', function ($query) {
                    $query->whereColumn('jumlah', '<=', 'stok_minimum');
                })
                ->count(),
            'overdue_maintenance' => 0, // This would come from maintenance tracking
            'staff_absence' => 0, // This would come from attendance system
        ];

        return Inertia::render('dashboard/EnhancedManager', [
            'kpis' => $kpis,
            'dailyPerformance' => $dailyPerformance,
            'branchComparison' => $branchComparison,
            'categoryPerformance' => $categoryPerformance,
            'staffPerformance' => $staffPerformance,
            'operationalAlerts' => $operationalAlerts,
            'assignedBranches' => $user->cabang,
        ]);
    }

    /**
     * Supervisor Dashboard - Operational oversight
     */
    public function supervisorDashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isSupervisor()) {
            abort(403, 'Unauthorized access');
        }

        $today = Carbon::today();
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // Current Shift Status
        $currentShifts = Shift::whereIn('cabang_id', $assignedCabangIds)
            ->whereDate('waktu_buka', $today)
            ->with(['cabang', 'user'])
            ->orderBy('waktu_buka')
            ->get();

        // Staff Status
        $staffStatus = User::whereIn('role', ['kasir', 'supervisor'])
            ->whereHas('cabang', function ($query) use ($assignedCabangIds) {
                $query->whereIn('cabang.id', $assignedCabangIds);
            })
            ->with(['cabang', 'shift' => function ($query) use ($today) {
                $query->whereDate('waktu_buka', $today);
            }])
            ->get();

        // Hourly Performance (Today)
        $hourlyPerformance = [];
        for ($hour = 6; $hour <= 22; $hour++) {
            $startTime = Carbon::today()->setHour($hour)->setMinute(0);
            $endTime = $startTime->copy()->addHour();

            $revenue = (float) Transaksi::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'selesai')
                ->whereBetween('created_at', [$startTime, $endTime])
                ->sum('total');

            $transactions = (int) Transaksi::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'selesai')
                ->whereBetween('created_at', [$startTime, $endTime])
                ->count();

            $hourlyPerformance[] = [
                'hour' => $hour,
                'time' => $startTime->format('H:00'),
                'revenue' => $revenue,
                'transactions' => $transactions,
            ];
        }

        // Open Bills Status
        $openBills = OpenBill::whereIn('cabang_id', $assignedCabangIds)
            ->where('status', 'open')
            ->with(['cabang', 'user'])
            ->orderBy('created_at')
            ->get();

        // Recent Transactions
        $recentTransactions = Transaksi::whereIn('cabang_id', $assignedCabangIds)
            ->with(['cabang', 'user'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'nomor_invoice', 'total', 'status', 'created_at', 'cabang_id', 'user_id']);

        // Operational Metrics
        $operationalMetrics = [
            'active_staff' => (int) User::whereIn('role', ['kasir', 'supervisor'])
                ->whereHas('shift', function ($query) use ($today) {
                    $query->whereDate('waktu_buka', $today)
                        ->where('status', 'buka');
                })
                ->count(),
            'total_staff' => (int) User::whereIn('role', ['kasir', 'supervisor'])
                ->whereHas('cabang', function ($query) use ($assignedCabangIds) {
                    $query->whereIn('cabang.id', $assignedCabangIds);
                })
                ->count(),
            'open_bills_count' => $openBills->count(),
            'avg_bill_value' => (float) OpenBill::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'open')
                ->avg('total') ?? 0,
        ];

        // Performance Alerts
        $performanceAlerts = [
            'low_performance_branches' => (int) \DB::table('transaksi')
                ->select('cabang_id')
                ->whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->groupBy('cabang_id')
                ->havingRaw('SUM(total) < ?', [1000000])
                ->pluck('cabang_id')
                ->count(),
            'long_open_bills' => OpenBill::whereIn('cabang_id', $assignedCabangIds)
                ->where('status', 'open')
                ->where('created_at', '<', Carbon::now()->subHours(2))
                ->count(),
        ];

        return Inertia::render('dashboard/EnhancedSupervisor', [
            'currentShifts' => $currentShifts,
            'staffStatus' => $staffStatus,
            'hourlyPerformance' => $hourlyPerformance,
            'openBills' => $openBills,
            'recentTransactions' => $recentTransactions,
            'operationalMetrics' => $operationalMetrics,
            'performanceAlerts' => $performanceAlerts,
        ]);
    }

    /**
     * Cashier Dashboard - Personal performance focus
     */
    public function kasirDashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isKasir()) {
            abort(403, 'Unauthorized access');
        }

        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Personal Performance KPIs
        $kpis = [
            'today_revenue' => (float) Transaksi::where('user_id', $user->id)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->sum('total'),
            'today_transactions' => (int) Transaksi::where('user_id', $user->id)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->count(),
            'month_revenue' => (float) Transaksi::where('user_id', $user->id)
                ->where('status', 'selesai')
                ->whereDate('created_at', '>=', $thisMonth)
                ->sum('total'),
            'avg_transaction' => (float) Transaksi::where('user_id', $user->id)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->avg('total') ?? 0,
            'current_shift_revenue' => (float) Transaksi::where('user_id', $user->id)
                ->where('status', 'selesai')
                ->where('shift_id', optional($user->shift)->id)
                ->sum('total'),
        ];

        // Hourly Performance (Today)
        $hourlyPerformance = [];
        for ($hour = 6; $hour <= 22; $hour++) {
            $startTime = Carbon::today()->setHour($hour)->setMinute(0);
            $endTime = $startTime->copy()->addHour();

            $revenue = (float) Transaksi::where('user_id', $user->id)
                ->where('status', 'selesai')
                ->whereBetween('created_at', [$startTime, $endTime])
                ->sum('total');

            $transactions = (int) Transaksi::where('user_id', $user->id)
                ->where('status', 'selesai')
                ->whereBetween('created_at', [$startTime, $endTime])
                ->count();

            $hourlyPerformance[] = [
                'hour' => $hour,
                'time' => $startTime->format('H:00'),
                'revenue' => $revenue,
                'transactions' => $transactions,
            ];
        }

        // Top Products Sold (Personal)
        $topProducts = DB::table('item_transaksi')
            ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.user_id', $user->id)
            ->where('transaksi.status', 'selesai')
            ->whereDate('transaksi.created_at', '>=', $today)
            ->select('produk.nama', DB::raw('SUM(item_transaksi.jumlah) as total_sold'), DB::raw('SUM(item_transaksi.subtotal) as total_revenue'))
            ->groupBy('produk.id', 'produk.nama')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        // Shift Performance Comparison
        $shiftPerformance = Shift::where('user_id', $user->id)
            ->whereDate('waktu_buka', '>=', $thisMonth)
            ->with(['cabang'])
            ->select('shifts.*')
            ->selectRaw('SUM(transaksi.total) as total_revenue')
            ->selectRaw('COUNT(transaksi.id) as transaction_count')
            ->leftJoin('transaksi', function ($join) {
                $join->on('shifts.id', '=', 'transaksi.shift_id')
                    ->where('transaksi.status', 'selesai');
            })
            ->groupBy('shifts.id')
            ->orderByDesc('waktu_buka')
            ->limit(10)
            ->get();

        // Recent Transactions (Personal)
        $recentTransactions = Transaksi::where('user_id', $user->id)
            ->with(['cabang'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'nomor_invoice', 'total', 'status', 'created_at', 'cabang_id']);

        // Performance Tips
        $performanceTips = $this->generatePerformanceTips($user, $today);

        // Current Shift Status
        $currentShift = Shift::where('user_id', $user->id)
            ->where('status', 'buka')
            ->with(['cabang'])
            ->first();

        return Inertia::render('dashboard/EnhancedKasir', [
            'kpis' => $kpis,
            'hourlyPerformance' => $hourlyPerformance,
            'topProducts' => $topProducts,
            'shiftPerformance' => $shiftPerformance,
            'recentTransactions' => $recentTransactions,
            'performanceTips' => $performanceTips,
            'currentShift' => $currentShift,
        ]);
    }

    /**
     * Real-time data endpoint for dashboard updates
     */
    public function getRealtimeData(Request $request)
    {
        $user = $request->user();
        $type = $request->get('type', 'general');
        $interval = $request->get('interval', 60); // seconds

        $data = [];

        switch ($user->role) {
            case 'admin':
            case 'it_support':
                $data = $this->getAdminRealtimeData($type);
                break;
            case 'manager':
                $data = $this->getManagerRealtimeData($user, $type);
                break;
            case 'supervisor':
                $data = $this->getSupervisorRealtimeData($user, $type);
                break;
            case 'kasir':
                $data = $this->getKasirRealtimeData($user, $type);
                break;
        }

        return response()->json([
            'data' => $data,
            'timestamp' => Carbon::now()->toISOString(),
            'interval' => $interval,
        ]);
    }

    private function getAdminRealtimeData($type)
    {
        $today = Carbon::today();

        switch ($type) {
            case 'revenue':
                return [
                    'today_revenue' => (float) Transaksi::where('status', 'selesai')->whereDate('created_at', $today)->sum('total'),
                    'active_transactions' => (int) Transaksi::where('status', 'pending')->count(),
                ];
            case 'system':
                return [
                    'active_shifts' => (int) Shift::where('status', 'buka')->count(),
                    'online_users' => (int) User::where('aktif', true)->count(),
                    'system_load' => rand(20, 80), // This would come from monitoring
                ];
            default:
                return [
                    'today_revenue' => (float) Transaksi::where('status', 'selesai')->whereDate('created_at', $today)->sum('total'),
                    'active_transactions' => (int) Transaksi::where('status', 'pending')->count(),
                    'active_shifts' => (int) Shift::where('status', 'buka')->count(),
                ];
        }
    }

    private function getManagerRealtimeData($user, $type)
    {
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();
        $today = Carbon::today();

        switch ($type) {
            case 'revenue':
                return [
                    'today_revenue' => (float) Transaksi::whereIn('cabang_id', $assignedCabangIds)->where('status', 'selesai')->whereDate('created_at', $today)->sum('total'),
                    'active_transactions' => (int) Transaksi::whereIn('cabang_id', $assignedCabangIds)->where('status', 'pending')->count(),
                ];
            case 'operations':
                return [
                    'active_shifts' => (int) Shift::whereIn('cabang_id', $assignedCabangIds)->where('status', 'buka')->count(),
                    'open_bills' => (int) OpenBill::whereIn('cabang_id', $assignedCabangIds)->where('status', 'open')->count(),
                    'low_stock_alerts' => (int) (Schema::hasTable('stok_etalase')
                        ? DB::table('stok_etalase')->whereIn('cabang_id', $assignedCabangIds)->whereColumn('jumlah', '<=', 'stok_minimum')->count()
                        : 0),
                ];
            default:
                return [
                    'today_revenue' => (float) Transaksi::whereIn('cabang_id', $assignedCabangIds)->where('status', 'selesai')->whereDate('created_at', $today)->sum('total'),
                    'active_shifts' => (int) Shift::whereIn('cabang_id', $assignedCabangIds)->where('status', 'buka')->count(),
                ];
        }
    }

    private function getSupervisorRealtimeData($user, $type)
    {
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();
        $today = Carbon::today();

        switch ($type) {
            case 'shifts':
                return [
                    'active_shifts' => Shift::whereIn('cabang_id', $assignedCabangIds)->where('status', 'buka')->with(['cabang', 'user'])->get(),
                    'recent_transactions' => Transaksi::whereIn('cabang_id', $assignedCabangIds)->with(['cabang', 'user'])->orderByDesc('created_at')->limit(5)->get(),
                ];
            case 'staff':
                return [
                    'active_staff' => User::whereIn('role', ['kasir', 'supervisor'])->whereHas('shift', function ($query) use ($assignedCabangIds, $today) {
                        $query->whereIn('cabang_id', $assignedCabangIds)->whereDate('waktu_buka', $today)->where('status', 'buka');
                    })->with(['cabang'])->get(),
                ];
            default:
                return [
                    'active_shifts' => (int) Shift::whereIn('cabang_id', $assignedCabangIds)->where('status', 'buka')->count(),
                    'open_bills' => (int) OpenBill::whereIn('cabang_id', $assignedCabangIds)->where('status', 'open')->count(),
                ];
        }
    }

    private function getKasirRealtimeData($user, $type)
    {
        $today = Carbon::today();

        switch ($type) {
            case 'performance':
                return [
                    'today_revenue' => (float) Transaksi::where('user_id', $user->id)->where('status', 'selesai')->whereDate('created_at', $today)->sum('total'),
                    'today_transactions' => (int) Transaksi::where('user_id', $user->id)->where('status', 'selesai')->whereDate('created_at', $today)->count(),
                    'current_shift_revenue' => (float) Transaksi::where('user_id', $user->id)->where('status', 'selesai')->where('shift_id', optional($user->shift)->id)->sum('total'),
                ];
            default:
                return [
                    'today_revenue' => (float) Transaksi::where('user_id', $user->id)->where('status', 'selesai')->whereDate('created_at', $today)->sum('total'),
                    'today_transactions' => (int) Transaksi::where('user_id', $user->id)->where('status', 'selesai')->whereDate('created_at', $today)->count(),
                ];
        }
    }

    private function generatePerformanceTips($user, $date)
    {
        $tips = [];

        // Analyze performance and generate tips
        $avgTransaction = (float) Transaksi::where('user_id', $user->id)
            ->where('status', 'selesai')
            ->whereDate('created_at', $date)
            ->avg('total') ?? 0;

        if ($avgTransaction < 50000) {
            $tips[] = 'Coba tawarkan produk tambahan untuk meningkatkan nilai transaksi';
        }

        $transactionCount = (int) Transaksi::where('user_id', $user->id)
            ->where('status', 'selesai')
            ->whereDate('created_at', $date)
            ->count();

        if ($transactionCount < 20) {
            $tips[] = 'Kecepatan layanan bisa ditingkatkan untuk melayani lebih banyak pelanggan';
        }

        // Add more tips based on various metrics
        $tips[] = 'Selalu konfirmasi pesanan pelanggan untuk menghindari kesalahan';
        $tips[] = 'Jaga kebersihan dan kerapian area kerja';

        return array_slice($tips, 0, 3); // Return top 3 tips
    }
}
