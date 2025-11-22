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
