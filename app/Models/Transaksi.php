<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';

    protected $fillable = [
        'shift_id',
        'cabang_id',
        'user_id',
        'nomor_invoice',
        'subtotal',
        'diskon',
        'pajak',
        'total',
        'status',
        'catatan',
        'waktu_selesai',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'pajak' => 'decimal:2',
        'total' => 'decimal:2',
        'waktu_selesai' => 'datetime',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->hasMany(ItemTransaksi::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', 'selesai');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getTotalTunaiAttribute()
    {
        return $this->pembayaran()->where('metode_pembayaran', 'tunai')->sum('jumlah');
    }

    public function getTotalQrisAttribute()
    {
        return $this->pembayaran()->where('metode_pembayaran', 'qris')->sum('jumlah');
    }
}
