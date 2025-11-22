<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $table = 'shift';

    protected $fillable = [
        'user_id',
        'cabang_id',
        'saldo_awal',
        'saldo_akhir',
        'saldo_diharapkan',
        'selisih',
        'total_tunai',
        'total_qris',
        'waktu_buka',
        'waktu_tutup',
        'status',
        'catatan',
    ];

    protected $casts = [
        'saldo_awal' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
        'saldo_diharapkan' => 'decimal:2',
        'selisih' => 'decimal:2',
        'total_tunai' => 'decimal:2',
        'total_qris' => 'decimal:2',
        'waktu_buka' => 'datetime',
        'waktu_tutup' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function kalibrasi()
    {
        return $this->hasMany(Kalibrasi::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function mutasiStok()
    {
        return $this->hasMany(MutasiStok::class);
    }

    public function scopeBuka($query)
    {
        return $query->where('status', 'buka');
    }

    public function scopeTutup($query)
    {
        return $query->where('status', 'tutup');
    }

    public function getSedangBukaAttribute()
    {
        return $this->status === 'buka';
    }

    public function getTotalTransaksiAttribute()
    {
        return $this->transaksi()->where('status', 'selesai')->sum('total');
    }
}
