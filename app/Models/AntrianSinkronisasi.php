<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AntrianSinkronisasi extends Model
{
    use HasFactory;

    protected $table = 'antrian_sinkronisasi';

    protected $fillable = [
        'id_perangkat',
        'tipe_entitas',
        'id_entitas',
        'payload',
        'status',
        'jumlah_percobaan',
        'waktu_sinkronisasi',
    ];

    protected $casts = [
        'waktu_sinkronisasi' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeGagal($query)
    {
        return $query->where('status', 'gagal');
    }
}
