<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpenBill extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'open_bills';

    protected $fillable = [
        'shift_id',
        'cabang_id',
        'user_id',
        'nama_pelanggan',
        'nomor_open_bill',
        'subtotal',
        'diskon',
        'diskon_persen',
        'diskon_rounding_mode',
        'diskon_rounding_unit',
        'diskon_rounding_delta',
        'pajak',
        'total',
        'status',
        'catatan',
        'audit_log',
        'deleted_by',
        'delete_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'diskon_persen' => 'decimal:4',
        'diskon_rounding_unit' => 'integer',
        'diskon_rounding_delta' => 'decimal:2',
        'pajak' => 'decimal:2',
        'total' => 'decimal:2',
        'audit_log' => 'array',
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

    public function items()
    {
        return $this->hasMany(OpenBillItem::class);
    }

    public function addAuditLog(string $action, array $data = []): void
    {
        $log = $this->audit_log ?? [];
        $log[] = [
            'action' => $action,
            'user_id' => auth()->id(),
            'timestamp' => now()->toDateTimeString(),
            'data' => $data,
        ];
        $this->update(['audit_log' => $log]);
    }
}
