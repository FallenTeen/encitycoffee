<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    use HasFactory;

    protected $table = 'mutasi_stok';

    protected $fillable = [
        'stok_etalase_id',
        'user_id',
        'shift_id',
        'tipe',
        'jumlah_sebelum',
        'jumlah_sesudah',
        'jumlah_perubahan',
        'catatan',
    ];

    protected $casts = [
        'jumlah_sebelum' => 'decimal:4',
        'jumlah_sesudah' => 'decimal:4',
        'jumlah_perubahan' => 'decimal:4',
    ];

    public function stokEtalase()
    {
        return $this->belongsTo(StokEtalase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeTipe($query, $tipe)
    {
        return $query->where('tipe', $tipe);
    }
}
