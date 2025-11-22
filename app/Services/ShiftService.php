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
        $shiftAktif = Shift::where('user_id', $user->id)
            ->where('status', 'buka')
            ->first();

        if ($shiftAktif) {
            throw new \Exception('User sudah memiliki shift yang masih buka');
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'cabang_id' => $cabang->id,
            'saldo_awal' => $saldoAwal,
            'waktu_buka' => Carbon::now(),
            'status' => 'buka',
        ]);

        return $shift;
    }

    public function tutupShift(Shift $shift, float $saldoAkhir, ?string $catatan = null)
    {
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
            ->first();
    }
}
