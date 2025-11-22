<?php

namespace App\Services;

use App\Models\StokEtalase;
use App\Models\BatchStok;
use App\Models\MutasiStok;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StokService
{
    public function tambahStok(
        Cabang $cabang,
        Produk $produk,
        string $tipeStok,
        float $jumlah,
        string $nomorBatch,
        ?Carbon $tanggalKadaluarsa = null,
        float $hargaBeli = 0,
        ?User $user = null
    ) {
        DB::beginTransaction();
        try {
            $stokEtalase = StokEtalase::firstOrCreate(
                [
                    'cabang_id' => $cabang->id,
                    'produk_id' => $produk->id,
                    'tipe_stok' => $tipeStok,
                ],
                [
                    'jumlah' => 0,
                    'stok_minimum' => 0,
                ]
            );

            $jumlahSebelum = $stokEtalase->jumlah;
            $stokEtalase->jumlah += $jumlah;
            $stokEtalase->save();

            BatchStok::create([
                'stok_etalase_id' => $stokEtalase->id,
                'nomor_batch' => $nomorBatch,
                'jumlah' => $jumlah,
                'tanggal_kadaluarsa' => $tanggalKadaluarsa,
                'harga_beli' => $hargaBeli,
                'waktu_terima' => Carbon::now(),
            ]);

            MutasiStok::create([
                'stok_etalase_id' => $stokEtalase->id,
                'user_id' => $user?->id,
                'tipe' => 'masuk',
                'jumlah_sebelum' => $jumlahSebelum,
                'jumlah_sesudah' => $stokEtalase->jumlah,
                'jumlah_perubahan' => $jumlah,
                'catatan' => "Stok masuk - Batch: {$nomorBatch}",
            ]);

            DB::commit();
            return $stokEtalase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function kurangiStok(
        StokEtalase $stokEtalase,
        float $jumlah,
        User $user,
        ?int $shiftId = null,
        string $tipe = 'keluar',
        ?string $catatan = null
    ) {
        DB::beginTransaction();
        try {
            if ($stokEtalase->jumlah < $jumlah) {
                throw new \Exception('Stok tidak mencukupi');
            }

            $jumlahSebelum = $stokEtalase->jumlah;
            $stokEtalase->jumlah -= $jumlah;
            $stokEtalase->save();

            MutasiStok::create([
                'stok_etalase_id' => $stokEtalase->id,
                'user_id' => $user->id,
                'shift_id' => $shiftId,
                'tipe' => $tipe,
                'jumlah_sebelum' => $jumlahSebelum,
                'jumlah_sesudah' => $stokEtalase->jumlah,
                'jumlah_perubahan' => -$jumlah,
                'catatan' => $catatan,
            ]);

            DB::commit();
            return $stokEtalase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function sesuaikanStok(
        StokEtalase $stokEtalase,
        float $jumlahBaru,
        User $user,
        string $catatan
    ) {
        DB::beginTransaction();
        try {
            $jumlahSebelum = $stokEtalase->jumlah;
            $jumlahPerubahan = $jumlahBaru - $jumlahSebelum;

            $stokEtalase->jumlah = $jumlahBaru;
            $stokEtalase->save();

            MutasiStok::create([
                'stok_etalase_id' => $stokEtalase->id,
                'user_id' => $user->id,
                'tipe' => 'penyesuaian',
                'jumlah_sebelum' => $jumlahSebelum,
                'jumlah_sesudah' => $jumlahBaru,
                'jumlah_perubahan' => $jumlahPerubahan,
                'catatan' => $catatan,
            ]);

            DB::commit();
            return $stokEtalase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function dapatkanStokRendah(Cabang $cabang)
    {
        return StokEtalase::where('cabang_id', $cabang->id)
            ->stokRendah()
            ->with('produk')
            ->get();
    }

    public function dapatkanBarangMendekatiKadaluarsa(Cabang $cabang, int $hari = 30)
    {
        return BatchStok::whereHas('stokEtalase', function ($query) use ($cabang) {
                $query->where('cabang_id', $cabang->id);
            })
            ->mendekatiKadaluarsa($hari)
            ->with('stokEtalase.produk')
            ->get();
    }
}
