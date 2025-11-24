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
    /**
     * Simpan data kalibrasi berdasarkan spesifikasi.
     * Validasi: shift aktif (status 'buka'), produk perlu_kalibrasi, parameter wajib.
     * Jika berat beans > 0, kurangi stok beans (tipe_stok: produksi_minuman) dan catat mutasi.
     */
    public function simpanKalibrasi(array $data): Kalibrasi
    {
        // Validasi minimal field
        foreach (['shift_id', 'produk_id', 'nomor_percobaan'] as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("Field {$field} wajib diisi");
            }
        }

        $beratBeansGram = (float) ($data['berat_beans_gram'] ?? 0);
        $terpilih = (bool) ($data['terpilih'] ?? false);
        $catatan = $data['catatan'] ?? null;

        $shift = Shift::where('id', $data['shift_id'])->where('status', 'buka')->first();
        if (!$shift) {
            throw new \Exception('Shift tidak aktif atau tidak ditemukan');
        }

        $produk = Produk::where('id', $data['produk_id'])->where('perlu_kalibrasi', true)->first();
        if (!$produk) {
            throw new \Exception('Produk tidak valid atau tidak memerlukan kalibrasi');
        }

        return $this->tambahKalibrasi(
            $shift,
            $produk,
            (int) $data['nomor_percobaan'],
            $beratBeansGram,
            $terpilih,
            $catatan
        );
    }
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

    /**
     * Versi memilih kalibrasi berdasarkan ID.
     */
    public function pilihKalibrasiById(int $kalibrasiId): Kalibrasi
    {
        $kalibrasi = Kalibrasi::findOrFail($kalibrasiId);
        return $this->pilihKalibrasi($kalibrasi);
    }

    public function dapatkanKalibrasiTerpilih(Shift $shift, Produk $produk)
    {
        return Kalibrasi::where('shift_id', $shift->id)
            ->where('produk_id', $produk->id)
            ->where('terpilih', true)
            ->first();
    }

    /**
     * Ambil semua kalibrasi dalam satu shift (diurutkan ASC) beserta relasi produk.
     */
    public function ambilKalibrasiPerShift(int $shiftId)
    {
        return Kalibrasi::where('shift_id', $shiftId)
            ->with('produk')
            ->orderBy('nomor_percobaan', 'asc')
            ->get();
    }
}
