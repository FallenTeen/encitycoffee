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
    
    /**
     * Ambil produk berdasarkan multiple cabang IDs.
     * Untuk manager multi-cabang.
     */
    public function getProdukByCabangMulti(array $cabangIds, bool $useCache = true)
    {
        if (empty($cabangIds)) {
            return collect([]);
        }
        
        // Single branch - use existing method
        if (count($cabangIds) === 1) {
            return $this->getProdukByCabang($cabangIds[0], $useCache);
        }
        
        // Sort IDs to ensure consistent cache key regardless of order
        $sortedCabangIds = array_values(array_unique(array_map('intval', $cabangIds)));
        sort($sortedCabangIds);
        
        // Multiple branches - fetch all and merge
        $cacheKey = 'produk_multi_' . implode('_', $sortedCabangIds);
        
        if (!$useCache) {
            return $this->fetchFromDatabaseMulti($sortedCabangIds);
        }
        
        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($sortedCabangIds) {
                Log::info('Fetching produk from database for multiple cabang', ['cabang_ids' => $sortedCabangIds]);
                return $this->fetchFromDatabaseMulti($sortedCabangIds);
            });
        } catch (\Exception $e) {
            Log::error('Cache error for multi-cabang, falling back to database', [
                'error' => $e->getMessage(),
                'cabang_ids' => $sortedCabangIds
            ]);
            return $this->fetchFromDatabaseMulti($sortedCabangIds);
        }
    }
    
    private function fetchFromDatabaseMulti(array $cabangIds)
    {
        $produk = Produk::query()
            ->with([
                'stokEtalase' => function ($q) use ($cabangIds) {
                    $q->whereIn('cabang_id', $cabangIds);
                }, 
                'kategori',
                'cabang'
            ])
            ->where(function ($query) use ($cabangIds) {
                // Produk dengan cabang_id di salah satu cabang
                $query->whereIn('cabang_id', $cabangIds)
                    // ATAU produk yang ada di stok_etalase salah satu cabang
                      ->orWhereHas('stokEtalase', function ($sq) use ($cabangIds) {
                          $sq->whereIn('cabang_id', $cabangIds);
                      });
            })
            ->select([
                'produk.id',
                'produk.sku',
                'produk.nama',
                'produk.kelompok_nama',
                'produk.varian',
                'produk.deskripsi',
                'produk.harga_jual',
                'produk.tipe',
                'produk.base',
                'produk.image_path',
                'produk.kategori_id',
                'produk.harga_modal',
                'produk.satuan_dasar',
                'produk.aktif',
                'produk.perlu_kalibrasi',
                'produk.cabang_id'
            ])
            ->orderBy('produk.nama')
            ->get();
            
        Log::info('Produk multi-cabang database fetch completed', [
            'cabang_ids' => $cabangIds,
            'produk_count' => $produk->count(),
        ]);
        
        return $produk;
    }

    private function fetchFromDatabase(int $cabangId)
    {
        $produk = Produk::query()
            ->untukCabang($cabangId)
            ->with([
                'stokEtalase' => function ($q) use ($cabangId) {
                    $q->where('cabang_id', $cabangId);
                },
                'kategori',
                'cabang',
            ])
            ->select([
                'produk.id',
                'produk.sku',
                'produk.nama',
                'produk.kelompok_nama',
                'produk.varian',
                'produk.deskripsi',
                'produk.harga_jual',
                'produk.tipe',
                'produk.base',
                'produk.image_path',
                'produk.kategori_id',
                'produk.harga_modal',
                'produk.satuan_dasar',
                'produk.aktif',
                'produk.perlu_kalibrasi',
                'produk.cabang_id'
            ])
            ->orderBy('produk.nama')
            ->get();

        Log::info('Produk database fetch completed', [
            'cabang_id' => $cabangId,
            'produk_count' => $produk->count(),
            'produk_ids' => $produk->pluck('id')->all(),
            'produk_names' => $produk->pluck('nama')->all(),
        ]);

        return $produk;
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
        return Produk::query()
            ->with(['stokEtalase.cabang', 'kategori'])
            ->select([
                'produk.id',
                'produk.sku',
                'produk.nama',
                'produk.kelompok_nama',
                'produk.varian',
                'produk.deskripsi',
                'produk.harga_jual',
                'produk.tipe',
                'produk.base',
                'produk.image_path',
                'produk.kategori_id',
                'produk.harga_modal',
                'produk.satuan_dasar',
                'produk.aktif',
                'produk.perlu_kalibrasi',
                'produk.cabang_id'
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

        // Clear all multi-branch cache keys
        // Since we can't list all possible multi-cabang combinations, 
        // we increment the cache version to invalidate all multi-branch caches
        $this->incrementCacheVersion();

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
    
    /**
     * Search produk berdasarkan multiple cabang IDs.
     */
    public function searchProdukMultiCabang(array $cabangIds, string $query, bool $useCache = true)
    {
        // Sort IDs to ensure consistent cache key
        $sortedCabangIds = array_values(array_unique(array_map('intval', $cabangIds)));
        sort($sortedCabangIds);
        
        $produk = $this->getProdukByCabangMulti($sortedCabangIds, $useCache);

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