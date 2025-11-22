<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BatchStok extends Model
{
    use HasFactory;

    protected $table = 'batch_stok';

    protected $fillable = [
        'stok_etalase_id',
        'nomor_batch',
        'jumlah',
        'tanggal_kadaluarsa',
        'harga_beli',
        'waktu_terima',
    ];

    protected $casts = [
        'jumlah' => 'decimal:4',
        'harga_beli' => 'decimal:2',
        'tanggal_kadaluarsa' => 'date',
        'waktu_terima' => 'datetime',
    ];

    public function stokEtalase()
    {
        return $this->belongsTo(StokEtalase::class);
    }

    public function scopeMendekatiKadaluarsa($query, $hari = 30)
    {
        return $query->where('tanggal_kadaluarsa', '<=', Carbon::now()->addDays($hari));
    }

    public function scopeSudahKadaluarsa($query)
    {
        return $query->where('tanggal_kadaluarsa', '<', Carbon::now());
    }

    public function getSudahKadaluarsaAttribute()
    {
        return $this->tanggal_kadaluarsa && $this->tanggal_kadaluarsa->isPast();
    }

    public function getMendekatiKadaluarsaAttribute()
    {
        return $this->tanggal_kadaluarsa &&
               $this->tanggal_kadaluarsa->isFuture() &&
               $this->tanggal_kadaluarsa->diffInDays(Carbon::now()) <= 30;
    }
}
