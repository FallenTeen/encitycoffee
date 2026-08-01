<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleItem extends Model
{
    use HasFactory;

    protected $table = 'bundle_items';

    protected $fillable = [
        'bundle_id',
        'produk_id',
        'jumlah',
    ];

    public function bundle()
    {
        return $this->belongsTo(Produk::class, 'bundle_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
