<?php

namespace App\Services;

use App\Models\Produk;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductCacheService
{
    private const CACHE_TTL = 300; // 5 menit
    private const CACHE_PREFIX = 'produk_cabang_';
    
    public function getProdukByCabang(int $cabangId, bool $useCache = true)
    {
        $cacheKey = self::CACHE_PREFIX . $cabangId;
        
        if (!$useCache) {
            return $this->fetchFromDatabase($cabangId);
        }
        
        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cabangId) {
                Log::info('Fetching produk from database', ['cabang_id' => $cabangId]);
                return $this->fetchFromDatabase($cabangId);
            });
        } catch (\Exception $e) {
            Log::error('Cache error, falling back to database', [
                'error' => $e->getMessage(),
                'cabang_id' => $cabangId
            ]);
            return $this->fetchFromDatabase($cabangId);
        }
    }
    
    private function fetchFromDatabase(int $cabangId)
    {
        return Produk::aktif()
            ->withStokCabang($cabangId)
            ->select([
                'produk.id',
                'produk.sku',
                'produk.nama',
                'produk.deskripsi',
                'produk.harga_jual',
                'produk.tipe',
                'produk.image_path'
            ])
            ->whereHas('stokEtalase', function ($query) use ($cabangId) {
                $query->where('cabang_id', $cabangId)
                    ->where('jumlah', '>', 0);
            })
            ->orderBy('produk.nama')
            ->get();
    }
    
    public function clearCache(int $cabangId)
    {
        $cacheKey = self::CACHE_PREFIX . $cabangId;
        Cache::forget($cacheKey);
        Log::info('Product cache cleared', ['cabang_id' => $cabangId]);
    }
    
    public function searchProduk(int $cabangId, string $query, bool $useCache = true)
    {
        $produk = $this->getProdukByCabang($cabangId, $useCache);
        
        if (empty($query)) {
            return $produk;
        }
        
        $searchTerm = strtolower($query);
        return $produk->filter(function ($item) use ($searchTerm) {
            return str_contains(strtolower($item->nama), $searchTerm) ||
                   str_contains(strtolower($item->sku), $searchTerm) ||
                   str_contains(strtolower($item->deskripsi), $searchTerm);
        })->values();
    }
    
    public function getProdukById(int $cabangId, int $produkId, bool $useCache = true)
    {
        $produk = $this->getProdukByCabang($cabangId, $useCache);
        return $produk->where('id', $produkId)->first();
    }
}