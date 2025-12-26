<?php

namespace App\Http\Controllers;

use App\Services\ProductCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\KategoriProduk;

class ProdukController extends Controller
{
    protected $productCacheService;
    
    public function __construct(ProductCacheService $productCacheService)
    {
        $this->productCacheService = $productCacheService;
    }
    
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $cabangId = $this->getCabangId($request, $user);
            
            if (!$cabangId) {
                return Inertia::render('Produk/Index', [
                    'produk' => collect(),
                    'cabangList' => $this->getCabangList($user),
                    'selectedCabang' => null,
                    'filters' => ['search' => '', 'cabang_id' => null],
                    'kategoriList' => KategoriProduk::all()
                ]);
            }
            
            $produk = $this->getProdukByCabang($cabangId, $request);
            $selectedCabang = Cabang::find($cabangId);
            
            return Inertia::render('Produk/Index', [
                'produk' => $produk,
                'cabangList' => $this->getCabangList($user),
                'selectedCabang' => $selectedCabang,
                'filters' => [
                    'search' => $request->input('search', ''),
                    'cabang_id' => $cabangId,
                    'kategori_id' => $request->input('kategori_id', '')
                ],
                'kategoriList' => KategoriProduk::all()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching produk', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return back()->with('error', 'Gagal mengambil data produk');
        }
    }
    
    public function show(Request $request, $produkId)
    {
        try {
            $user = auth()->user();
            $cabangId = $this->getCabangId($request, $user);
            
            if (!$cabangId) {
                return back()->with('error', 'Cabang belum dipilih');
            }
            
            $produk = $this->productCacheService->getProdukById($cabangId, $produkId);
            
            if (!$produk) {
                return back()->with('error', 'Produk tidak ditemukan di cabang ini');
            }
            
            return Inertia::render('Produk/Show', [
                'produk' => $produk,
                'selectedCabang' => Cabang::find($cabangId)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching produk detail', [
                'produk_id' => $produkId,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Gagal mengambil detail produk');
        }
    }
    
    public function clearCache(Request $request)
    {
        try {
            $user = auth()->user();
            $cabangId = $this->getCabangId($request, $user);
            
            if (!$cabangId) {
                return back()->with('error', 'Cabang belum dipilih');
            }
            
            $this->productCacheService->clearCache($cabangId);
            
            Log::info('Product cache cleared', [
                'cabang_id' => $cabangId,
                'user_id' => $user->id
            ]);
            
            return back()->with('success', 'Cache produk berhasil dibersihkan');
            
        } catch (\Exception $e) {
            Log::error('Error clearing cache', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal membersihkan cache');
        }
    }
    
    public function daftarProduk(Request $request)
    {
        try {
            $user = auth()->user();
            $cabangId = $this->getCabangId($request, $user);
            
            if (!$cabangId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cabang belum ditetapkan'
                ], 400);
            }
            
            $search = $request->input('search', '');
            $kategoriId = $request->input('kategori_id', '');
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            
            $produk = $this->getProdukForApi($cabangId, $search, $kategoriId);
            
            $total = $produk->count();
            $offset = ($page - 1) * $perPage;
            $items = $produk->slice($offset, $perPage)->values();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ],
                'kategori_list' => KategoriProduk::select('id', 'nama')->get()
            ]);
            
        } catch (\Exception $e) {
            Log::error('API Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data produk'
            ], 500);
        }
    }
    
    public function mobileProduk(Request $request)
    {
        try {
            $user = auth()->user();
            $cabangId = $user->cabang_id ?? $request->input('cabang_id');
            
            if (!$cabangId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cabang tidak tersedia'
                ], 400);
            }
            
            $search = $request->input('search', '');
            $kategoriId = $request->input('kategori_id', '');
            $tipe = $request->input('tipe', '');
            
            $produk = $this->getProdukForApi($cabangId, $search, $kategoriId, $tipe);
            
            return response()->json([
                'success' => true,
                'produk' => $produk,
                'kategori_list' => KategoriProduk::select('id', 'nama')->get(),
                'total' => $produk->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mobile API Error', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data produk'
            ], 500);
        }
    }
    
    private function getCabangId(Request $request, $user)
    {
        if (in_array($user->role, ['manager', 'it_support'])) {
            $cabangId = $request->input('cabang_id');
            
            if (!$cabangId) {
                $defaultCabang = Cabang::first();
                $cabangId = $defaultCabang ? $defaultCabang->id : null;
            }
            
            return $cabangId;
        }
        
        return $user->cabang_id;
    }
    
    private function getCabangList($user)
    {
        if (in_array($user->role, ['manager', 'it_support'])) {
            return Cabang::select('id', 'nama', 'kode')->get();
        }
        
        return null;
    }
    
    private function getProdukByCabang($cabangId, Request $request)
    {
        $useCache = $request->boolean('use_cache', true);
        $search = $request->input('search', '');
        $kategoriId = $request->input('kategori_id', '');
        
        if ($search) {
            $produk = $this->productCacheService->searchProduk($cabangId, $search, $useCache);
        } else {
            $produk = $this->productCacheService->getProdukByCabang($cabangId, $useCache);
        }
        
        if ($kategoriId) {
            $produk = $produk->where('kategori_id', $kategoriId);
        }
        
        return $produk;
    }
    
    private function getProdukForApi($cabangId, $search = '', $kategoriId = '', $tipe = '')
    {
        if ($search) {
            $produk = $this->productCacheService->searchProduk($cabangId, $search);
        } else {
            $produk = $this->productCacheService->getProdukByCabang($cabangId);
        }
        
        if ($kategoriId) {
            $produk = $produk->where('kategori_id', $kategoriId);
        }
        
        if ($tipe) {
            $produk = $produk->where('tipe', $tipe);
        }
        
        return $produk->map(function ($item) {
            return [
                'id' => $item->id,
                'sku' => $item->sku,
                'nama' => $item->nama,
                'deskripsi' => $item->deskripsi,
                'harga_jual' => $item->harga_jual,
                'tipe' => $item->tipe,
                'kategori_id' => $item->kategori_id,
                'image_path' => $item->image_path,
                'stok_etalase' => $item->stokEtalase->first() ? [
                    'jumlah' => $item->stokEtalase->first()->jumlah,
                    'stok_minimum' => $item->stokEtalase->first()->stok_minimum
                ] : null
            ];
        });
    }
}