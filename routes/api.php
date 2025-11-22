<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\KalibrasiController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\StokController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\SinkronisasiController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('shift')->group(function () {
        Route::get('/aktif', [ShiftController::class, 'dapatkanAktif']);
        Route::post('/buka', [ShiftController::class, 'buka']);
        Route::post('/{shift}/tutup', [ShiftController::class, 'tutup']);
        Route::get('/{shift}', [ShiftController::class, 'tampilkan']);
        Route::get('/', [ShiftController::class, 'daftar']);
    });

    Route::prefix('kalibrasi')->group(function () {
        Route::post('/', [KalibrasiController::class, 'simpan']);
        Route::put('/{kalibrasi}/pilih', [KalibrasiController::class, 'pilih']);
        Route::get('/shift/{shift}', [KalibrasiController::class, 'dapatkanBerdasarkanShift']);
    });

    Route::prefix('produk')->group(function () {
        Route::get('/', [ProdukController::class, 'daftar']);
        Route::get('/{produk}', [ProdukController::class, 'tampilkan']);
        Route::get('/tipe/{tipe}', [ProdukController::class, 'berdasarkanTipe']);
        Route::get('/{produk}/satuan', [ProdukController::class, 'satuan']);
    });

    Route::prefix('stok')->group(function () {
        Route::get('/cabang/{cabang}', [StokController::class, 'dapatkanBerdasarkanCabang']);
        Route::get('/cabang/{cabang}/rendah', [StokController::class, 'dapatkanStokRendah']);
        Route::get('/cabang/{cabang}/mendekati-kadaluarsa', [StokController::class, 'dapatkanMendekatiKadaluarsa']);
        Route::post('/tambah', [StokController::class, 'tambah']);
        Route::post('/sesuaikan', [StokController::class, 'sesuaikan']);
        Route::get('/mutasi/{stokEtalase}', [StokController::class, 'dapatkanMutasi']);
    });

    Route::prefix('transaksi')->group(function () {
        Route::post('/', [TransaksiController::class, 'simpan']);
        Route::get('/{transaksi}', [TransaksiController::class, 'tampilkan']);
        Route::put('/{transaksi}/batal', [TransaksiController::class, 'batalkan']);
        Route::get('/shift/{shift}', [TransaksiController::class, 'dapatkanBerdasarkanShift']);
        Route::get('/cabang/{cabang}', [TransaksiController::class, 'dapatkanBerdasarkanCabang']);
    });

    Route::prefix('laporan')->group(function () {
        Route::get('/shift/{shift}/ringkasan', [LaporanController::class, 'ringkasanShift']);
        Route::get('/cabang/{cabang}/harian', [LaporanController::class, 'laporanHarian']);
        Route::get('/cabang/{cabang}/penjualan-produk', [LaporanController::class, 'penjualanProduk']);
        Route::get('/cabang/{cabang}/stok', [LaporanController::class, 'laporanStok']);
        Route::get('/user/{user}/kinerja', [LaporanController::class, 'kinerjaUser']);
    });

    Route::prefix('sinkronisasi')->group(function () {
        Route::post('/antrian', [SinkronisasiController::class, 'tambahKeAntrian']);
        Route::post('/proses', [SinkronisasiController::class, 'prosesAntrian']);
        Route::get('/status', [SinkronisasiController::class, 'dapatkanStatus']);
    });
});
