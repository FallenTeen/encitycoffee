<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BundleItem;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';

    protected static function booted(): void
    {
        // Invalidate product cache whenever a produk is created/updated/deleted
        // or when its cabang_id changes. This ensures kasir sees the freshest list.
        $invalidate = function (Produk $produk) {
            try {
                /** @var \App\Services\ProductCacheService $service */
                $service = app(\App\Services\ProductCacheService::class);
                $service->clearAllCache();
            } catch (\Throwable $e) {
                \Log::warning('Produk model event: cache clear failed', [
                    'error' => $e->getMessage(),
                    'produk_id' => $produk->id,
                ]);
            }
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    protected $fillable = [
        'kategori_id',
        'sku',
        'nama',
        'kelompok_nama',
        'varian',
        'deskripsi',
        'image_path',
        'tipe',
        'base',
        'satuan_dasar',
        'harga_modal',
        'harga_jual',
        'aktif',
        'perlu_kalibrasi',
        'audit_log',
        'cabang_id',
    ];

    protected $casts = [
        'harga_modal' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'aktif' => 'boolean',
        'perlu_kalibrasi' => 'boolean',
        'audit_log' => 'array',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriProduk::class, 'kategori_id');
    }

    /**
     * Relasi ke cabang tempat produk dibuat.
     * NULL berarti produk adalah legacy (sebelum sistem branch isolation).
     */
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function bundleItems()
    {
        return $this->hasMany(BundleItem::class, 'bundle_id');
    }

    public function satuan()
    {
        return $this->hasMany(SatuanProduk::class);
    }

    public function stokEtalase()
    {
        return $this->hasMany(StokEtalase::class);
    }

    public function itemTransaksi()
    {
        return $this->hasMany(ItemTransaksi::class);
    }

    public function kalibrasi()
    {
        return $this->hasMany(Kalibrasi::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Exclude Beans products from the query.
     *
     * A product is considered "Beans" if EITHER:
     *  - its `tipe` column equals 'beans', OR
     *  - its related KategoriProduk has nama/slug equal to 'beans' (case-insensitive)
     *
     * Beans are shown on a separate customer-facing screen, so every other
     * category (Coffee, Non-Coffee, Tea, Drinks, Snacks, Desserts, Sets, and
     * any future category) must still come through untouched.
     */
    public function scopeBukanBeans($query)
    {
        return $query
            ->where(function ($q) {
                $q->whereNull('tipe')->orWhere('tipe', '!=', 'beans');
            })
            ->whereDoesntHave('kategori', function ($q) {
                $q->whereRaw('LOWER(nama) = ?', ['beans'])
                    ->orWhereRaw('LOWER(slug) = ?', ['beans']);
            });
    }

    /**
     * Restrict to products available at a given cabang, based on cabang_id.
     * 
     * ATURAN (Cabang Isolation):
     * - Produk dengan cabang_id NULL hanya bisa dilihat oleh IT Support (legacy data)
     * - Produk dengan cabang_id = X hanya bisa dilihat oleh:
     *   - IT Support (semua cabang)
     *   - Manager yang mengampu cabang X
     *   - Manager multi-cabang yang mengampu cabang X
     * 
     * PENTING: Filter ini harus dikombinasikan dengan filter akses user di controller
     * karena scope ini tidak tahu role/user mana yang mengakses.
     */
    public function scopeUntukCabang($query, $cabangId)
    {
        return $query->where(function ($q) use ($cabangId) {
            // Produk yang dibuat di cabang ini
            $q->where('cabang_id', $cabangId)
            // ATAU produk yang ada di stok_etalase cabang ini (untuk backward compatibility)
              ->orWhereHas('stokEtalase', function ($sq) use ($cabangId) {
                  $sq->where('cabang_id', $cabangId);
              });
        });
    }
    
    /**
     * Scope untuk mengambil produk yang dibuat oleh cabang tertentu.
     * NULL cabang_id berarti legacy product (sebelum sistem branch isolation).
     */
    public function scopeDibuatDiCabang($query, $cabangId)
    {
        return $query->where('cabang_id', $cabangId);
    }
    
    /**
     * Scope untuk produk legacy (tanpa cabang_id).
     * Hanya untuk IT Support.
     */
    public function scopeLegacyProduk($query)
    {
        return $query->whereNull('cabang_id');
    }
    
    /**
     * Scope untuk filter produk berdasarkan daftar cabang yang diizinkan.
     * Untuk manager multi-cabang yang bisa akses semua cabangnya.
     */
    public function scopeDiCabangManapun($query, array $cabangIds)
    {
        return $query->where(function ($q) use ($cabangIds) {
            // Produk yang dibuat di salah satu cabang yang diizinkan
            $q->whereIn('cabang_id', $cabangIds)
            // ATAU produk yang ada di stok_etalase salah satu cabang yang diizinkan
              ->orWhereHas('stokEtalase', function ($sq) use ($cabangIds) {
                  $sq->whereIn('cabang_id', $cabangIds);
              });
        });
    }

    public function scopeTipe($query, $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    public function scopeBeans($query)
    {
        return $query->where('tipe', 'beans');
    }

    public function scopeMinuman($query)
    {
        return $query->where('tipe', 'minuman');
    }

    public function scopeSnack($query)
    {
        return $query->where('tipe', 'snack');
    }

    public function scopeCabang($query, $cabangId)
    {
        // Filter berdasarkan cabang_id produk atau stok_etalase
        return $query->where(function ($q) use ($cabangId) {
            $q->where('cabang_id', $cabangId)
              ->orWhereHas('stokEtalase', function ($sq) use ($cabangId) {
                  $sq->where('cabang_id', $cabangId);
              });
        });
    }

    public function scopeWithStokCabang($query, $cabangId)
    {
        return $query->with([
            'stokEtalase' => function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            }, 
            'kategori',
            'cabang'
        ]);
    }

    public function getStokCabang($cabangId)
    {
        return $this->stokEtalase()
            ->where('cabang_id', $cabangId)
            ->first();
    }

    public function getHargaFormattedAttribute()
    {
        return 'Rp ' . number_format($this->harga_jual, 0, ',', '.');
    }

    public function addAuditLog(string $action, array $data = []): void
    {
        $log = $this->audit_log ?? [];
        $log[] = [
            'action' => $action,
            'user_id' => auth()->id(),
            'cabang_id' => $this->cabang_id, // Track cabang_id
            'timestamp' => now()->toDateTimeString(),
            'data' => $data,
        ];
        $this->update(['audit_log' => $log]);
    }
    
    /**
     * Check apakah produk adalah legacy (sebelum sistem branch isolation).
     */
    public function isLegacy(): bool
    {
        return is_null($this->cabang_id);
    }
    
    /**
     * Get nama cabang tempat produk dibuat.
     */
    public function getCabangNameAttribute(): ?string
    {
        return $this->cabang?->nama;
    }
}
