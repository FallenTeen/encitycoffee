<?php
namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\SatuanProduk;
use App\Models\StokEtalase;
use App\Models\ItemTransaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProdukController extends Controller
{
    public function __construct()
    {

    }

    public function index(Request $request)
    {
        Gate::authorize('view-produk');
        $validated = $request->validate([
            'kategori_id' => 'nullable|integer|exists:kategori_produk,id',
            'tipe' => 'nullable|in:beans,minuman,snack',
            'aktif' => 'nullable|boolean',
            'pencarian' => 'nullable|string|max:255',
        ]);

        $query = Produk::with(['kategori', 'satuan']);

        if (!empty($validated['kategori_id'])) {
            $query->where('kategori_id', (int) $validated['kategori_id']);
        }
        if (!empty($validated['tipe'])) {
            $query->where('tipe', $validated['tipe']);
        }
        if (array_key_exists('aktif', $validated) && $validated['aktif'] !== null) {
            $query->where('aktif', (bool) $validated['aktif']);
        }
        if (!empty($validated['pencarian'])) {
            $q = $validated['pencarian'];
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $produks = $query->orderBy('nama')->paginate(20)->appends($validated);
        $kategori_list = KategoriProduk::orderBy('nama')->get(['id', 'nama']);

        return Inertia::render('produk/Index', [
            'produks' => $produks,
            'kategori_list' => $kategori_list,
            'filter_aktif' => $validated,
        ]);
    }


    public function daftarProduk(Request $request)
    {
        Gate::authorize('view-produk');

        $validated = $request->validate([
            'kategori_id' => 'nullable|integer|exists:kategori_produk,id',
            'tipe' => 'nullable|in:beans,minuman,snack',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Produk::with(['kategori', 'satuan'])->where('aktif', true);

        if (!empty($validated['kategori_id'])) {
            $query->where('kategori_id', (int) $validated['kategori_id']);
        }
        if (!empty($validated['tipe'])) {
            $query->where('tipe', $validated['tipe']);
        }
        if (!empty($validated['search'])) {
            $q = $validated['search'];
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        $produks = $query->orderBy('nama')->paginate(50);

        return response()->json($produks);
    }


    public function tampilkanProduk(Produk $produk)
    {
        Gate::authorize('view-produk');
        $produk->load(['kategori', 'satuan']);
        return response()->json($produk);
    }


    public function produkBerdasarkanTipe($tipe)
    {
        Gate::authorize('view-produk');
        if (!in_array($tipe, ['beans', 'minuman', 'snack'], true)) {
            return response()->json(['error' => 'Tipe produk tidak valid'], 422);
        }
        $produks = Produk::where('tipe', $tipe)
            ->where('aktif', true)
            ->with(['kategori', 'satuan'])
            ->orderBy('nama')
            ->paginate(50);
        return response()->json($produks);
    }


    public function satuanProduk(Produk $produk)
    {
        Gate::authorize('view-produk');
        $satuan = $produk->satuan()->orderBy('nilai_konversi')->get();
        return response()->json($satuan);
    }

    public function addSatuan(Request $request, Produk $produk)
    {
        Gate::authorize('view-produk');
        $validated = $request->validate([
            'nama_satuan' => 'required|string|max:50',
            'nilai_konversi' => 'required|numeric|min:0.0001',
        ]);

        $exists = SatuanProduk::where('produk_id', $produk->id)
            ->whereRaw('LOWER(nama_satuan) = ?', [strtolower($validated['nama_satuan'])])
            ->exists();
        if ($exists) {
            return back()->withErrors(['nama_satuan' => 'Satuan sudah ada untuk produk ini'])->withInput();
        }

        SatuanProduk::create([
            'produk_id' => $produk->id,
            'nama_satuan' => $validated['nama_satuan'],
            'nilai_konversi' => $validated['nilai_konversi'],
        ]);

        return back()->with('success', 'Satuan produk berhasil ditambahkan');
    }

    public function deleteSatuan(SatuanProduk $satuan)
    {
        Gate::authorize('view-produk');
        $satuan->delete();
        return back()->with('success', 'Satuan produk berhasil dihapus');
    }

    public function create()
    {
        Gate::authorize('view-produk');
        $kategori = KategoriProduk::orderBy('nama')->get(['id', 'nama']);
        $tipe_options = ['beans', 'minuman', 'snack'];
        $satuan_options = ['kg', 'gram', 'pcs', 'liter', 'ml'];
        return Inertia::render('produk/Create', [
            'kategori' => $kategori,
            'tipe_options' => $tipe_options,
            'satuan_options' => $satuan_options,
            'satuans' => $satuan_options,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('view-produk');
        $validated = $request->validate([
            'kategori_id' => 'required|integer|exists:kategori_produk,id',
            'sku' => 'required|string|max:50|unique:produk,sku',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tipe' => 'required|in:beans,minuman,snack',
            'satuan_dasar' => 'required|string|max:50',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|gt:harga_modal',
            'perlu_kalibrasi' => 'nullable|boolean',
            'aktif' => 'nullable|boolean',
        ]);

        if (($validated['tipe'] ?? null) !== 'beans' && ($validated['perlu_kalibrasi'] ?? false)) {
            return back()->withErrors(['perlu_kalibrasi' => 'Hanya produk beans yang bisa dikalibrasi'])->withInput();
        }

        $payload = array_merge($validated, [
            'aktif' => $validated['aktif'] ?? true,
        ]);

        Produk::create($payload);

        return redirect()->route('produk.index')->with('success', 'Produk berhasil dibuat');
    }

    public function edit(Produk $produk)
    {
        Gate::authorize('view-produk');
        $kategori = KategoriProduk::orderBy('nama')->get(['id', 'nama']);
        $satuan_list = $produk->satuan()->orderBy('nilai_konversi')->get();
        $tipe_options = ['beans', 'minuman', 'snack'];
        $satuan_options = ['kg', 'gram', 'pcs', 'liter', 'ml'];
        $stok_tersedia = StokEtalase::where('produk_id', $produk->id)->with('cabang')->get();
        return Inertia::render('produk/Edit', [
            'produk' => $produk->load('kategori'),
            'kategori' => $kategori,
            'satuan_list' => $satuan_list,
            'stok_tersedia' => $stok_tersedia,
            'tipe_options' => $tipe_options,
            'satuan_options' => $satuan_options,
        ]);
    }

    public function update(Request $request, Produk $produk)
    {
        Gate::authorize('view-produk');
        $validated = $request->validate([
            'kategori_id' => 'required|integer|exists:kategori_produk,id',
            'sku' => 'required|string|max:50|unique:produk,sku,' . $produk->id,
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tipe' => 'required|in:beans,minuman,snack',
            'satuan_dasar' => 'required|string|max:50',
            'harga_modal' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|gt:harga_modal',
            'perlu_kalibrasi' => 'nullable|boolean',
            'aktif' => 'nullable|boolean',
        ]);

        if (($validated['tipe'] ?? null) !== 'beans' && ($validated['perlu_kalibrasi'] ?? false)) {
            return back()->withErrors(['perlu_kalibrasi' => 'Hanya produk beans yang bisa dikalibrasi'])->withInput();
        }

        if (array_key_exists('aktif', $validated) && $validated['aktif'] === false && $produk->aktif === true) {
            $hasActiveStock = StokEtalase::where('produk_id', $produk->id)->where('jumlah', '>', 0)->exists();
            if ($hasActiveStock) {
                return back()->with('warning', 'Produk masih memiliki stok aktif')->withInput();
            }
        }

        $payload = array_merge($validated, [
            'aktif' => $validated['aktif'] ?? $produk->aktif,
        ]);

        $produk->update($payload);

        return redirect()->route('produk.index')->with('success', 'Produk berhasil diupdate');
    }

    public function destroy(Produk $produk)
    {
        Gate::authorize('view-produk');

        $hasStok = StokEtalase::where('produk_id', $produk->id)->exists();
        if ($hasStok) {
            return back()->withErrors(['produk' => 'Tidak bisa hapus produk dengan data stok']);
        }

        $hasTransaksi = ItemTransaksi::where('produk_id', $produk->id)->exists();
        if ($hasTransaksi) {
            return back()->withErrors(['produk' => 'Tidak bisa hapus produk dengan riwayat transaksi']);
        }

        $produk->delete();

        return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus');
    }

    public function show(Produk $produk)
    {
        Gate::authorize('view-produk');
        return Inertia::render('produk/Show', compact('produk'));
    }
}
