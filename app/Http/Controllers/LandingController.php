<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Produk;
use App\Models\KategoriProduk;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function home()
    {
        return Inertia::render('Landing/Home');
    }

    public function about()
    {
        return Inertia::render('Landing/About');
    }

    public function menu()
    {
        $cabangList = Cabang::aktif()->get();
        return Inertia::render('Landing/Menu', [
            'cabangList' => $cabangList
        ]);
    }

    public function cabangMenu($slug, $kategoriSlug = null)
    {
        $cabang = Cabang::where('kode', $slug)->aktif()->firstOrFail();

        $rawCategories = KategoriProduk::with(['produk' => function ($query) {
            $query->aktif();
        }])->get();

        $categories = $rawCategories->map(function ($kategori) {
            return [
                'id' => $kategori->id,
                'nama' => $kategori->nama,
                'slug' => $kategori->slug,
                'produk' => $kategori->produk->map(function ($produk) {
                    $imagePath = $produk->image_path;

                    return [
                        'id' => $produk->id,
                        'nama' => $produk->nama,
                        'deskripsi' => $produk->deskripsi,
                        'harga_jual' => $produk->harga_jual,
                        'image_url' => $imagePath ? url('storage/' . ltrim($imagePath, '/')) : null,
                    ];
                })->values(),
            ];
        })->values();

        $selectedCategory = null;
        if ($kategoriSlug) {
            $selectedCategory = KategoriProduk::where('slug', $kategoriSlug)->first();
        }

        return Inertia::render('Landing/OutletMenu', [
            'cabang' => $cabang,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'kategoriSlug' => $kategoriSlug
        ]);
    }
    public function menupercabang(Request $request, $namaCabang = null)
    {
        $cabangList = Cabang::aktif()->get();

        $selectedCabang = null;
        $produkList = [];

        if ($namaCabang !== null) {
            $decodedNama = rawurldecode($namaCabang);
            $selectedCabang = Cabang::whereRaw('LOWER(nama) = ?', [strtolower($decodedNama)])->aktif()->first();

            if ($selectedCabang) {
                // IMPORTANT: Query the produk table directly, not through the
                // old $selectedCabang->produk() belongsToMany (which had its
                // own quirks). Availability per cabang is expressed via
                // stok_etalase: a product shows up here if it has a
                // stok_etalase row for THIS cabang, or if it hasn't been
                // assigned to any cabang yet (fallback while data entry
                // catches up — see scopeUntukCabang).
                $produkList = Produk::query()
                    ->with('kategori')
                    ->aktif()
                    ->bukanBeans()
                    ->untukCabang($selectedCabang->id)
                    ->orderBy('nama')
                    ->get()
                    ->map(function ($produk) {
                        return [
                            'id' => $produk->id,
                            'nama' => $produk->nama,
                            'deskripsi' => $produk->deskripsi,
                            'harga_jual' => $produk->harga_jual,
                            'image_path' => $produk->image_path,
                            'image_url' => $produk->image_path
                                ? url('storage/' . ltrim($produk->image_path, '/'))
                                : null,
                            'kategori' => $produk->kategori ? [
                                'id' => $produk->kategori->id,
                                'nama' => $produk->kategori->nama,
                                'slug' => $produk->kategori->slug,
                            ] : null,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        // Category tabs are derived on the frontend from $produkList itself,
        // so any new category added to kategori_produk shows up automatically
        // without touching this controller.
        return Inertia::render('Landing/ListMenu', [
            'cabangList' => $cabangList,
            'selectedCabang' => $selectedCabang,
            'namaCabang' => $namaCabang,
            'produkList' => $produkList,
        ]);
    }
}
