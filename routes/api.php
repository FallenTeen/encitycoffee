<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\KalibrasiController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\SinkronisasiController;

// -----------------------------
// POS API (real routes, requires auth)
// prefix: /api/pos/...
// -----------------------------
Route::prefix('pos')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::middleware('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth')->group(function () {
        Route::prefix('shift')->group(function () {
            Route::get('aktif', [ShiftController::class, 'ambilShiftAktif']);
            Route::post('buka', [ShiftController::class, 'bukaShift']);
            Route::post('{shift}/tutup', [ShiftController::class, 'tutupShift']);
            Route::get('{shift}', [ShiftController::class, 'tampilkanShift']);
            Route::get('/', [ShiftController::class, 'daftarShift']);
        });

        Route::prefix('kalibrasi')->group(function () {
            Route::post('/', [KalibrasiController::class, 'simpanKalibrasi']);
            Route::put('{kalibrasi}/pilih', [KalibrasiController::class, 'pilihKalibrasi']);
            Route::get('shift/{shift}', [KalibrasiController::class, 'ambilKalibrasiPerShift']);
        });

        Route::prefix('produk')->group(function () {
            Route::get('/', [ProdukController::class, 'daftarProduk']);
            Route::get('{produk}', [ProdukController::class, 'tampilkanProduk']);
            Route::get('tipe/{tipe}', [ProdukController::class, 'produkBerdasarkanTipe']);
            Route::get('{produk}/satuan', [ProdukController::class, 'satuanProduk']);
        });

        Route::prefix('stok')->group(function () {
            Route::get('cabang/{cabang}', [StokController::class, 'stokBerdasarkanCabang']);
            Route::get('cabang/{cabang}/rendah', [StokController::class, 'stokRendah']);
            Route::get('cabang/{cabang}/mendekati-kadaluarsa', [StokController::class, 'barangMendekatiKadaluarsa']);
            Route::get('mutasi/{stokEtalase}', [StokController::class, 'riwayatMutasi']);
        });

        Route::prefix('transaksi')->group(function () {
            Route::post('/', [TransaksiController::class, 'buatTransaksi']);
            Route::get('{transaksi}', [TransaksiController::class, 'tampilkanTransaksi']);
            Route::put('{transaksi}/batal', [TransaksiController::class, 'batalkanTransaksi']);
            Route::get('shift/{shift}', [TransaksiController::class, 'transaksiPerShift']);
        });

        Route::prefix('laporan')->group(function () {
            Route::get('shift/{shift}/ringkasan', [LaporanController::class, 'ringkasanShift']);
            Route::get('cabang/{cabang}/harian', [LaporanController::class, 'laporanHarian']);
            Route::get('cabang/{cabang}/penjualan-produk', [LaporanController::class, 'penjualanProduk']);
            Route::get('cabang/{cabang}/stok', [LaporanController::class, 'laporanStok']);
            Route::get('user/{user}/kinerja', [LaporanController::class, 'kinerjaUser']);
        });

        Route::prefix('sinkronisasi')->group(function () {
            Route::post('antrian', [SinkronisasiController::class, 'tambahKeAntrian']);
            Route::post('proses', [SinkronisasiController::class, 'prosesAntrian']);
            Route::get('status', [SinkronisasiController::class, 'statusSinkronisasi']);
        });
    });
});

// -----------------------------
// Viewer API (buat read-only JSON endpoints)
// prefix: /api/viewer/...
// -----------------------------
Route::prefix('viewer')->group(function () {
    Route::prefix('shift')->group(function () {
        Route::get('aktif', [ShiftController::class, 'ambilShiftAktif']);
        Route::get('{shift}', [ShiftController::class, 'tampilkanShift']);
        Route::get('/', [ShiftController::class, 'daftarShift']);
    });

    Route::prefix('kalibrasi')->group(function () {
        Route::get('shift/{shift}', [KalibrasiController::class, 'ambilKalibrasiPerShift']);
    });

    Route::prefix('produk')->group(function () {
        Route::get('/', [ProdukController::class, 'daftarProduk']);
        Route::get('{produk}', [ProdukController::class, 'tampilkanProduk']);
        Route::get('tipe/{tipe}', [ProdukController::class, 'produkBerdasarkanTipe']);
        Route::get('{produk}/satuan', [ProdukController::class, 'satuanProduk']);
    });

    Route::prefix('stok')->group(function () {
        Route::get('cabang/{cabang}', [StokController::class, 'stokBerdasarkanCabang']);
        Route::get('cabang/{cabang}/rendah', [StokController::class, 'stokRendah']);
        Route::get('cabang/{cabang}/mendekati-kadaluarsa', [StokController::class, 'barangMendekatiKadaluarsa']);
        Route::get('mutasi/{stokEtalase}', [StokController::class, 'riwayatMutasi']);
    });

    Route::prefix('transaksi')->group(function () {
        Route::get('{transaksi}', [TransaksiController::class, 'tampilkanTransaksi']);
        Route::get('shift/{shift}', [TransaksiController::class, 'transaksiPerShift']);
    });

    Route::prefix('laporan')->group(function () {
        Route::get('shift/{shift}/ringkasan', [LaporanController::class, 'ringkasanShift']);
        Route::get('cabang/{cabang}/harian', [LaporanController::class, 'laporanHarian']);
        Route::get('cabang/{cabang}/penjualan-produk', [LaporanController::class, 'penjualanProduk']);
        Route::get('cabang/{cabang}/stok', [LaporanController::class, 'laporanStok']);
        Route::get('user/{user}/kinerja', [LaporanController::class, 'kinerjaUser']);
    });

    Route::prefix('sinkronisasi')->group(function () {
        Route::get('status', [SinkronisasiController::class, 'statusSinkronisasi']);
    });
});

