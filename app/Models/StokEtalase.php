<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokEtalase extends Model
{
    use HasFactory;

    protected $table = 'stok_etalase';

    protected $fillable = [
        'cabang_id',
        'produk_id',
        'tipe_stok',
        'jumlah',
        'stok_minimum',
    ];

    protected $casts = [
        'jumlah' => 'decimal:4',
        'stok_minimum' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        // Auto-invalidate product cache whenever stok_etalase changes,
        // so kasir mobile app sees up-to-date produk list immediately.
        // Without this hook, cache becomes stale whenever new stok is added
        // (e.g. manager adding a new produk to a cabang -> produk won't show
        // on kasir mobile until 5 min TTL expires).
        static::saved(function (StokEtalase $stokEtalase) {
            try {
                app(\App\Services\ProductCacheService::class)
                    ->clearCache((int) $stokEtalase->cabang_id);
            } catch (\Throwable $e) {
                \Log::warning('StokEtalase saved event: cache clear failed', [
                    'error' => $e->getMessage(),
                    'stok_etalase_id' => $stokEtalase->id,
                ]);
            }
        });

        static::deleted(function (StokEtalase $stokEtalase) {
            try {
                app(\App\Services\ProductCacheService::class)
                    ->clearCache((int) $stokEtalase->cabang_id);
            } catch (\Throwable $e) {
                \Log::warning('StokEtalase deleted event: cache clear failed', [
                    'error' => $e->getMessage(),
                    'stok_etalase_id' => $stokEtalase->id,
                ]);
            }
        });
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function batch()
    {
        return $this->hasMany(BatchStok::class);
    }

    public function mutasi()
    {
        return $this->hasMany(MutasiStok::class);
    }

    public function scopeStokRendah($query)
    {
        return $query->whereColumn('jumlah', '<=', 'stok_minimum');
    }

    public function scopeTipeStok($query, $tipe)
    {
        return $query->where('tipe_stok', $tipe);
    }

    public function getStokRendahAttribute()
    {
        return $this->jumlah <= $this->stok_minimum;
    }
}
