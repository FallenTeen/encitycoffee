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
        
        $categories = KategoriProduk::with(['produk' => function ($query) {
            $query->aktif();
        }])->get();

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
