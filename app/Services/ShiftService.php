<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Models\Cabang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function bukaShift(User $user, Cabang $cabang, float $saldoAwal)
    {
        // Cek shift aktif
        $shiftAktif = Shift::where('user_id', $user->id)
            ->where('status', 'buka')
            ->first();
        if ($shiftAktif) {
            throw new \Exception('User sudah memiliki shift aktif');
        }

        // Cek akses cabang
        $punyaAkses = $user->cabang()->where('cabang.id', $cabang->id)->exists();
        if (!$punyaAkses) {
            throw new \Exception('Tidak memiliki akses ke cabang ini');
        }

        // Validasi saldo awal
        if ($saldoAwal < 0) {
            throw new \InvalidArgumentException('Saldo awal tidak boleh negatif');
        }

        DB::beginTransaction();
        try {
            $shift = Shift::create([
                'user_id' => $user->id,
                'cabang_id' => $cabang->id,
                'saldo_awal' => $saldoAwal,
                'waktu_buka' => Carbon::now(),
                'status' => 'buka',
            ]);
            $shift->addAuditLog('open', [
                'saldo_awal' => $saldoAwal,
                'cabang_id' => $cabang->id,
                'user_id' => $user->id,
            ]);

            DB::commit();
            return $shift;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function tutupShift(Shift $shift, float $saldoAkhir, ?string $catatan = null)
    {
        // Validasi status shift dan saldo akhir
        if ($shift->status !== 'buka') {
            throw new \Exception('Shift tidak dalam status buka');
        }
        if ($saldoAkhir < 0) {
            throw new \InvalidArgumentException('Saldo akhir tidak boleh negatif');
        }

        DB::beginTransaction();
        try {
            $transaksi = $shift->transaksi()->where('status', 'selesai')->get();

            $totalTunai = $transaksi->sum(function ($trans) {
                return $trans->pembayaran()
                    ->where('metode_pembayaran', 'tunai')
                    ->sum('jumlah');
            });

            $totalQris = $transaksi->sum(function ($trans) {
                return $trans->pembayaran()
                    ->where('metode_pembayaran', 'qris')
                    ->sum('jumlah');
            });

            $saldoDiharapkan = $shift->saldo_awal + $totalTunai;
            $selisih = $saldoAkhir - $saldoDiharapkan;

            $shift->update([
                'saldo_akhir' => $saldoAkhir,
                'saldo_diharapkan' => $saldoDiharapkan,
                'selisih' => $selisih,
                'total_tunai' => $totalTunai,
                'total_qris' => $totalQris,
                'waktu_tutup' => Carbon::now(),
                'status' => 'tutup',
                'catatan' => $catatan,
            ]);
            $shift->addAuditLog('close', [
                'saldo_awal' => $shift->saldo_awal,
                'saldo_akhir' => $saldoAkhir,
                'saldo_diharapkan' => $saldoDiharapkan,
                'selisih' => $selisih,
                'total_tunai' => $totalTunai,
                'total_qris' => $totalQris,
                'catatan' => $catatan,
            ]);

            DB::commit();
            return $shift;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function dapatkanShiftAktif(User $user)
    {
        return Shift::where('user_id', $user->id)
            ->where('status', 'buka')
            ->with(['cabang', 'user', 'kalibrasi.produk'])
            ->first();
    }

    /**
     * Daftar shift dengan filter, urutan waktu_buka DESC, pagination 20.
     */
    public function daftarShift(array $filter = [], int $perPage = 20)
    {
        $query = Shift::query()->with(['user', 'cabang'])->orderBy('waktu_buka', 'desc');

        if (!empty($filter['user_id'])) {
            $query->where('user_id', $filter['user_id']);
        }
        if (!empty($filter['cabang_id'])) {
            $query->where('cabang_id', $filter['cabang_id']);
        }
        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }
        if (!empty($filter['tanggal'])) {
            $query->whereDate('waktu_buka', $filter['tanggal']);
        }

        return $query->paginate($perPage);
    }
}
