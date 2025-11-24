<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\KategoriProdukController;
use App\Http\Controllers\LaporanController;
use Inertia\Inertia;
use App\Http\Controllers\TransaksiController;

// ============================================================================
// GUEST ROUTES (Unauthenticated users only)
// ============================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ------------------------------------------------------------------------
    // IT SUPPORT ROUTES (it_support only)
    // Prefix: /admin
    // ------------------------------------------------------------------------
    Route::middleware('role:it_support')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // System Logs (static route first)
        Route::get('/system-logs', [SystemController::class, 'logs'])->name('system.logs');

        // Users Routes (create before dynamic)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/assign-cabang', [UserController::class, 'assignCabang'])->name('users.assign-cabang');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

        // Cabang Routes (create before dynamic)
        Route::get('/cabang', [CabangController::class, 'index'])->name('cabang.index');
        Route::get('/cabang/create', [CabangController::class, 'create'])->name('cabang.create');
        Route::post('/cabang', [CabangController::class, 'store'])->name('cabang.store');
        Route::get('/cabang/{cabang}/edit', [CabangController::class, 'edit'])->name('cabang.edit');
        Route::put('/cabang/{cabang}', [CabangController::class, 'update'])->name('cabang.update');
        Route::delete('/cabang/{cabang}', [CabangController::class, 'destroy'])->name('cabang.destroy');
        Route::get('/cabang/{cabang}', [CabangController::class, 'show'])->name('cabang.show');
    });

    // ------------------------------------------------------------------------
    // MANAGER ROUTES (manager, it_support)
    // Prefix: /manager
    // ------------------------------------------------------------------------
    Route::middleware('role:manager,it_support')->prefix('manager')->name('manager.')->group(function () {
        Route::get('/dashboard', [ManagerController::class, 'dashboard'])->name('dashboard');
        Route::get('/laporan-cabang', [ManagerController::class, 'laporanCabang'])->name('laporan.cabang');
        Route::get('/performa-shift', [ManagerController::class, 'perfomaShift'])->name('performa.shift');

        // Supervisor Routes (create before dynamic)
        Route::get('/supervisor', [SupervisorController::class, 'index'])->name('supervisor.index');
        Route::get('/supervisor/create', [SupervisorController::class, 'create'])->name('supervisor.create');
        Route::post('/supervisor', [SupervisorController::class, 'store'])->name('supervisor.store');
        Route::get('/supervisor/{supervisor}/edit', [SupervisorController::class, 'edit'])->name('supervisor.edit');
        Route::put('/supervisor/{supervisor}', [SupervisorController::class, 'update'])->name('supervisor.update');
        Route::delete('/supervisor/{supervisor}', [SupervisorController::class, 'destroy'])->name('supervisor.destroy');
        Route::get('/supervisor/{supervisor}', [SupervisorController::class, 'show'])->name('supervisor.show');
    });

    // ------------------------------------------------------------------------
    // PRODUK ROUTES (manager, it_support)
    // Prefix: /produk
    // CRITICAL: ALL static routes MUST come BEFORE dynamic {produk} routes
    // ------------------------------------------------------------------------
    Route::middleware('role:manager,it_support')->prefix('produk')->name('produk.')->group(function () {
        // Main produk static routes
        Route::get('/', [ProdukController::class, 'index'])->name('index');
        Route::get('/create', [ProdukController::class, 'create'])->name('create');
        Route::post('/', [ProdukController::class, 'store'])->name('store');

        // KATEGORI routes (MUST be before {produk} to avoid clash)
        Route::get('/kategori', [KategoriProdukController::class, 'index'])->name('kategori.index');
        Route::get('/kategori/create', [KategoriProdukController::class, 'create'])->name('kategori.create');
        Route::post('/kategori', [KategoriProdukController::class, 'store'])->name('kategori.store');
        Route::get('/kategori/{kategori}/edit', [KategoriProdukController::class, 'edit'])->name('kategori.edit');
        Route::put('/kategori/{kategori}', [KategoriProdukController::class, 'update'])->name('kategori.update');
        Route::delete('/kategori/{kategori}', [KategoriProdukController::class, 'destroy'])->name('kategori.destroy');

        // SATUAN delete route (static segment)
        Route::delete('/satuan/{satuan}', [ProdukController::class, 'deleteSatuan'])->name('satuan.delete');

        // PRODUK dynamic routes (MUST be LAST to avoid catching 'kategori' or 'satuan')
        Route::post('/{produk}/satuan', [ProdukController::class, 'addSatuan'])->name('satuan.store');
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

        // Static action routes
        Route::post('/tambah', [StokController::class, 'store'])->name('store');
        Route::post('/sesuaikan', [StokController::class, 'adjust'])->name('adjust');
        Route::get('/rendah', [StokController::class, 'rendah'])->name('rendah');
        Route::get('/kadaluarsa', [StokController::class, 'kadaluarsa'])->name('kadaluarsa');

        // Dynamic routes with specific prefixes
        Route::get('/cabang/{cabang}', [StokController::class, 'byCabang'])->name('by-cabang');
        Route::get('/mutasi/{stokEtalase}', [StokController::class, 'mutasi'])->name('mutasi');
    });

    // ------------------------------------------------------------------------
    // LAPORAN ROUTES (supervisor, manager, it_support)
    // Prefix: /laporan
    // CRITICAL: Static routes BEFORE dynamic routes
    // ------------------------------------------------------------------------
    Route::middleware('role:supervisor,manager,it_support')->prefix('laporan')->name('laporan.')->group(function () {
        // Static laporan routes
        Route::get('/shift', [LaporanController::class, 'shift'])->name('shift');
        Route::get('/harian', [LaporanController::class, 'harian'])->name('harian');
        Route::get('/penjualan-produk', [LaporanController::class, 'penjualanProduk'])->name('penjualan-produk');
        Route::get('/stok', [LaporanController::class, 'stok'])->name('stok');
        Route::get('/kinerja-kasir', [LaporanController::class, 'kinerjaKasir'])->name('kinerja-kasir');

        // Export routes
        Route::post('/export-pdf', [LaporanController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export-excel', [LaporanController::class, 'exportExcel'])->name('export.excel');

        // Dynamic route (last)
        Route::get('/shift/{shift}', [LaporanController::class, 'detailShift'])->name('shift.detail');
    });

    // ------------------------------------------------------------------------
    // TRANSAKSI ROUTES (supervisor, manager, it_support)
    // Prefix: /transaksi
    // CRITICAL: Static routes BEFORE dynamic routes
    // ------------------------------------------------------------------------
    Route::middleware('role:supervisor,manager,it_support')->prefix('transaksi')->name('transaksi.')->group(function () {
        Route::get('/', [TransaksiController::class, 'index'])->name('index');

        // Routes with specific prefixes (before general dynamic)
        Route::get('/shift/{shift}', [TransaksiController::class, 'byShift'])->name('by-shift');

        // Dynamic routes with specific actions
        Route::get('/{transaksi}/show', [TransaksiController::class, 'show'])->name('show');
        Route::get('/{transaksi}/print', [TransaksiController::class, 'printStruk'])->name('print');
        Route::put('/{transaksi}/batal', [TransaksiController::class, 'void'])->name('void');
    });

    // ------------------------------------------------------------------------
    // SUPERVISOR ROUTES (supervisor, manager, it_support)
    // Prefix: /supervisor
    // CRITICAL: Static routes BEFORE dynamic routes
    // ------------------------------------------------------------------------
    Route::middleware('role:supervisor,manager,it_support')->prefix('supervisor')->name('supervisor.')->group(function () {
        // Static supervisor routes
        Route::get('/dashboard', [SupervisorController::class, 'dashboard'])->name('dashboard');
        Route::get('/monitoring-shift', [SupervisorController::class, 'monitoringShift'])->name('monitoring.shift');
        Route::get('/laporan-stok', [SupervisorController::class, 'laporanStok'])->name('stok.report');

        // Dynamic shift route
        Route::get('/shift/{shift}', [SupervisorController::class, 'shiftDetails'])->name('shift.detail');

        // KASIR routes (create before dynamic)
        Route::get('/kasir', [KasirController::class, 'index'])->name('kasir.index');
        Route::get('/kasir/create', [KasirController::class, 'create'])->name('kasir.create');
        Route::post('/kasir', [KasirController::class, 'store'])->name('kasir.store');
        Route::get('/kasir/{kasir}/edit', [KasirController::class, 'edit'])->name('kasir.edit');
        Route::put('/kasir/{kasir}', [KasirController::class, 'update'])->name('kasir.update');
        Route::delete('/kasir/{kasir}', [KasirController::class, 'destroy'])->name('kasir.destroy');
        Route::get('/kasir/{kasir}', [KasirController::class, 'show'])->name('kasir.show');
    });
});
