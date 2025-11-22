<?php

namespace App\Services;

use App\Models\Kalibrasi;
use App\Models\Shift;
use App\Models\Produk;
use App\Models\StokEtalase;
use App\Models\MutasiStok;
use Illuminate\Support\Facades\DB;

class KalibrasiService
{
    public function tambahKalibrasi(
        Shift $shift,
        Produk $produk,
        int $nomorPercobaan,
        float $beratBeansGram,
        bool $terpilih = false,
        ?string $catatan = null
    ) {
        DB::beginTransaction();
        try {
            $kalibrasi = Kalibrasi::create([
                'shift_id' => $shift->id,
                'produk_id' => $produk->id,
                'nomor_percobaan' => $nomorPercobaan,
                'berat_beans_gram' => $beratBeansGram,
                'terpilih' => $terpilih,
                'catatan' => $catatan,
            ]);

            $stokEtalase = StokEtalase::where('cabang_id', $shift->cabang_id)
                ->where('produk_id', $produk->id)
                ->where('tipe_stok', 'produksi_minuman')
                ->firstOrFail();

            $jumlahDalamKg = $beratBeansGram / 1000;

            if ($stokEtalase->jumlah < $jumlahDalamKg) {
                throw new \Exception('Stok tidak mencukupi untuk kalibrasi');
            }

            $jumlahSebelum = $stokEtalase->jumlah;
            $stokEtalase->jumlah -= $jumlahDalamKg;
            $stokEtalase->save();

            MutasiStok::create([
                'stok_etalase_id' => $stokEtalase->id,
                'user_id' => $shift->user_id,
                'shift_id' => $shift->id,
                'tipe' => 'kalibrasi',
                'jumlah_sebelum' => $jumlahSebelum,
                'jumlah_sesudah' => $stokEtalase->jumlah,
                'jumlah_perubahan' => -$jumlahDalamKg,
                'catatan' => "Kalibrasi percobaan #{$nomorPercobaan} - {$beratBeansGram}g",
            ]);

            DB::commit();
            return $kalibrasi;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function pilihKalibrasi(Kalibrasi $kalibrasi)
    {
        DB::beginTransaction();
        try {
            Kalibrasi::where('shift_id', $kalibrasi->shift_id)
                ->where('produk_id', $kalibrasi->produk_id)
                ->update(['terpilih' => false]);

            $kalibrasi->update(['terpilih' => true]);

            DB::commit();
            return $kalibrasi;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function dapatkanKalibrasiTerpilih(Shift $shift, Produk $produk)
    {
        return Kalibrasi::where('shift_id', $shift->id)
            ->where('produk_id', $produk->id)
            ->where('terpilih', true)
            ->first();
    }
}
