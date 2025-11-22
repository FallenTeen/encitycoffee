<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kalibrasi extends Model
{
    use HasFactory;

    protected $table = 'kalibrasi';

    protected $fillable = [
        'shift_id',
        'produk_id',
        'nomor_percobaan',
        'berat_beans_gram',
        'terpilih',
        'catatan',
    ];

    protected $casts = [
        'berat_beans_gram' => 'decimal:2',
        'terpilih' => 'boolean',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function scopeTerpilih($query)
    {
        return $query->where('terpilih', true);
    }
}
