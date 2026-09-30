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
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeGagal($query)
    {
        return $query->where('status', 'gagal');
    }

    public function scopeTersinkronisasi($query)
    {
        return $query->where('status', 'tersinkronisasi');
    }
}
