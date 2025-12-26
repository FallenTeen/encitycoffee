<?php

namespace App\Http\Controllers;

use App\Services\ProductCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProdukController extends Controller
{
    protected $productCacheService;
    
    public function __construct(ProductCacheService $productCacheService)
    {
        $this->productCacheService = $productCacheService;
    }
    
    /**
     * Get all products by cabang
     * URL: /produk?cabang_id=1&search=kopi&use_cache=true
     */
    public function index(Request $request)
    {
        // Ambil cabang_id dari query parameter
        $cabangId = $request->input('cabang_id');
        
        // Validasi cabang_id
        $v = Validator::make(['cabang_id' => $cabangId], [
            'cabang_id' => 'required|integer|exists:cabang,id'
        ]);
        
        if ($v->fails()) {
            Log::warning('Invalid cabang_id for produk index', [
                'cabang_id' => $cabangId,
                'errors' => $v->errors()
            ]);
            return response()->json([
                'error' => 'Parameter cabang_id tidak valid',
                'details' => $v->errors()
            ], 422);
        }
        
        try {
            $useCache = $request->boolean('use_cache', true);
            $search = $request->input('search', '');
            
            Log::info('Fetching produk for cabang', [
                'cabang_id' => $cabangId,
                'use_cache' => $useCache,
                'search' => $search
            ]);
            
            // Cari produk berdasarkan search atau ambil semua
            if ($search) {
                $produk = $this->productCacheService->searchProduk($cabangId, $search, $useCache);
            } else {
                $produk = $this->productCacheService->getProdukByCabang($cabangId, $useCache);
            }
            
            Log::info('Produk fetched successfully', [
                'cabang_id' => $cabangId,
                'count' => $produk->count(),
                'search' => $search
            ]);
            
            return response()->json([
                'produk' => $produk,
                'count' => $produk->count(),
                'cached' => $useCache,
                'cabang_id' => $cabangId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching produk', [
                'cabang_id' => $cabangId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Gagal mengambil data produk',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single product detail
     * URL: /produk/{produk}?cabang_id=1
     */
    public function show(Request $request, $produkId)
    {
        // Ambil cabang_id dari query parameter
        $cabangId = $request->input('cabang_id');
        
        // Validasi
        $v = Validator::make([
            'cabang_id' => $cabangId,
            'produk_id' => $produkId
        ], [
            'cabang_id' => 'required|integer|exists:cabang,id',
            'produk_id' => 'required|integer|exists:produk,id'
        ]);
        
        if ($v->fails()) {
            return response()->json([
                'error' => 'Parameter tidak valid',
                'details' => $v->errors()
            ], 422);
        }
        
        try {
            $useCache = $request->boolean('use_cache', true);
            $produk = $this->productCacheService->getProdukById($cabangId, $produkId, $useCache);
            
            if (!$produk) {
                return response()->json([
                    'error' => 'Produk tidak ditemukan di cabang ini'
                ], 404);
            }
            
            return response()->json([
                'produk' => $produk,
                'cabang_id' => $cabangId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching produk detail', [
                'cabang_id' => $cabangId,
                'produk_id' => $produkId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Gagal mengambil detail produk'
            ], 500);
        }
    }
    
    /**
     * Clear product cache
     * URL: /produk/cache/clear?cabang_id=1
     */
    public function clearCache(Request $request)
    {
        // Ambil cabang_id dari query parameter
        $cabangId = $request->input('cabang_id');
        
        // Validasi
        $v = Validator::make(['cabang_id' => $cabangId], [
            'cabang_id' => 'required|integer|exists:cabang,id'
        ]);
        
        if ($v->fails()) {
            return response()->json([
                'error' => 'Parameter cabang_id tidak valid',
                'details' => $v->errors()
            ], 422);
        }
        
        try {
            $this->productCacheService->clearCache($cabangId);
            
            Log::info('Product cache cleared manually', [
                'cabang_id' => $cabangId,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'message' => 'Cache produk berhasil dibersihkan',
                'cabang_id' => $cabangId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error clearing product cache', [
                'cabang_id' => $cabangId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Gagal membersihkan cache'
            ], 500);
        }
    }
}