<?php

namespace App\Services;

use App\Models\Produk;
use App\Models\StokEtalase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductCacheService
{
    private const CACHE_TTL = 300; // 5 menit
    private const CACHE_PREFIX = 'produk_cabang_';
    private const GLOBAL_CACHE_PREFIX = 'produk_global_';
    private const CACHE_VERSION_KEY = 'produk_cache_version';
    
    public function getProdukByCabang(int $cabangId, bool $useCache = true)
    {
        $cacheKey = $this->getCacheKey($cabangId);
        
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
                'produk.image_path',
                'produk.kategori_id',
                'produk.harga_modal',
                'produk.satuan_dasar',
                'produk.perlu_kalibrasi'
            ])
            ->whereHas('stokEtalase', function ($query) use ($cabangId) {
                $query->where('cabang_id', $cabangId)
                    ->where('jumlah', '>', 0);
            })
            ->orderBy('produk.nama')
            ->get();
    }
    
    public function getAllProduk(bool $useCache = true)
    {
        $cacheKey = self::GLOBAL_CACHE_PREFIX . 'all';
        
        if (!$useCache) {
            return $this->fetchAllFromDatabase();
        }
        
        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () {
                Log::info('Fetching all produk from database');
                return $this->fetchAllFromDatabase();
            });
        } catch (\Exception $e) {
            Log::error('Cache error for all produk, falling back to database', [
                'error' => $e->getMessage()
            ]);
            return $this->fetchAllFromDatabase();
        }
    }
    
    private function fetchAllFromDatabase()
    {
        return Produk::aktif()
            ->with(['stokEtalase.cabang', 'kategori'])
            ->select([
                'produk.id',
                'produk.sku',
                'produk.nama',
                'produk.deskripsi',
                'produk.harga_jual',
                'produk.tipe',
                'produk.image_path',
                'produk.kategori_id',
                'produk.harga_modal',
                'produk.satuan_dasar',
                'produk.perlu_kalibrasi'
            ])
            ->orderBy('produk.nama')
            ->get();
    }
    
    public function clearCache(int $cabangId)
    {
        $cacheKey = $this->getCacheKey($cabangId);
        Cache::forget($cacheKey);
        
        // Also clear global cache
        Cache::forget(self::GLOBAL_CACHE_PREFIX . 'all');
        
        Log::info('Product cache cleared', ['cabang_id' => $cabangId]);
    }
    
    public function clearAllCache()
    {
        // Get all cabang IDs
        $cabangIds = \App\Models\Cabang::pluck('id')->all();
        
        foreach ($cabangIds as $cabangId) {
            $this->clearCache($cabangId);
        }
        
        Log::info('All product cache cleared');
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
    
    public function getProdukByIds(array $produkIds, int $cabangId, bool $useCache = true)
    {
        $produk = $this->getProdukByCabang($cabangId, $useCache);
        return $produk->whereIn('id', $produkIds)->values();
    }
    
    public function getStokRendah(int $cabangId, bool $useCache = true)
    {
        $produk = $this->getProdukByCabang($cabangId, $useCache);
        
        return $produk->filter(function ($item) {
            $stokEtalase = $item->stokEtalase->first();
            return $stokEtalase && $stokEtalase->jumlah <= $stokEtalase->stok_minimum;
        })->values();
    }
    
    public function getCacheStats(int $cabangId)
    {
        $cacheKey = $this->getCacheKey($cabangId);
        $hasCache = Cache::has($cacheKey);
        
        return [
            'has_cache' => $hasCache,
            'cache_key' => $cacheKey,
            'ttl' => $hasCache ? self::CACHE_TTL : 0,
            'timestamp' => $hasCache ? Cache::get($cacheKey . '_timestamp') : null
        ];
    }
    
    private function getCacheKey(int $cabangId): string
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, 'v1');
        return self::CACHE_PREFIX . $cabangId . '_' . $version;
    }
    
    public function incrementCacheVersion()
    {
        $currentVersion = Cache::get(self::CACHE_VERSION_KEY, 'v1');
        $versionNumber = (int) str_replace('v', '', $currentVersion);
        $newVersion = 'v' . ($versionNumber + 1);
        
        Cache::put(self::CACHE_VERSION_KEY, $newVersion, 86400); // 24 hours
        Log::info('Cache version incremented', ['old_version' => $currentVersion, 'new_version' => $newVersion]);
    }
}