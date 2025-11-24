<?php
namespace App\Http\Controllers;

use App\Models\KategoriProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class KategoriProdukController extends Controller
{
    public function __construct()
    {

    }

    public function index(Request $request)
    {
        Gate::authorize('view-manager-dashboard');
        $kategori = KategoriProduk::withCount('produk')->orderBy('nama')->paginate(20);
        return Inertia::render('produk/kategori/Index', compact('kategori'));
    }

    public function create()
    {
        Gate::authorize('view-manager-dashboard');
        return Inertia::render('produk/kategori/Create');
    }

    public function store(Request $request)
    {
        Gate::authorize('view-manager-dashboard');
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:kategori_produk,nama',
            'slug' => 'required|string|max:255|unique:kategori_produk,slug',
            'deskripsi' => 'nullable|string',
        ]);

        KategoriProduk::create($validated);

        return redirect()->route('produk.kategori.index')->with('success', 'Kategori produk berhasil dibuat.');
    }

    public function show(KategoriProduk $kategori)
    {
        Gate::authorize('view-manager-dashboard');
        $produk_list = $kategori->produk()->with('satuan')->paginate(20);
        return Inertia::render('produk/kategori/Show', [
            'kategori' => $kategori,
            'produk_list' => $produk_list,
        ]);
    }

    public function edit(KategoriProduk $kategori)
    {
        Gate::authorize('view-manager-dashboard');
        return Inertia::render('produk/kategori/Edit', ['kategori' => $kategori]);
    }

    public function update(Request $request, KategoriProduk $kategori)
    {
        Gate::authorize('view-manager-dashboard');
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:kategori_produk,nama,' . $kategori->id,
            'slug' => 'required|string|max:255|unique:kategori_produk,slug,' . $kategori->id,
            'deskripsi' => 'nullable|string',
        ]);

        $kategori->update($validated);

        return redirect()->route('produk.kategori.index')->with('success', 'Kategori produk berhasil diperbarui.');
    }

    public function destroy(KategoriProduk $kategori)
    {
        Gate::authorize('view-manager-dashboard');
        if ($kategori->produk()->count() > 0) {
            return back()->withErrors(['kategori' => 'Kategori masih memiliki produk']);
        }

        $kategori->delete();
        return redirect()->route('produk.kategori.index')->with('success', 'Kategori produk berhasil dihapus.');
    }
}
