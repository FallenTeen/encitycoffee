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
        'deskripsi',
        'image_path',
        'tipe',
        'satuan_dasar',
        'harga_modal',
        'harga_jual',
        'aktif',
        'perlu_kalibrasi',
    ];

    protected $casts = [
        'harga_modal' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'aktif' => 'boolean',
        'perlu_kalibrasi' => 'boolean',
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
}
