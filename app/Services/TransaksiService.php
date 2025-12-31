<?php

namespace App\Services;

use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Models\Pembayaran;
use App\Models\Shift;
use App\Models\Produk;
use App\Models\StokEtalase;
use App\Models\MutasiStok;
use App\Models\User;
use App\Models\OpenBill;
use App\Models\OpenBillItem;
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
        ?string $catatan = null,
        ?string $namaPelanggan = null
    ) {
        // Validasi awal sesuai spesifikasi
        if ($shift->status !== 'buka') {
            throw new \Exception('Shift tidak dalam status buka');
        }
        if (empty($items)) {
            throw new \InvalidArgumentException('Items tidak boleh kosong');
        }
        if (empty($pembayaran)) {
            throw new \InvalidArgumentException('Pembayaran tidak boleh kosong');
        }
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
                'nama_pelanggan' => $namaPelanggan,
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
                        ->where('tipe_stok', 'produksi_minuman')
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

    public function buatOpenBill(
        Shift $shift,
        array $items,
        float $diskon = 0,
        float $pajak = 0,
        ?string $catatan = null,
        ?string $namaPelanggan = null
    ) {
        if ($shift->status !== 'buka') {
            throw new \Exception('Shift tidak dalam status buka');
        }
        if (empty($items)) {
            throw new \InvalidArgumentException('Items tidak boleh kosong');
        }

        DB::beginTransaction();
        try {
            $nomorOpenBill = $this->generateNomorOpenBill($shift->cabang_id);

            $subtotal = 0;
            $itemOpenBill = [];

            foreach ($items as $item) {
                $produk = Produk::findOrFail($item['produk_id']);
                $subtotalItem = $produk->harga_jual * $item['jumlah'];
                $subtotal += $subtotalItem;

                $itemOpenBill[] = [
                    'produk' => $produk,
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $produk->harga_jual,
                    'subtotal' => $subtotalItem,
                    'catatan' => $item['catatan'] ?? null,
                ];
            }

            $total = $subtotal - $diskon + $pajak;

            $openBill = OpenBill::create([
                'shift_id' => $shift->id,
                'cabang_id' => $shift->cabang_id,
                'user_id' => $shift->user_id,
                'nama_pelanggan' => $namaPelanggan,
                'nomor_open_bill' => $nomorOpenBill,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'total' => $total,
                'status' => 'open',
                'catatan' => $catatan,
            ]);

            foreach ($itemOpenBill as $item) {
                OpenBillItem::create([
                    'open_bill_id' => $openBill->id,
                    'produk_id' => $item['produk']->id,
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['subtotal'],
                    'catatan' => $item['catatan'],
                ]);
            }

            DB::commit();
            return $openBill->load(['items.produk', 'shift', 'cabang']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateOpenBill(
        OpenBill $openBill,
        array $items,
        float $diskon = 0,
        float $pajak = 0,
        ?string $catatan = null,
        ?string $namaPelanggan = null
    ) {
        if ($openBill->status !== 'open') {
            throw new \Exception('Open bill sudah tidak aktif');
        }

        $shift = $openBill->shift;
        if (! $shift || $shift->status !== 'buka') {
            throw new \Exception('Shift tidak dalam status buka');
        }
        if (empty($items)) {
            throw new \InvalidArgumentException('Items tidak boleh kosong');
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $itemOpenBill = [];

            foreach ($items as $item) {
                $produk = Produk::findOrFail($item['produk_id']);
                $subtotalItem = $produk->harga_jual * $item['jumlah'];
                $subtotal += $subtotalItem;

                $itemOpenBill[] = [
                    'produk' => $produk,
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $produk->harga_jual,
                    'subtotal' => $subtotalItem,
                    'catatan' => $item['catatan'] ?? null,
                ];
            }

            $total = $subtotal - $diskon + $pajak;

            $openBill->update([
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'total' => $total,
                'status' => 'open',
                'catatan' => $catatan,
                'nama_pelanggan' => $namaPelanggan,
            ]);

            $openBill->items()->delete();

            foreach ($itemOpenBill as $item) {
                OpenBillItem::create([
                    'open_bill_id' => $openBill->id,
                    'produk_id' => $item['produk']->id,
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['subtotal'],
                    'catatan' => $item['catatan'],
                ]);
            }

            DB::commit();
            return $openBill->load(['items.produk', 'shift', 'cabang']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function kurangiStokMinuman(Shift $shift, Produk $produk, int $jumlah)
    {
        $kalibrasi = $this->kalibrasiService->dapatkanKalibrasiTerpilih($shift, $produk);
        if (! $kalibrasi) {
            return;
        }

        $totalBeansGram = $kalibrasi->berat_beans_gram * $jumlah;
        $totalBeansKg = $totalBeansGram / 1000;

        $stokEtalase = StokEtalase::where('cabang_id', $shift->cabang_id)
            ->where('produk_id', $produk->id)
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

    private function generateNomorOpenBill(int $cabangId): string
    {
        $tanggal = Carbon::now()->format('Ymd');
        $cabang = str_pad($cabangId, 3, '0', STR_PAD_LEFT);

        $openBillTerakhir = OpenBill::where('nomor_open_bill', 'like', "OB-{$tanggal}-{$cabang}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($openBillTerakhir) {
            $nomorTerakhir = (int) substr($openBillTerakhir->nomor_open_bill, -4);
            $nomorBaru = str_pad($nomorTerakhir + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nomorBaru = '0001';
        }

        return "OB-{$tanggal}-{$cabang}-{$nomorBaru}";
    }

    public function batalkanTransaksi(Transaksi $transaksi, User $user, ?string $alasan = null)
    {
        // Validasi status transaksi dan otorisasi user
        if ($transaksi->status === 'batal') {
            throw new \Exception('Transaksi sudah dibatalkan');
        }
        if ($transaksi->status !== 'selesai') {
            throw new \Exception('Hanya transaksi selesai yang dapat dibatalkan');
        }
        $transaksi->loadMissing('shift');
        if (($transaksi->shift->status ?? null) !== 'buka') {
            throw new \Exception('Shift tidak dalam status buka');
        }
        if (!in_array($user->role, ['manager', 'it_support'])) {
            throw new \Exception('Tidak berwenang membatalkan transaksi');
        }

        DB::beginTransaction();
        try {
            $transaksi->update([
                'status' => 'batal',
                'catatan' => trim(($transaksi->catatan ?? '') . ' | ' .
                    'Dibatalkan oleh ' . ($user->name ?? $user->nama ?? 'User') .
                    ' pada ' . Carbon::now()->toDateTimeString() .
                    ($alasan ? (' | Alasan: ' . $alasan) : '')),
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
                        'shift_id' => $transaksi->shift_id,
                        'tipe' => 'masuk',
                        'jumlah_sebelum' => $jumlahSebelum,
                        'jumlah_sesudah' => $stokEtalase->jumlah,
                        'jumlah_perubahan' => $item->jumlah,
                        'catatan' => "Return pembatalan transaksi #{$transaksi->nomor_invoice}",
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

    public function transaksiPerShift(Shift $shift, array $filter = [], int $perPage = 20)
    {
        $query = Transaksi::where('shift_id', $shift->id)
            ->with(['item.produk', 'pembayaran'])
            ->orderByDesc('created_at');

        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        if (!empty($filter['metode'])) {
            $query->whereHas('pembayaran', function ($q) use ($filter) {
                $q->where('metode_pembayaran', $filter['metode']);
            });
        }

        return $query->paginate($perPage);
    }

    public function convertOpenBillToTransaksi(
        OpenBill $openBill,
        array $pembayaran,
        float $diskon = 0,
        float $pajak = 0,
        ?string $catatan = null
    ) {
        // Validasi awal
        $shift = $openBill->shift;
        if ($shift->status !== 'buka') {
            throw new \Exception('Shift tidak dalam status buka');
        }
        if ($openBill->status !== 'open') {
            throw new \Exception('Open bill sudah tidak aktif');
        }
        if (empty($pembayaran)) {
            throw new \InvalidArgumentException('Pembayaran tidak boleh kosong');
        }

        DB::beginTransaction();
        try {
            // Ambil semua item dari open bill
            $items = $openBill->items()->with('produk')->get()->map(function ($item) {
                return [
                    'produk_id' => $item->produk_id,
                    'jumlah' => $item->jumlah,
                    'catatan' => $item->catatan,
                ];
            })->toArray();

            // Buat transaksi baru menggunakan method existing
            $transaksi = $this->buatTransaksi(
                $shift,
                $items,
                $pembayaran,
                $diskon,
                $pajak,
                $catatan ?? $openBill->catatan,
                $openBill->nama_pelanggan
            );

            // Update status open bill menjadi closed
            $openBill->update(['status' => 'closed']);

            DB::commit();
            return $transaksi;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
