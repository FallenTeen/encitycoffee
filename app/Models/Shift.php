<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'shift';

    protected $fillable = [
        'user_id',
        'cabang_id',
        'saldo_awal',
        'saldo_akhir',
        'saldo_diharapkan',
        'selisih',
        'total_tunai',
        'total_qris',
        'waktu_buka',
        'waktu_tutup',
        'status',
        'catatan',
        'audit_log',
    ];

    protected $casts = [
        'saldo_awal' => 'decimal:2',
        'saldo_akhir' => 'decimal:2',
        'saldo_diharapkan' => 'decimal:2',
        'selisih' => 'decimal:2',
        'total_tunai' => 'decimal:2',
        'total_qris' => 'decimal:2',
        'waktu_buka' => 'datetime',
        'waktu_tutup' => 'datetime',
        'audit_log' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function kalibrasi()
    {
        return $this->hasMany(Kalibrasi::class);
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function mutasiStok()
    {
        return $this->hasMany(MutasiStok::class);
    }

    public function scopeBuka($query)
    {
        return $query->where('status', 'buka');
    }

    public function scopeTutup($query)
    {
        return $query->where('status', 'tutup');
    }

    public function getSedangBukaAttribute()
    {
        return $this->status === 'buka';
    }

    public function getTotalTransaksiAttribute()
    {
        return $this->transaksi()->where('status', 'selesai')->sum('total');
    }

    public function addAuditLog(string $action, array $data = [])
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

    public function validateSaldo()
    {
        if (!is_numeric($this->saldo_awal) || $this->saldo_awal < 0) {
            throw new \InvalidArgumentException('Saldo awal harus berupa angka positif');
        }

        if ($this->saldo_akhir !== null && (!is_numeric($this->saldo_akhir) || $this->saldo_akhir < 0)) {
            throw new \InvalidArgumentException('Saldo akhir harus berupa angka positif atau null');
        }

        return true;
    }

    public function safeUpdate(array $data)
    {
        try {
            $this->validateSaldo();
            $this->update($data);
            $this->addAuditLog('update', $data);
            return true;
        } catch (\Exception $e) {
            Log::error('Shift safeUpdate failed', [
                'shift_id' => $this->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shift) {
            $shift->validateSaldo();
            Log::info('Creating shift', [
                'user_id' => $shift->user_id,
                'cabang_id' => $shift->cabang_id,
                'saldo_awal' => $shift->saldo_awal
            ]);
        });

        static::updating(function ($shift) {
            $shift->validateSaldo();
            Log::info('Updating shift', [
                'shift_id' => $shift->id,
                'changes' => $shift->getDirty()
            ]);
        });
    }
}
