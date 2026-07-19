<?php

namespace App\Models;

use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    use HasFactory;

    protected $table = 'cabang';

    protected $fillable = [
        'kode',
        'nama',
        'alamat',
        'telepon',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_cabang');
    }

    public function stokEtalase()
    {
        return $this->hasMany(StokEtalase::class);
    }

    public function produk()
    {
        return $this->belongsToMany(Produk::class, 'stok_etalase', 'cabang_id', 'produk_id')
            ->withPivot(['tipe_stok', 'jumlah', 'stok_minimum']);
    }

    public function shift()
    {
        return $this->hasMany(Shift::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
