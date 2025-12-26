<?php

namespace App\Http\Controllers;

use App\Services\ProductCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Models\Cabang;

class ProdukController extends Controller
{
    protected $productCacheService;
    
    public function __construct(ProductCacheService $productCacheService)
    {
        $this->productCacheService = $productCacheService;
    }
    
    /**
     * Display products page
     * URL: /produk (untuk manager/it_support akan tampil semua cabang)
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Tentukan cabang_id berdasarkan role
            // Manager/IT Support bisa pilih cabang, role lain otomatis ambil dari user
            if (in_array($user->role, ['manager', 'it_support'])) {
                // Ambil dari query parameter atau cabang pertama sebagai default
                $cabangId = $request->input('cabang_id');
                
                // Jika tidak ada cabang_id, ambil cabang pertama
                if (!$cabangId) {
                    $defaultCabang = Cabang::first();
                    $cabangId = $defaultCabang ? $defaultCabang->id : null;
                }
                
                // Ambil semua cabang untuk dropdown
                $cabangList = Cabang::select('id', 'nama', 'kode')->get();
            } else {
                // Role lain (supervisor, kasir) gunakan cabang mereka sendiri
                $cabangId = $user->cabang_id;
                $cabangList = null; // Tidak perlu dropdown
            }
            
            // Validasi cabang_id jika ada
            if ($cabangId) {
                $v = Validator::make(['cabang_id' => $cabangId], [
                    'cabang_id' => 'required|integer|exists:cabang,id'
                ]);
                
                if ($v->fails()) {
                    Log::warning('Invalid cabang_id for produk index', [
                        'cabang_id' => $cabangId,
                        'user_id' => $user->id,
                        'errors' => $v->errors()
                    ]);
                    
                    // Redirect ke cabang pertama jika invalid
                    $defaultCabang = Cabang::first();
                    return redirect()->route('produk.index', ['cabang_id' => $defaultCabang->id]);
                }
            }
            
            // Ambil data produk jika ada cabang_id
            $produk = null;
            $selectedCabang = null;
            
            if ($cabangId) {
                $useCache = $request->boolean('use_cache', true);
                $search = $request->input('search', '');
                
                Log::info('Fetching produk for cabang', [
                    'cabang_id' => $cabangId,
                    'use_cache' => $useCache,
                    'search' => $search,
                    'user_id' => $user->id
                ]);
                
                // Cari produk
                if ($search) {
                    $produk = $this->productCacheService->searchProduk($cabangId, $search, $useCache);
                } else {
                    $produk = $this->productCacheService->getProdukByCabang($cabangId, $useCache);
                }
                
                // Ambil info cabang yang dipilih
                $selectedCabang = Cabang::find($cabangId);
                
                Log::info('Produk fetched successfully', [
                    'cabang_id' => $cabangId,
                    'count' => $produk ? $produk->count() : 0,
                    'search' => $search
                ]);
            }
            
            // Render halaman Inertia
            return Inertia::render('Produk/Index', [
                'produk' => $produk,
                'cabangList' => $cabangList,
                'selectedCabang' => $selectedCabang,
                'filters' => [
                    'search' => $request->input('search', ''),
                    'cabang_id' => $cabangId
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching produk', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Gagal mengambil data produk: ' . $e->getMessage());
        }
    }
    
    /**
     * Show product detail page
     * URL: /produk/{produk}
     */
    public function show(Request $request, $produkId)
    {
        try {
            $user = auth()->user();
            
            // Tentukan cabang_id
            if (in_array($user->role, ['manager', 'it_support'])) {
                $cabangId = $request->input('cabang_id');
                
                // Jika tidak ada, ambil cabang pertama
                if (!$cabangId) {
                    $defaultCabang = Cabang::first();
                    $cabangId = $defaultCabang ? $defaultCabang->id : null;
                }
            } else {
                $cabangId = $user->cabang_id;
            }
            
            // Validasi
            $v = Validator::make([
                'cabang_id' => $cabangId,
                'produk_id' => $produkId
            ], [
                'cabang_id' => 'required|integer|exists:cabang,id',
                'produk_id' => 'required|integer|exists:produk,id'
            ]);
            
            if ($v->fails()) {
                return back()->with('error', 'Data tidak valid');
            }
            
            $useCache = $request->boolean('use_cache', true);
            $produk = $this->productCacheService->getProdukById($cabangId, $produkId, $useCache);
            
            if (!$produk) {
                return back()->with('error', 'Produk tidak ditemukan di cabang ini');
            }
            
            $selectedCabang = Cabang::find($cabangId);
            
            return Inertia::render('Produk/Show', [
                'produk' => $produk,
                'selectedCabang' => $selectedCabang
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching produk detail', [
                'produk_id' => $produkId,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Gagal mengambil detail produk');
        }
    }
    
    /**
     * Clear product cache
     * URL: POST /produk/cache/clear
     */
    public function clearCache(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Tentukan cabang_id
            if (in_array($user->role, ['manager', 'it_support'])) {
                $cabangId = $request->input('cabang_id');
            } else {
                $cabangId = $user->cabang_id;
            }
            
            // Validasi
            $v = Validator::make(['cabang_id' => $cabangId], [
                'cabang_id' => 'required|integer|exists:cabang,id'
            ]);
            
            if ($v->fails()) {
                return back()->with('error', 'Parameter cabang tidak valid');
            }
            
            $this->productCacheService->clearCache($cabangId);
            
            Log::info('Product cache cleared manually', [
                'cabang_id' => $cabangId,
                'user_id' => $user->id
            ]);
            
            return back()->with('success', 'Cache produk berhasil dibersihkan');
            
        } catch (\Exception $e) {
            Log::error('Error clearing product cache', [
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Gagal membersihkan cache');
        }
    }
}