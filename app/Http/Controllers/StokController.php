<?php
namespace App\Http\Controllers;

use App\Models\StokEtalase;
use App\Models\BatchStok;
use App\Models\MutasiStok;
use Illuminate\Http\Request;

class StokController extends Controller
{
    public function stokBerdasarkanCabang($cabangId)
    {
        $stok = StokEtalase::with(['produk.kategori','batch'])
            ->where('cabang_id', $cabangId)
            ->get();

        return response()->json(['stok' => $stok]);
    }

    public function stokRendah($cabangId)
    {
        $stok = StokEtalase::with('produk')
            ->where('cabang_id', $cabangId)
            ->whereColumn('jumlah', '<=', 'stok_minimum')
            ->get();

        return response()->json(['stok_rendah' => $stok]);
    }

    public function barangMendekatiKadaluarsa(Request $request, $cabangId)
    {
        $hari = (int) $request->get('hari', 30);

        $batches = BatchStok::with(['stokEtalase.produk'])
            ->whereHas('stokEtalase', function($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            })
            ->where('tanggal_kadaluarsa', '<=', now()->addDays($hari))
            ->get();

        return response()->json(['batches' => $batches]);
    }

    public function riwayatMutasi(StokEtalase $stokEtalase)
    {
        $data = MutasiStok::with(['user','shift'])
            ->where('stok_etalase_id', $stokEtalase->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($data);
    }
}
