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
        Gate::authorize('view-produk');
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:nama,produk_count',
            'sort_dir' => 'nullable|in:asc,desc',
        ]);

        $query = KategoriProduk::withCount('produk');

        if (!empty($validated['search'])) {
            $q = $validated['search'];
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('deskripsi', 'like', "%{$q}%");
            });
        }

        $sortBy = $validated['sort_by'] ?? 'nama';
        $sortDir = $validated['sort_dir'] ?? 'asc';

        if ($sortBy === 'produk_count') {
            $query->orderBy('produk_count', $sortDir);
        } else {
            $query->orderBy('nama', $sortDir);
        }

        $kategori = $query->paginate(20)->appends($validated);

        return Inertia::render('produk/kategori/Index', [
            'kategori' => $kategori,
            'filter_aktif' => [
                'search' => $validated['search'] ?? '',
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function create()
    {
        Gate::authorize('view-produk');
        return Inertia::render('produk/kategori/Create');
    }

    public function store(Request $request)
    {
        Gate::authorize('view-produk');
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
        Gate::authorize('view-produk');
        $produk_list = $kategori->produk()->with('satuan')->paginate(20);
        return Inertia::render('produk/kategori/Show', [
            'kategori' => $kategori,
            'produk_list' => $produk_list,
        ]);
    }

    public function edit(KategoriProduk $kategori)
    {
        Gate::authorize('view-produk');
        return Inertia::render('produk/kategori/Edit', ['kategori' => $kategori]);
    }

    public function update(Request $request, KategoriProduk $kategori)
    {
        Gate::authorize('view-produk');
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
        Gate::authorize('view-produk');
        if ($kategori->produk()->count() > 0) {
            return back()->withErrors(['kategori' => 'Kategori masih memiliki produk']);
        }

        $kategori->delete();
        return redirect()->route('produk.kategori.index')->with('success', 'Kategori produk berhasil dihapus.');
    }
}
