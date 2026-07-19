<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';

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
     * Restrict to products available at a given cabang, based on stok_etalase.
     *
     * Each cabang has its own menu — a product only belongs to a cabang if it
     * has a stok_etalase row for that cabang_id. As a safety net for products
     * that haven't been assigned to any cabang yet (no stok_etalase rows at
     * all), they still show up everywhere so nothing silently disappears
     * while branch data entry is still in progress. Once a product gets its
     * first stok_etalase row, it becomes branch-specific.
     */
    public function scopeUntukCabang($query, $cabangId)
    {
        return $query->where(function ($q) use ($cabangId) {
            $q->whereHas('stokEtalase', function ($sq) use ($cabangId) {
                $sq->where('cabang_id', $cabangId);
            })->orWhereDoesntHave('stokEtalase');
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
        return $query->whereHas('stokEtalase', function ($q) use ($cabangId) {
            $q->where('cabang_id', $cabangId);
        });
    }

    public function scopeWithStokCabang($query, $cabangId)
    {
        return $query->with(['stokEtalase' => function ($q) use ($cabangId) {
            $q->where('cabang_id', $cabangId);
        }, 'kategori']);
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
            'timestamp' => now()->toDateTimeString(),
            'data' => $data,
        ];
        $this->update(['audit_log' => $log]);
    }
}
