<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transaksi';

    protected $fillable = [
        'shift_id',
        'cabang_id',
        'user_id',
        'nama_pelanggan',
        'nomor_invoice',
        'subtotal',
        'diskon',
        'pajak',
        'total',
        'status',
        'catatan',
        'waktu_selesai',
        'deleted_by',
        'delete_reason',
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

    public function scopeBatal($query)
    {
        return $query->where('status', 'batal');
    }

    public function getTotalTunaiAttribute()
    {
        return $this->pembayaran()->where('metode_pembayaran', 'tunai')->sum('jumlah');
    }

    public function getTotalQrisAttribute()
    {
        return $this->pembayaran()->where('metode_pembayaran', 'qris')->sum('jumlah');
    }

    /**
     * Get formatted waktu_selesai with WIB timezone for receipt printing
     */
    public function getWaktuSelesaiFormattedAttribute()
    {
        if (!$this->waktu_selesai) {
            return null;
        }
        
        // Convert to WIB timezone (UTC+7)
        $waktuWIB = $this->waktu_selesai->copy()->setTimezone('Asia/Jakarta');
        
        // Format: 10 Mar 2024 14:30 (short and clear for receipt)
        return $waktuWIB->format('d M Y H:i');
    }

    /**
     * Get formatted created_at with WIB timezone for receipt printing
     */
    public function getCreatedAtFormattedAttribute()
    {
        // Convert to WIB timezone (UTC+7)
        $waktuWIB = $this->created_at->copy()->setTimezone('Asia/Jakarta');
        
        // Format: 10 Mar 2024 14:30 (short and clear for receipt)
        return $waktuWIB->format('d M Y H:i');
    }

    /**
     * Get formatted updated_at with WIB timezone for receipt printing
     */
    public function getUpdatedAtFormattedAttribute()
    {
        // Convert to WIB timezone (UTC+7)
        $waktuWIB = $this->updated_at->copy()->setTimezone('Asia/Jakarta');
        
        // Format: 10 Mar 2024 14:30 (short and clear for receipt)
        return $waktuWIB->format('d M Y H:i');
    }
}
