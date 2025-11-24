<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use App\Models\Transaksi;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && method_exists($user, 'isItSupport') && $user->isItSupport()) {
            abort_unless(Gate::allows('view-admin-dashboard'), 403);

            $today = Carbon::today();
            $totalRevenue = (float) (Transaksi::where('status', 'selesai')->sum('total') ?? 0);
            $cabangCount = (int) Cabang::count();
            $userCount = (int) User::count();
            $cabangAktif = (int) Cabang::where('aktif', true)->count();
            $shiftHariIni = (int) Shift::whereDate('waktu_buka', $today)->count();
            $transaksiHariIni = (float) Transaksi::where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->sum('total');


            $grafik7Hari = [];
            for ($i = 6; $i >= 0; $i--) {
                $tanggal = Carbon::today()->subDays($i);
                $total = (float) Transaksi::where('status', 'selesai')
                    ->whereDate('created_at', $tanggal)
                    ->sum('total');
                $grafik7Hari[] = [
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'total' => $total,
                ];
            }


            $logAktivitas = Transaksi::orderByDesc('created_at')
                ->take(10)
                ->get(['id','nomor_invoice','total','status','created_at']);

            return Inertia::render('dashboard/Admin', [
                'user' => $user,
                'total_cabang' => $cabangCount,
                'total_user' => $userCount,
                'cabang_aktif' => $cabangAktif,
                'shift_hari_ini' => $shiftHariIni,
                'transaksi_hari_ini' => $transaksiHariIni,
                'grafik_penjualan_7_hari' => $grafik7Hari,
                'log_aktivitas' => $logAktivitas,
                'totalRevenue' => $totalRevenue, // tetap kirim untuk kompatibilitas
            ]);
        }

        if ($user && method_exists($user, 'isManager') && $user->isManager()) {
            abort_unless(Gate::allows('view-manager-dashboard'), 403);

            $today = Carbon::today();
            $cabangIds = $user->cabang->pluck('id')->all();

            $shiftAktif = (int) Shift::whereIn('cabang_id', $cabangIds)
                ->where('status', 'buka')
                ->count();

            $penjualanHariIni = (float) Transaksi::whereIn('cabang_id', $cabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->sum('total');

            $transaksiHariIni = (int) Transaksi::whereIn('cabang_id', $cabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->count();

            $stokRendah = (int) \App\Models\StokEtalase::whereIn('cabang_id', $cabangIds)
                ->whereColumn('jumlah', '<=', 'stok_minimum')
                ->count();

            $kadaluarsaAlert = (int) \App\Models\BatchStok::whereHas('stokEtalase', function ($q) use ($cabangIds) {
                    $q->whereIn('cabang_id', $cabangIds);
                })
                ->whereDate('tanggal_kadaluarsa', '<=', Carbon::now()->addDays(30))
                ->count();


            $grafikPenjualan = [];
            for ($i = 6; $i >= 0; $i--) {
                $tanggal = Carbon::today()->subDays($i);
                $total = (float) Transaksi::whereIn('cabang_id', $cabangIds)
                    ->where('status', 'selesai')
                    ->whereDate('created_at', $tanggal)
                    ->sum('total');
                $grafikPenjualan[] = [
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'total' => $total,
                ];
            }


            $tanggalMulai = Carbon::today()->subDays(30);
            $topProduk = \App\Models\ItemTransaksi::whereHas('transaksi', function ($q) use ($cabangIds, $tanggalMulai) {
                    $q->whereIn('cabang_id', $cabangIds)
                      ->where('status', 'selesai')
                      ->whereDate('created_at', '>=', $tanggalMulai);
                })
                ->selectRaw('produk_id, SUM(jumlah) as total_terjual')
                ->groupBy('produk_id')
                ->orderByDesc('total_terjual')
                ->take(10)
                ->with('produk:id,nama,harga_jual')
                ->get();

            return Inertia::render('dashboard/Manager', [
                'user' => $user,
                'total_cabang_kelola' => count($cabangIds),
                'shift_aktif' => $shiftAktif,
                'penjualan_hari_ini' => $penjualanHariIni,
                'transaksi_hari_ini' => $transaksiHariIni,
                'stok_rendah' => $stokRendah,
                'kadaluarsa_alert' => $kadaluarsaAlert,
                'grafik_penjualan' => $grafikPenjualan,
                'top_produk' => $topProduk,
            ]);
        }

        if ($user && method_exists($user, 'isSupervisor') && $user->isSupervisor()) {
            abort_unless(Gate::allows('view-supervisor-dashboard'), 403);

            $cabangIds = $user->cabang->pluck('id')->all();
            $today = Carbon::today();

            $shiftAktif = Shift::whereIn('cabang_id', $cabangIds)
                ->where('status', 'buka')
                ->with(['user:id,name', 'cabang:id,nama'])
                ->get();

            $totalTransaksiHariIni = (int) Transaksi::whereIn('cabang_id', $cabangIds)
                ->where('status', 'selesai')
                ->whereDate('created_at', $today)
                ->count();

            $totalPenjualan = (float) Transaksi::whereIn('cabang_id', $cabangIds)
                ->where('status', 'selesai')
                ->sum('total');

            $stokRendah = \App\Models\StokEtalase::whereIn('cabang_id', $cabangIds)
                ->whereColumn('jumlah', '<=', 'stok_minimum')
                ->count();

            $mendekatiKadaluarsa = \App\Models\BatchStok::whereHas('stokEtalase', function ($q) use ($cabangIds) {
                    $q->whereIn('cabang_id', $cabangIds);
                })
                ->whereDate('tanggal_kadaluarsa', '<=', Carbon::now()->addDays(7))
                ->count();

            $transaksiTerbaru = Transaksi::whereIn('cabang_id', $cabangIds)
                ->orderByDesc('created_at')
                ->take(10)
                ->get(['id','nomor_invoice','total','status','created_at']);


            $kasirAktif = User::whereHas('shift', function ($q) {
                    $q->where('status', 'buka');
                })
                ->get(['id','name','role']);

            return Inertia::render('dashboard/Supervisor', [
                'user' => $user,
                'shift_aktif' => $shiftAktif,
                'total_transaksi_hari_ini' => $totalTransaksiHariIni,
                'total_penjualan' => $totalPenjualan,
                'stok_alert' => [
                    'stok_rendah' => (int) $stokRendah,
                    'mendekati_kadaluarsa' => (int) $mendekatiKadaluarsa,
                ],
                'transaksi_terbaru' => $transaksiTerbaru,
                'kasir_aktif' => $kasirAktif,
            ]);
        }

        if ($user && method_exists($user, 'isKasir') && $user->isKasir()) {
            abort_unless(Gate::allows('view-kasir-dashboard'), 403);

            $activeShift = Shift::where('user_id', $user->id)->where('status', 'buka')->first();

            $transaksiShiftIni = 0;
            $penjualanShiftIni = 0.0;
            $grafikPerJam = [];

            if ($activeShift) {
                $transaksiQuery = Transaksi::where('shift_id', $activeShift->id)->where('status', 'selesai');
                $transaksiShiftIni = (int) $transaksiQuery->count();
                $penjualanShiftIni = (float) $transaksiQuery->sum('total');

                // Grafik per jam untuk shift aktif
                $records = $transaksiQuery->get(['id','total','created_at']);
                $byHour = [];
                foreach ($records as $r) {
                    $hour = Carbon::parse($r->created_at)->format('H:00');
                    $byHour[$hour] = ($byHour[$hour] ?? 0) + (float) $r->total;
                }
                // Sort by hour ascending
                ksort($byHour);
                foreach ($byHour as $hour => $total) {
                    $grafikPerJam[] = ['jam' => $hour, 'total' => $total];
                }
            }

            return Inertia::render('dashboard/Kasir', [
                'user' => $user,
                'shift_saya' => $activeShift,
                'transaksi_shift_ini' => $transaksiShiftIni,
                'penjualan_shift_ini' => $penjualanShiftIni,
                'grafik_per_jam' => $grafikPerJam,
            ]);
        }

        return Inertia::render('dashboard', [
            'user' => $user,
        ]);
    }
}
