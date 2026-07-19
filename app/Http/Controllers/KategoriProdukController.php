<?php

namespace App\Http\Controllers;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KategoriProdukController extends Controller
{
    /**
     * Get authorized branch IDs for the current user
     */
    private function getAuthorizedCabangIds(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $role = strtolower((string) $user->role);

        // it_support can see all branches
        if ($role === 'it_support') {
            return [];
        }

        // Get user's assigned branches
        if (method_exists($user, 'cabang') && $user->cabang) {
            return $user->cabang->pluck('id')->all();
        }

        return [];
    }

    public function index(Request $request)
    {
        Gate::authorize('manage-kategori');
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:nama,produk_count',
            'sort_dir' => 'nullable|in:asc,desc',
            'cabang_id' => 'nullable|integer|exists:cabang,id',
        ]);

        $authorizedCabangIds = $this->getAuthorizedCabangIds();
        $selectedCabangId = $validated['cabang_id'] ?? null;

        // Validate selected branch is in authorized list
        if ($selectedCabangId && !empty($authorizedCabangIds)) {
            if (!in_array($selectedCabangId, $authorizedCabangIds)) {
                abort(403, 'Tidak memiliki akses ke cabang ini');
            }
        }

        $query = KategoriProduk::withCount([
            'produk' => function ($subQuery) use ($authorizedCabangIds, $selectedCabangId) {
                // Only count products from authorized branches
                if (!empty($authorizedCabangIds)) {
                    $subQuery->whereIn('cabang_id', $authorizedCabangIds);
                }
                // Filter by selected branch if specified
                if ($selectedCabangId) {
                    $subQuery->where('cabang_id', $selectedCabangId);
                }
            }
        ]);

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

        // Get branch list for filter dropdown
        $cabangList = [];
        if (!empty($authorizedCabangIds)) {
            $cabangList = \App\Models\Cabang::whereIn('id', $authorizedCabangIds)
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama])
                ->all();
        }

        return Inertia::render('produk/kategori/Index', [
            'kategori' => $kategori,
            'filter_aktif' => [
                'search' => $validated['search'] ?? '',
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'cabang_id' => $selectedCabangId,
            ],
            'cabang_list' => $cabangList,
        ]);
    }

    public function create()
    {
        Gate::authorize('manage-kategori');
        return Inertia::render('produk/kategori/Create');
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-kategori');
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:kategori_produk,nama',
            'slug' => 'required|string|max:255|unique:kategori_produk,slug',
            'deskripsi' => 'nullable|string',
        ]);

        KategoriProduk::create($validated);

        return redirect()->route('produk.kategori.index')->with('success', 'Kategori produk berhasil dibuat.');
    }

    public function show(KategoriProduk $kategori, Request $request)
    {
        Gate::authorize('manage-kategori');
        
        $validated = $request->validate([
            'cabang_id' => 'nullable|integer|exists:cabang,id',
        ]);
        
        $authorizedCabangIds = $this->getAuthorizedCabangIds();
        $selectedCabangId = $validated['cabang_id'] ?? null;
        
        // Validate selected branch
        if ($selectedCabangId && !empty($authorizedCabangIds)) {
            if (!in_array($selectedCabangId, $authorizedCabangIds)) {
                abort(403, 'Tidak memiliki akses ke cabang ini');
            }
        }
        
        $produkQuery = $kategori->produk()->with('satuan');
        
        // Filter by authorized branches
        if (!empty($authorizedCabangIds)) {
            $produkQuery->whereIn('cabang_id', $authorizedCabangIds);
        }
        
        // Filter by selected branch
        if ($selectedCabangId) {
            $produkQuery->where('cabang_id', $selectedCabangId);
        }
        
        $produk_list = $produkQuery->paginate(20);
        
        // Get branch list for filter dropdown
        $cabangList = [];
        if (!empty($authorizedCabangIds)) {
            $cabangList = \App\Models\Cabang::whereIn('id', $authorizedCabangIds)
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama])
                ->all();
        }
        
        return Inertia::render('produk/kategori/Show', [
            'kategori' => $kategori,
            'produk_list' => $produk_list,
            'filter_aktif' => [
                'cabang_id' => $selectedCabangId,
            ],
            'cabang_list' => $cabangList,
        ]);
    }

    public function edit(KategoriProduk $kategori)
    {
        Gate::authorize('manage-kategori');
        return Inertia::render('produk/kategori/Edit', ['kategori' => $kategori]);
    }

    public function update(Request $request, KategoriProduk $kategori)
    {
        Gate::authorize('manage-kategori');
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
        Gate::authorize('manage-kategori');
        if ($kategori->produk()->count() > 0) {
            return back()->withErrors(['kategori' => 'Kategori masih memiliki produk']);
        }

        $kategori->delete();
        return redirect()->route('produk.kategori.index')->with('success', 'Kategori produk berhasil dihapus.');
    }
}
