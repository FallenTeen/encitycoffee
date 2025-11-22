<?php
namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\SatuanProduk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function daftarProduk(Request $request)
    {
        $query = Produk::with(['kategori','satuan'])->aktif();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($s) use ($q) {
                $s->where('nama', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        $data = $query->orderBy('nama')->get();
        return response()->json(['produk' => $data]);
    }

    public function tampilkanProduk(Produk $produk)
    {
        $produk->load(['kategori','satuan']);
        return response()->json(['produk' => $produk]);
    }

    public function produkBerdasarkanTipe($tipe)
    {
        $data = Produk::with(['kategori','satuan'])
            ->where('tipe', $tipe)
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();

        return response()->json(['produk' => $data]);
    }

    public function satuanProduk(Produk $produk)
    {
        $satuan = SatuanProduk::where('produk_id', $produk->id)->get();
        return response()->json(['satuan' => $satuan]);
    }
}
