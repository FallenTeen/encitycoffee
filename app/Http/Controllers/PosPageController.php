<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PosPageController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('pos/Home');
    }


    public function login()
    {
        return Inertia::render('pos/auth/Login');
    }

    public function me(Request $request)
    {
        return Inertia::render('pos/auth/Me', ['user' => $request->user()]);
    }


    public function shiftsList()
    {
        return Inertia::render('pos/shifts/List', ['shifts' => []]);
    }

    public function shiftActive(Request $request)
    {
        return Inertia::render('pos/shifts/Active', ['shift' => null]);
    }

    public function shiftOpen()
    {
        return Inertia::render('pos/shifts/Open');
    }

    public function shiftClose($id)
    {
        return Inertia::render('pos/shifts/Close', ['shift' => ['id' => $id]]);
    }

    public function shiftDetail($id)
    {
        return Inertia::render('pos/shifts/Detail', ['shift' => ['id' => $id, 'transaksi' => []]]);
    }


    public function kalibrasiList()
    {
        return Inertia::render('pos/kalibrasi/List', ['kalibrasi' => []]);
    }

    public function kalibrasiCreate(Request $request)
    {
        return Inertia::render('pos/kalibrasi/Create', ['shift' => null]);
    }

    public function kalibrasiSelect()
    {
        return Inertia::render('pos/kalibrasi/Select', ['kalibrasi' => []]);
    }

    public function kalibrasiByShift($shiftId)
    {
        return Inertia::render('pos/kalibrasi/ByShift', ['kalibrasi' => []]);
    }


    public function produkList()
    {
        return Inertia::render('pos/produk/List', ['produk' => []]);
    }

    public function produkShow($id)
    {
        return Inertia::render('pos/produk/Show', ['produk' => ['id' => $id]]);
    }

    public function produkByType($tipe)
    {
        return Inertia::render('pos/produk/ByType', ['tipe' => $tipe, 'produk' => []]);
    }


    public function stokByCabang()
    {
        return Inertia::render('pos/stok/ByCabang', ['stok' => []]);
    }

    public function stokLow()
    {
        return Inertia::render('pos/stok/LowStock', ['stok' => []]);
    }

    public function stokExpiring()
    {
        return Inertia::render('pos/stok/Expiring', ['batch' => []]);
    }

    public function stokMutasi()
    {
        return Inertia::render('pos/stok/MutasiHistory', ['mutasi' => []]);
    }


    public function transaksiCreate()
    {
        return Inertia::render('pos/transaksi/Create', ['shift' => null]);
    }

    public function transaksiShow($id)
    {
        return Inertia::render('pos/transaksi/Show', ['transaksi' => ['id' => $id]]);
    }

    public function transaksiCancel($id)
    {
        return Inertia::render('pos/transaksi/Cancel', ['transaksi' => ['id' => $id]]);
    }

    public function transaksiPerShift()
    {
        return Inertia::render('pos/transaksi/PerShift', ['transaksi' => []]);
    }


    public function laporanShiftSummary($shiftId)
    {
        return Inertia::render('pos/laporan/ShiftSummary', ['ringkasan' => []]);
    }

    public function laporanDaily()
    {
        return Inertia::render('pos/laporan/Daily');
    }

    public function laporanProductSales()
    {
        return Inertia::render('pos/laporan/ProductSales');
    }

    public function laporanStock()
    {
        return Inertia::render('pos/laporan/StockReport', ['laporan' => []]);
    }

    public function laporanUserPerformance()
    {
        return Inertia::render('pos/laporan/UserPerformance');
    }


    public function sinkEnqueue()
    {
        return Inertia::render('pos/sinkronisasi/Enqueue');
    }

    public function sinkProcess()
    {
        return Inertia::render('pos/sinkronisasi/Process', ['result' => []]);
    }

    public function sinkStatus()
    {
        return Inertia::render('pos/sinkronisasi/Status', ['status' => []]);
    }
}
