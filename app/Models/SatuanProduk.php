<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatuanProduk extends Model
{
    use HasFactory;

    protected $table = 'satuan_produk';

    protected $fillable = [
        'produk_id',
        'nama_satuan',
        'nilai_konversi',
    ];

    protected $casts = [
        'nilai_konversi' => 'decimal:4',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}
