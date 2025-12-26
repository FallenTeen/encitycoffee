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
use App\Http\Controllers\StokController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\KategoriProdukController;
use App\Http\Controllers\LaporanController;
use Inertia\Inertia;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Auth;
use App\Models\Cabang;
use App\Services\StokService;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('welcome', [
        'canRegister' => true,
    ]);
})->name('home');

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ------------------------------------------------------------------------
    // IT SUPPORT ROUTES (it_support only)
    // Prefix: /admin
    // ------------------------------------------------------------------------
    Route::middleware('role:it_support')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
    });

    // ------------------------------------------------------------------------
    // MANAGER ROUTES (manager, it_support)
    // Prefix: /manager
    // ------------------------------------------------------------------------
    Route::middleware('role:manager,it_support')->prefix('manager')->name('manager.')->group(function () {
        Route::get('/dashboard', [ManagerController::class, 'dashboard'])->name('dashboard');
        Route::get('/laporan-cabang', [ManagerController::class, 'laporanCabang'])->name('laporan.cabang');
        Route::get('/performa-shift', [ManagerController::class, 'perfomaShift'])->name('performa.shift');
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
    // PRODUK ROUTES (manager, it_support)
    // Prefix: /produk
    // CRITICAL: ALL static routes MUST come BEFORE dynamic {produk} routes
    // ------------------------------------------------------------------------
    Route::middleware('role:manager,it_support')->prefix('produk')->name('produk.')->group(function () {
        // STATIC ROUTES (harus di atas)
        Route::get('/create', [ProdukController::class, 'create'])->name('create');
        Route::post('/', [ProdukController::class, 'store'])->name('store');
        Route::get('/sku-suggest', [ProdukController::class, 'suggestSku'])->name('sku.suggest');
        Route::get('/check-sku', [ProdukController::class, 'checkSku'])->name('sku.check');
        
        // Cache management route
        Route::get('/cache/clear', [ProdukController::class, 'clearCache'])->name('cache.clear');
        
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
        
        // DYNAMIC ROUTES (harus di bawah semua static routes)
        Route::get('/{produk}/edit', [ProdukController::class, 'edit'])->name('edit');
        Route::put('/{produk}', [ProdukController::class, 'update'])->name('update');
        Route::delete('/{produk}', [ProdukController::class, 'destroy'])->name('destroy');
        Route::get('/{produk}', [ProdukController::class, 'show'])->name('show');
        
        // INDEX ROUTE - Menggunakan query parameter ?cabang_id=1
        Route::get('/', [ProdukController::class, 'index'])->name('index');
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
        Route::get('/harian', [LaporanController::class, 'harian'])->name('harian');
        Route::get('/penjualan-produk', [LaporanController::class, 'penjualanProduk'])->name('penjualan-produk');
        Route::get('/pendapatan-kategori', [LaporanController::class, 'pendapatanKategori'])->name('pendapatan-kategori');
        Route::get('/stok', [LaporanController::class, 'stok'])->name('stok');
        Route::get('/kinerja-kasir', [LaporanController::class, 'kinerjaKasir'])->name('kinerja-kasir');
        Route::post('/export-pdf', [LaporanController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export-excel', [LaporanController::class, 'exportExcel'])->name('export.excel');
        Route::get('/shift/{shift}', [LaporanController::class, 'detailShift'])->name('shift.detail');
    });

    // ------------------------------------------------------------------------
    // TRANSAKSI ROUTES (supervisor, manager, it_support)
    // Prefix: /transaksi
    // CRITICAL: Static routes BEFORE dynamic routes
    // ------------------------------------------------------------------------
    Route::middleware('role:supervisor,manager,it_support')->prefix('transaksi')->name('transaksi.')->group(function () {
        Route::get('/', [TransaksiController::class, 'index'])->name('index');
        Route::get('/open-bill', [TransaksiController::class, 'daftarOpenBill'])->name('open-bill.index');
        Route::get('/shift/{shift}', [TransaksiController::class, 'byShift'])->name('by-shift');
        Route::get('/open-bill/{openBill}', [TransaksiController::class, 'tampilkanOpenBill'])->name('open-bill.show');
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
        Route::get('/dashboard', [SupervisorController::class, 'dashboard'])->name('dashboard');
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
});

require __DIR__.'/settings.php';