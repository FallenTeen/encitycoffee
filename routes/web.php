<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\KalibrasiController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\KategoriProdukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LandingController;
use Inertia\Inertia;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\EnhancedDashboardController;
use App\Http\Controllers\BundlingController;
use Illuminate\Support\Facades\Auth;
use App\Models\Cabang;
use App\Services\StokService;

$mainDomain = config('app.domain') ?: parse_url((string) config('app.url'), PHP_URL_HOST);
$backofficeDomain = config('app.backoffice_domain') ?: ($mainDomain ? ('web.' . $mainDomain) : null);
$isLocal = app()->environment('local');
$isRunningInConsole = app()->runningInConsole(); // NEW: Check if running artisan command (like wayfinder:generate)
$skipDomainConstraints = $isLocal || $isRunningInConsole; // NEW: Skip domains for local OR console

// ============================================================================
// LANDING PAGE ROUTES
// ============================================================================
$landingRouteCallback = function () {
    Route::get('/', [LandingController::class, 'home'])->name('landing.home');
    Route::get('/about', [LandingController::class, 'about'])->name('landing.about');
    Route::get('/menu', [LandingController::class, 'menu'])->name('landing.menu');
    Route::get('/cabang/{slug}', [LandingController::class, 'cabangMenu'])->name('landing.cabang');
    Route::get('/cabang/{slug}/{kategori}', [LandingController::class, 'cabangMenu'])->name('landing.cabang.kategori');
    Route::get('/menupercabang', [LandingController::class, 'menupercabang'])->name('landing.menupercabang.index');
    Route::get('/menupercabang/{namaCabang}', [LandingController::class, 'menupercabang'])->name('landing.menupercabang.show');
};

if ($skipDomainConstraints) {
    $landingRouteCallback();
} else {
    Route::domain($mainDomain ?: '__invalid.local')->group($landingRouteCallback);
}

// ============================================================================
// BACKOFFICE ROUTES
// ============================================================================
$backofficeRouteCallback = function () {
    Route::get('/', function () {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('login');
    })->name('home');

    // ============================================================================
    // AUTHENTICATED ROUTES
    // ============================================================================
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [EnhancedDashboardController::class, 'index'])->name('dashboard');
        // ------------------------------------------------------------------------
        // ENHANCED DASHBOARD ROUTES (all authenticated users)
        // Prefix: /dashboard
        // ------------------------------------------------------------------------
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/admin', [EnhancedDashboardController::class, 'adminDashboard'])->name('admin')->middleware('role:it_support');
            Route::get('/manager', [EnhancedDashboardController::class, 'managerDashboard'])->name('manager')->middleware('role:manager');
            Route::get('/supervisor', [EnhancedDashboardController::class, 'supervisorDashboard'])->name('supervisor')->middleware('role:supervisor');
            Route::get('/kasir', [EnhancedDashboardController::class, 'kasirDashboard'])->name('kasir')->middleware('role:kasir');

            // Real-time data endpoints
            Route::get('/realtime-data', [EnhancedDashboardController::class, 'getRealtimeData'])->name('realtime-data');
            Route::get('/realtime', [EnhancedDashboardController::class, 'getRealtimeData']);

            // Legacy dashboard routes (for backward compatibility)
            Route::get('/legacy', [DashboardController::class, 'index'])->name('legacy');
            Route::get('/legacy/admin', [DashboardController::class, 'admin'])->name('legacy.admin')->middleware('role:admin');
            Route::get('/legacy/manager', [DashboardController::class, 'manager'])->name('legacy.manager')->middleware('role:manager');
            Route::get('/legacy/supervisor', [DashboardController::class, 'supervisor'])->name('legacy.supervisor')->middleware('role:supervisor');
            Route::get('/legacy/kasir', [DashboardController::class, 'kasir'])->name('legacy.kasir')->middleware('role:kasir');
        });

        // ------------------------------------------------------------------------
        // IT SUPPORT ROUTES (it_support only)
        // Prefix: /admin
        // ------------------------------------------------------------------------
        Route::middleware('role:it_support')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [EnhancedDashboardController::class, 'adminDashboard'])->name('dashboard');
            Route::get('/system-logs', [SystemController::class, 'logs'])->name('system.logs');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::post('/users/{user}/assign-cabang', [UserController::class, 'assignCabang'])->name('users.assign-cabang');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
            Route::get('/cabang', [CabangController::class, 'index'])->name('cabang.index');
            Route::get('/cabang/create', [CabangController::class, 'create'])->name('cabang.create');
            Route::post('/cabang', [CabangController::class, 'store'])->name('cabang.store');
            Route::get('/cabang/{cabang}/edit', [CabangController::class, 'edit'])->name('cabang.edit');
            Route::put('/cabang/{cabang}', [CabangController::class, 'update'])->name('cabang.update');
            Route::delete('/cabang/{cabang}', [CabangController::class, 'destroy'])->name('cabang.destroy');
            Route::get('/cabang/{cabang}', [CabangController::class, 'show'])->name('cabang.show');
            Route::post('/cabang/{cabang}/users', [CabangController::class, 'attachUser'])->name('cabang.users.attach');
            Route::delete('/cabang/{cabang}/users/{user}', [CabangController::class, 'detachUser'])->name('cabang.users.detach');
            Route::post('/cabang/{cabang}/manager', [CabangController::class, 'setManager'])->name('cabang.manager.set');
            Route::delete('/cabang/{cabang}/manager', [CabangController::class, 'removeManager'])->name('cabang.manager.remove');
            Route::post('/cabang/{cabang}/supervisor', [CabangController::class, 'setSupervisor'])->name('cabang.supervisor.set');
            Route::delete('/cabang/{cabang}/supervisor', [CabangController::class, 'removeSupervisor'])->name('cabang.supervisor.remove');

            // Soft delete management routes
            Route::get('/deleted-transactions', function () {
                return Inertia::render('admin/DeletedItemsManager', ['type' => 'transaksi']);
            })->name('deleted-transactions');

            Route::get('/deleted-bills', function () {
                return Inertia::render('admin/DeletedItemsManager', ['type' => 'open-bill']);
            })->name('deleted-bills');
        });

        // ------------------------------------------------------------------------
        // MANAGER ROUTES (manager, it_support)
        // Prefix: /manager
        // ------------------------------------------------------------------------
        Route::middleware('role:manager,it_support')->prefix('manager')->name('manager.')->group(function () {
            Route::get('/dashboard', function () {
                return redirect()->route('dashboard.manager');
            })->name('dashboard');
            Route::get('/laporan-cabang', [ManagerController::class, 'laporanCabang'])->name('laporan.cabang');
            Route::get('/performa-shift', [ManagerController::class, 'perfomaShift'])->name('performa.shift');
            
            // Unified Karyawan (Employee) Management - combines Supervisor and Kasir
            Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
            Route::get('/karyawan/create', [KaryawanController::class, 'create'])->name('karyawan.create');
            Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
            Route::get('/karyawan/{karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');
            Route::get('/karyawan/{karyawan}/edit', [KaryawanController::class, 'edit'])->name('karyawan.edit');
            Route::put('/karyawan/{karyawan}', [KaryawanController::class, 'update'])->name('karyawan.update');
            Route::delete('/karyawan/{karyawan}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
            
            // Legacy routes (keeping for backward compatibility)
            Route::get('/kasir', [KasirController::class, 'indexManager'])->name('kasir.index');
            Route::get('/kasir/create', [KasirController::class, 'createManager'])->name('kasir.create');
            Route::post('/kasir', [KasirController::class, 'storeManager'])->name('kasir.store');
            Route::get('/kasir/{kasir}/edit', [KasirController::class, 'editManager'])->name('kasir.edit');
            Route::put('/kasir/{kasir}', [KasirController::class, 'updateManager'])->name('kasir.update');
            Route::delete('/kasir/{kasir}', [KasirController::class, 'destroyManager'])->name('kasir.destroy');
            Route::get('/supervisor', [SupervisorController::class, 'index'])->name('supervisor.index');
            Route::get('/supervisor/create', [SupervisorController::class, 'create'])->name('supervisor.create');
            Route::post('/supervisor', [SupervisorController::class, 'store'])->name('supervisor.store');
            Route::get('/supervisor/{supervisor}/edit', [SupervisorController::class, 'edit'])->name('supervisor.edit');
            Route::put('/supervisor/{supervisor}', [SupervisorController::class, 'update'])->name('supervisor.update');
            Route::delete('/supervisor/{supervisor}', [SupervisorController::class, 'destroy'])->name('supervisor.destroy');
            Route::get('/supervisor/{supervisor}', [SupervisorController::class, 'show'])->name('supervisor.show');
        });

        // ------------------------------------------------------------------------
        // PRODUK ROUTES (manager, it_support, supervisor, kasir)
        // Prefix: /produk
        // CRITICAL: ALL static routes MUST come BEFORE dynamic {produk} routes
        // ------------------------------------------------------------------------
        Route::middleware('role:manager,it_support,supervisor,kasir')->prefix('produk')->name('produk.')->group(function () {
            // INDEX ROUTE - Tampilan utama (bisa pilih cabang via query ?cabang_id=1)
            Route::get('/', [ProdukController::class, 'index'])->name('index');

            // STATIC ROUTES (harus di atas dynamic routes)
            Route::get('/create', [ProdukController::class, 'create'])->name('create');
            Route::post('/', [ProdukController::class, 'store'])->name('store');
            Route::get('/sku-suggest', [ProdukController::class, 'suggestSku'])->name('sku.suggest');
            Route::get('/check-sku', [ProdukController::class, 'checkSku'])->name('sku.check');
            Route::get('/kelompok-nama', [ProdukController::class, 'getKelompokNama'])->name('kelompok-nama');

            // Cache management route
            Route::post('/cache/clear', [ProdukController::class, 'clearCache'])->name('cache.clear');

            // Bundling routes (nested under /produk)
            Route::get('/bundling', [BundlingController::class, 'index'])->name('bundling.index');
            Route::get('/bundling/create', [BundlingController::class, 'create'])->name('bundling.create');
            Route::post('/bundling', [BundlingController::class, 'store'])->name('bundling.store');
            Route::get('/bundling/{bundling}/edit', [BundlingController::class, 'edit'])->name('bundling.edit');
            Route::put('/bundling/{bundling}', [BundlingController::class, 'update'])->name('bundling.update');
            Route::delete('/bundling/{bundling}', [BundlingController::class, 'destroy'])->name('bundling.destroy');

            // Kategori routes
            Route::get('/kategori', [KategoriProdukController::class, 'index'])->name('kategori.index');
            Route::get('/kategori/create', [KategoriProdukController::class, 'create'])->name('kategori.create');
            Route::post('/kategori', [KategoriProdukController::class, 'store'])->name('kategori.store');
            Route::get('/kategori/{kategori}', [KategoriProdukController::class, 'show'])->name('kategori.show');
            Route::get('/kategori/{kategori}/edit', [KategoriProdukController::class, 'edit'])->name('kategori.edit');
            Route::put('/kategori/{kategori}', [KategoriProdukController::class, 'update'])->name('kategori.update');
            Route::delete('/kategori/{kategori}', [KategoriProdukController::class, 'destroy'])->name('kategori.destroy');

            // Satuan routes
            Route::delete('/satuan/{satuan}', [ProdukController::class, 'deleteSatuan'])->name('satuan.delete');
            Route::post('/{produk}/satuan', [ProdukController::class, 'addSatuan'])->name('satuan.store');

            Route::post('/{produk}/toggle-aktif', [ProdukController::class, 'toggleAktif'])->name('toggle-aktif');

            // DYNAMIC ROUTES (harus di bawah semua static routes)
            Route::get('/{produk}/edit', [ProdukController::class, 'edit'])->name('edit');
            Route::put('/{produk}', [ProdukController::class, 'update'])->name('update');
            Route::delete('/{produk}', [ProdukController::class, 'destroy'])->name('destroy');
            Route::get('/{produk}', [ProdukController::class, 'show'])->name('show');
        });

        // ------------------------------------------------------------------------
        // STOK ROUTES (supervisor, manager, it_support)
        // Prefix: /stok
        // CRITICAL: Static routes BEFORE dynamic routes
        // ------------------------------------------------------------------------
        Route::middleware('role:supervisor,manager,it_support')->prefix('stok')->name('stok.')->group(function () {
            Route::get('/', [StokController::class, 'index'])->name('index');
            Route::post('/tambah', [StokController::class, 'store'])->name('store');
            Route::post('/sesuaikan', [StokController::class, 'adjust'])->name('adjust');
            Route::get('/rendah', [StokController::class, 'rendah'])->name('rendah');
            Route::get('/kadaluarsa', [StokController::class, 'kadaluarsa'])->name('kadaluarsa');
            Route::get('/cabang/{cabang}', [StokController::class, 'byCabang'])->name('by-cabang');
            Route::get('/mutasi/{stokEtalase}', [StokController::class, 'mutasi'])->name('mutasi');
        });

        // ------------------------------------------------------------------------
        // LAPORAN ROUTES (supervisor, manager, it_support)
        // Prefix: /laporan
        // CRITICAL: Static routes BEFORE dynamic routes
        // ------------------------------------------------------------------------
        Route::middleware('role:supervisor,manager,it_support')->prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/shift', [LaporanController::class, 'shift'])->name('shift');
            Route::get('/transaksi', [LaporanController::class, 'transaksi'])->name('transaksi');
            Route::get('/harian', [LaporanController::class, 'harian'])->name('harian');
            Route::get('/riwayat-transaksi', [LaporanController::class, 'riwayatTransaksi'])->name('riwayat-transaksi');
            Route::get('/analisis-penjualan', [LaporanController::class, 'analisisPenjualan'])->name('analisis-penjualan');
            Route::get('/export-riwayat-transaksi/excel', [LaporanController::class, 'exportRiwayatTransaksiExcel'])->name('export.riwayat-transaksi.excel');
            Route::get('/export-riwayat-transaksi/pdf', [LaporanController::class, 'exportRiwayatTransaksiPdf'])->name('export.riwayat-transaksi.pdf');
            Route::get('/export-analisis-penjualan/excel', [LaporanController::class, 'exportAnalisisPenjualanExcel'])->name('export.analisis-penjualan.excel');
            Route::get('/export-analisis-penjualan/pdf', [LaporanController::class, 'exportAnalisisPenjualanPdf'])->name('export.analisis-penjualan.pdf');
            Route::get('/penjualan-produk', [LaporanController::class, 'penjualanProduk'])->name('penjualan-produk');
            Route::get('/produk-favorit', [LaporanController::class, 'produkFavorit'])->name('produk-favorit');
            Route::get('/pendapatan-kategori', [LaporanController::class, 'pendapatanKategori'])->name('pendapatan-kategori');
            Route::get('/stok', [LaporanController::class, 'stok'])->name('stok');
            Route::get('/kinerja-kasir', [LaporanController::class, 'kinerjaKasir'])->name('kinerja-kasir');
            Route::match(['get', 'post'], '/export-pdf', [LaporanController::class, 'exportPdf'])->name('export.pdf');
            Route::match(['get', 'post'], '/export-excel', [LaporanController::class, 'exportExcel'])->name('export.excel');
            Route::get('/laporan/transaksi/export', [LaporanController::class, 'exportTransaksiExcel'])
                ->name('laporan.transaksi.export');
            Route::get('/shift/{shift}', [LaporanController::class, 'detailShift'])->name('shift.detail');
        });

        // ------------------------------------------------------------------------
        // TRANSAKSI ROUTES (supervisor, manager, it_support, kasir)
        // Prefix: /transaksi
        // CRITICAL: Static routes BEFORE dynamic routes
        // ------------------------------------------------------------------------
        Route::middleware('role:supervisor,manager,it_support,kasir')->prefix('transaksi')->name('transaksi.')->group(function () {
            Route::get('/export-pdf', [TransaksiController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export-excel', [TransaksiController::class, 'exportExcel'])->name('export.excel');
            Route::get('/laporan/transaksi/export', [LaporanController::class, 'exportTransaksiExcel'])
                ->name('laporan.transaksi.export');
            Route::get('/', [TransaksiController::class, 'index'])->name('index');
            Route::get('/open-bill', [TransaksiController::class, 'daftarOpenBill'])->name('open-bill.index');
            Route::get('/shift/{shift}', [TransaksiController::class, 'byShift'])->name('by-shift');
            Route::get('/open-bill/{openBill}', [TransaksiController::class, 'tampilkanOpenBill'])->name('open-bill.show');
            Route::get('/{transaksi}/show', [TransaksiController::class, 'show'])->name('show');
            Route::get('/{transaksi}/print', [TransaksiController::class, 'printStruk'])->name('print');
            Route::put('/{transaksi}/batal', [TransaksiController::class, 'void'])->name('void');

            // IT Support only routes for soft delete management
            Route::middleware('role:it_support')->group(function () {
                Route::delete('/{transaksi}/soft-delete', [TransaksiController::class, 'softDeleteTransaksi'])->name('soft-delete');
                Route::post('/{id}/restore', [TransaksiController::class, 'restoreTransaksi'])->name('restore');
                Route::get('/deleted/list', [TransaksiController::class, 'deletedTransaksi'])->name('deleted.list');
                Route::delete('/open-bill/{openBill}/soft-delete', [TransaksiController::class, 'softDeleteOpenBill'])->name('open-bill.soft-delete');
                Route::post('/open-bill/{id}/restore', [TransaksiController::class, 'restoreOpenBill'])->name('open-bill.restore');
                Route::get('/open-bill/deleted/list', [TransaksiController::class, 'deletedOpenBills'])->name('open-bill.deleted.list');
            });
        });

        // ------------------------------------------------------------------------
        // SUPERVISOR ROUTES (supervisor, manager, it_support)
        // Prefix: /supervisor
        // CRITICAL: Static routes BEFORE dynamic routes
        // ------------------------------------------------------------------------
        Route::middleware('role:supervisor,manager,it_support')->prefix('supervisor')->name('supervisor.')->group(function () {
            Route::get('/dashboard', function () {
                return redirect()->route('dashboard.supervisor');
            })->name('dashboard');
            Route::get('/monitoring-shift', [SupervisorController::class, 'monitoringShift'])->name('monitoring.shift');
            Route::get('/laporan-stok', [SupervisorController::class, 'laporanStok'])->name('stok.report');
            Route::get('/shift/{shift}', [SupervisorController::class, 'shiftDetails'])->name('shift.detail');
            Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
            Route::get('/kasir/create', [KasirController::class, 'create'])->name('kasir.create');
            Route::post('/kasir', [KasirController::class, 'store'])->name('kasir.store');
            Route::get('/kasir/{kasir}/edit', [KasirController::class, 'edit'])->name('kasir.edit');
            Route::put('/kasir/{kasir}', [KasirController::class, 'update'])->name('kasir.update');
            Route::delete('/kasir/{kasir}', [KasirController::class, 'destroy'])->name('kasir.destroy');
            Route::get('/kasir/{kasir}', [KasirController::class, 'show'])->name('kasir.show');
        });

        Route::prefix('pos')->middleware('role:kasir,supervisor,manager,it_support')->group(function () {
            Route::post('shift/buka', [ShiftController::class, 'buka']);
            Route::post('shift/{shift}/tutup', [ShiftController::class, 'tutup']);
            Route::post('kalibrasi', [KalibrasiController::class, 'simpan']);
            Route::post('transaksi', [TransaksiController::class, 'buatTransaksi']);
            Route::get('laporan/shift/{shift}/ringkasan', [LaporanController::class, 'ringkasanShift']);
        });

        Route::prefix('viewer')->group(function () {
            Route::get('stok/cabang/{cabang}/mendekati-kadaluarsa', function (Request $request, Cabang $cabang) {
                $hari = (int) $request->get('hari', 30);
                $hari = max(1, min(365, $hari));

                $batches = (new StokService())->dapatkanBarangMendekatiKadaluarsa($cabang, $hari);

                return response()->json(['batches' => $batches]);
            });
        });

        require __DIR__ . '/settings.php';
    });
};

if ($skipDomainConstraints) {
    $backofficeRouteCallback();
} else {
    Route::domain($backofficeDomain ?: '__invalid.local')->group($backofficeRouteCallback);
}
