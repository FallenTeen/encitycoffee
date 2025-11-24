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
        if ($jumlah <= 0) {
            throw new \InvalidArgumentException('Jumlah harus lebih dari 0');
        }
        if ($hargaBeli < 0) {
            throw new \InvalidArgumentException('Harga beli tidak boleh negatif');
        }
        if ($tanggalKadaluarsa && $tanggalKadaluarsa->isBefore(Carbon::today())) {
            throw new \InvalidArgumentException('Tanggal kadaluarsa harus setelah hari ini');
        }
        if (BatchStok::where('nomor_batch', $nomorBatch)->exists()) {
            throw new \Exception('Nomor batch sudah digunakan');
        }

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
        if ($jumlahBaru < 0) {
            throw new \InvalidArgumentException('Jumlah baru tidak boleh negatif');
        }
        if (mb_strlen($catatan) < 10) {
            throw new \InvalidArgumentException('Catatan minimal 10 karakter');
        }

        $jumlahSebelum = $stokEtalase->jumlah;
        $jumlahPerubahan = $jumlahBaru - $jumlahSebelum;
        $persentasePerubahan = $jumlahSebelum > 0
            ? abs($jumlahPerubahan / $jumlahSebelum) * 100
            : ($jumlahBaru > 0 ? 100 : 0);

        if ($persentasePerubahan > 20 && !in_array($user->role, ['it_support', 'manager'])) {
            throw new \Exception('Perubahan > 20% memerlukan approval manager');
        }

        DB::beginTransaction();
        try {
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

    public function sesuaikanStokDenganAlasan(
        StokEtalase $stokEtalase,
        float $jumlahBaru,
        string $alasan,
        string $catatan,
        User $user
    ) {
        $alasanValid = ['stock_opname', 'koreksi_kesalahan', 'kerusakan', 'kehilangan', 'lainnya'];
        if (!in_array($alasan, $alasanValid)) {
            throw new \InvalidArgumentException('Alasan penyesuaian tidak valid');
        }
        $catatanLengkap = "Penyesuaian - {$alasan}: {$catatan}";
        return $this->sesuaikanStok($stokEtalase, $jumlahBaru, $user, $catatanLengkap);
    }

    public function dapatkanStokRendah(Cabang $cabang)
    {
        return StokEtalase::where('cabang_id', $cabang->id)
            ->stokRendah()
            ->with('produk')
            ->get();
    }

    public function stokRendah(Cabang $cabang)
    {
        $stok = StokEtalase::where('cabang_id', $cabang->id)
            ->whereColumn('jumlah', '<=', 'stok_minimum')
            ->with('produk.kategori')
            ->get();

        return $stok->map(function (StokEtalase $s) {
            $persentase = $s->stok_minimum > 0 ? ($s->jumlah / $s->stok_minimum) * 100 : 0;
            $tingkat = $persentase < 25 ? 'kritis' : ($persentase < 50 ? 'rendah' : 'perlu_perhatian');
            return [
                'stok' => $s,
                'persentase' => round($persentase, 2),
                'tingkat_bahaya' => $tingkat,
            ];
        });
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

    public function barangMendekatiKadaluarsa(Cabang $cabang, int $batasHari = 30)
    {
        $batches = BatchStok::whereHas('stokEtalase', function ($query) use ($cabang) {
                $query->where('cabang_id', $cabang->id);
            })
            ->whereNotNull('tanggal_kadaluarsa')
            ->where('jumlah', '>', 0)
            ->whereDate('tanggal_kadaluarsa', '<=', Carbon::now()->addDays($batasHari))
            ->with(['stokEtalase.produk', 'stokEtalase.cabang'])
            ->orderBy('tanggal_kadaluarsa', 'asc')
            ->get();

        return $batches->map(function (BatchStok $b) {
            $hariTersisa = $b->tanggal_kadaluarsa ? $b->tanggal_kadaluarsa->diffInDays(Carbon::now()) : null;
            $nilaiKerugian = (float) $b->jumlah * (float) $b->harga_beli;
            $urgensi = 'rendah';
            if ($hariTersisa !== null) {
                if ($hariTersisa <= 7) $urgensi = 'kritis';
                elseif ($hariTersisa <= 14) $urgensi = 'tinggi';
                elseif ($hariTersisa <= 30) $urgensi = 'sedang';
            }
            return [
                'batch' => $b,
                'hari_tersisa' => $hariTersisa,
                'nilai_kerugian' => round($nilaiKerugian, 2),
                'tingkat_urgensitas' => $urgensi,
            ];
        });
    }

    public function riwayatMutasi(StokEtalase $stokEtalase, array $filter = [])
    {
        $query = MutasiStok::where('stok_etalase_id', $stokEtalase->id)
            ->with(['user', 'shift'])
            ->orderByDesc('created_at');

        if (!empty($filter['tipe'])) {
            $tipe = is_array($filter['tipe']) ? $filter['tipe'] : [$filter['tipe']];
            $query->whereIn('tipe', $tipe);
        }
        if (!empty($filter['tanggal_mulai'])) {
            $query->where('created_at', '>=', Carbon::parse($filter['tanggal_mulai']));
        }
        if (!empty($filter['tanggal_selesai'])) {
            $query->where('created_at', '<=', Carbon::parse($filter['tanggal_selesai']));
        }
        if (!empty($filter['user_id'])) {
            $query->where('user_id', (int) $filter['user_id']);
        }

        return $query->paginate(50);
    }
}
