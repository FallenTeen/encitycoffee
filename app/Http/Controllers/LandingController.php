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
}
