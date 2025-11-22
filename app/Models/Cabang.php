<?php

namespace App\Models;

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
