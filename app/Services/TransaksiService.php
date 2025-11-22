<?php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Models\Pembayaran;
use App\Models\Shift;
use App\Models\Produk;
use App\Models\StokEtalase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransaksiService
{
    private $stokService;
    private $kalibrasiService;

    public function __construct(StokService $stokService, KalibrasiService $kalibrasiService)
    {
        $this->stokService = $stokService;
        $this->kalibrasiService = $kalibrasiService;
    }

    public function buatTransaksi(
        Shift $shift,
        array $items,
        array $pembayaran,
        float $diskon = 0,
        float $pajak = 0,
        ?string $catatan = null
    ) {
        DB::beginTransaction();
        try {
            $nomorInvoice = $this->generateNomorInvoice($shift->cabang_id);

            $subtotal = 0;
            $itemTransaksi = [];

            foreach ($items as $item) {
                $produk = Produk::findOrFail($item['produk_id']);
                $subtotalItem = $produk->harga_jual * $item['jumlah'];
                $subtotal += $subtotalItem;

                $itemTransaksi[] = [
                    'produk' => $produk,
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $produk->harga_jual,
                    'subtotal' => $subtotalItem,
                    'catatan' => $item['catatan'] ?? null,
                ];
            }

            $total = $subtotal - $diskon + $pajak;

            $totalPembayaran = array_sum(array_column($pembayaran, 'jumlah'));
            if ($totalPembayaran < $total) {
                throw new \Exception('Jumlah pembayaran tidak mencukupi');
            }

            $transaksi = Transaksi::create([
                'shift_id' => $shift->id,
                'cabang_id' => $shift->cabang_id,
                'user_id' => $shift->user_id,
                'nomor_invoice' => $nomorInvoice,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'total' => $total,
                'status' => 'selesai',
                'catatan' => $catatan,
                'waktu_selesai' => Carbon::now(),
            ]);

            foreach ($itemTransaksi as $item) {
                ItemTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $item['produk']->id,
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['subtotal'],
                    'catatan' => $item['catatan'],
                ]);

                if ($item['produk']->tipe === 'minuman') {
                    $this->kurangiStokMinuman($shift, $item['produk'], $item['jumlah']);
                }

                if ($item['produk']->tipe === 'beans') {
                    $stokEtalase = StokEtalase::where('cabang_id', $shift->cabang_id)
                        ->where('produk_id', $item['produk']->id)
                        ->where('tipe_stok', 'penjualan_retail')
                        ->firstOrFail();

                    $jumlahDalamSatuanDasar = $item['jumlah'];
                    $this->stokService->kurangiStok(
                        $stokEtalase,
                        $jumlahDalamSatuanDasar,
                        $shift->user,
                        $shift->id,
                        'keluar',
                        "Transaksi #{$nomorInvoice}"
                    );
                }

                if ($item['produk']->tipe === 'snack') {
                    $stokEtalase = StokEtalase::where('cabang_id', $shift->cabang_id)
                        ->where('produk_id', $item['produk']->id)
                        ->firstOrFail();

                    $this->stokService->kurangiStok(
                        $stokEtalase,
                        $item['jumlah'],
                        $shift->user,
                        $shift->id,
                        'keluar',
                        "Transaksi #{$nomorInvoice}"
                    );
                }
            }

            foreach ($pembayaran as $bayar) {
                Pembayaran::create([
                    'transaksi_id' => $transaksi->id,
                    'metode_pembayaran' => $bayar['metode'],
                    'jumlah' => $bayar['jumlah'],
                    'nomor_referensi' => $bayar['referensi'] ?? null,
                ]);
            }

            DB::commit();
            return $transaksi->load(['item.produk', 'pembayaran']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function kurangiStokMinuman(Shift $shift, Produk $produk, int $jumlah)
    {
        $produkBeans = Produk::where('tipe', 'beans')
            ->where('perlu_kalibrasi', true)
            ->first();

        if (!$produkBeans) {
            return;
        }

        $kalibrasi = $this->kalibrasiService->dapatkanKalibrasiTerpilih($shift, $produkBeans);

        if (!$kalibrasi) {
            throw new \Exception('Belum ada kalibrasi yang dipilih untuk shift ini');
        }

        $totalBeansGram = $kalibrasi->berat_beans_gram * $jumlah;
        $totalBeansKg = $totalBeansGram / 1000;

        $stokEtalase = StokEtalase::where('cabang_id', $shift->cabang_id)
            ->where('produk_id', $produkBeans->id)
            ->where('tipe_stok', 'produksi_minuman')
            ->firstOrFail();

        $this->stokService->kurangiStok(
            $stokEtalase,
            $totalBeansKg,
            $shift->user,
            $shift->id,
            'keluar',
            "Produksi minuman: {$jumlah}x {$produk->nama}"
        );
    }

    private function generateNomorInvoice(int $cabangId): string
    {
        $tanggal = Carbon::now()->format('Ymd');
        $cabang = str_pad($cabangId, 3, '0', STR_PAD_LEFT);

        $transaksiTerakhir = Transaksi::where('nomor_invoice', 'like', "INV-{$tanggal}-{$cabang}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($transaksiTerakhir) {
            $nomorTerakhir = (int) substr($transaksiTerakhir->nomor_invoice, -4);
            $nomorBaru = str_pad($nomorTerakhir + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nomorBaru = '0001';
        }

        return "INV-{$tanggal}-{$cabang}-{$nomorBaru}";
    }

    public function batalkanTransaksi(Transaksi $transaksi, User $user)
    {
        if ($transaksi->status === 'batal') {
            throw new \Exception('Transaksi sudah dibatalkan');
        }

        DB::beginTransaction();
        try {
            $transaksi->update([
                'status' => 'batal',
                'catatan' => ($transaksi->catatan ?? '') . " | Dibatalkan oleh {$user->nama} pada " . Carbon::now(),
            ]);

            foreach ($transaksi->item as $item) {
                $produk = $item->produk;

                if ($produk->tipe === 'minuman') {
                    continue;
                }

                $tipeStok = $produk->tipe === 'beans' ? 'penjualan_retail' : 'produksi_minuman';

                $stokEtalase = StokEtalase::where('cabang_id', $transaksi->cabang_id)
                    ->where('produk_id', $produk->id)
                    ->where('tipe_stok', $tipeStok)
                    ->first();

                if ($stokEtalase) {
                    $jumlahSebelum = $stokEtalase->jumlah;
                    $stokEtalase->jumlah += $item->jumlah;
                    $stokEtalase->save();

                    MutasiStok::create([
                        'stok_etalase_id' => $stokEtalase->id,
                        'user_id' => $user->id,
                        'tipe' => 'masuk',
                        'jumlah_sebelum' => $jumlahSebelum,
                        'jumlah_sesudah' => $stokEtalase->jumlah,
                        'jumlah_perubahan' => $item->jumlah,
                        'catatan' => "Pembatalan transaksi #{$transaksi->nomor_invoice}",
                    ]);
                }
            }

            DB::commit();
            return $transaksi;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
