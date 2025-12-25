<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpenBillItem extends Model
{
    use HasFactory;

    protected $table = 'open_bill_items';

    protected $fillable = [
        'open_bill_id',
        'produk_id',
        'jumlah',
        'harga_satuan',
        'subtotal',
        'catatan',
    ];

    public function openBill()
    {
        return $this->belongsTo(OpenBill::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}

