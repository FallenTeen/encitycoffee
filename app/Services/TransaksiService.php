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
    private ReceiptDiscountService $receiptDiscountService;

    public function __construct(StokService $stokService, KalibrasiService $kalibrasiService, ReceiptDiscountService $receiptDiscountService)
    {
        $this->stokService = $stokService;
        $this->kalibrasiService = $kalibrasiService;
        $this->receiptDiscountService = $receiptDiscountService;
    }

    public function buatTransaksi(
        Shift $shift,
        array $items,
        array $pembayaran,
        ?float $diskonNominal = null,
        ?float $diskonPersen = null,
        array $pembulatan = [],
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
        if (empty($pembayaran)) {
            throw new \InvalidArgumentException('Pembayaran tidak boleh kosong');
        }
        return DB::transaction(function () use ($shift, $items, $pembayaran, $diskonNominal, $diskonPersen, $pembulatan, $pajak, $catatan, $namaPelanggan) {
            return $this->buatTransaksiTanpaTransaksi($shift, $items, $pembayaran, $diskonNominal, $diskonPersen, $pembulatan, $pajak, $catatan, $namaPelanggan);
        });
    }

    private function buatTransaksiTanpaTransaksi(
        Shift $shift,
        array $items,
        array $pembayaran,
        ?float $diskonNominal = null,
        ?float $diskonPersen = null,
        array $pembulatan = [],
        float $pajak = 0,
        ?string $catatan = null,
        ?string $namaPelanggan = null
    ) {
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

        $discountInput = [
            'total_awal' => $subtotal,
            'pajak' => $pajak,
        ];
        if ($diskonNominal !== null) {
            $discountInput['diskon_nominal'] = $diskonNominal;
        }
        if ($diskonPersen !== null) {
            $discountInput['diskon_persen'] = $diskonPersen;
        }
        if (! array_key_exists('diskon_nominal', $discountInput) && ! array_key_exists('diskon_persen', $discountInput)) {
            $discountInput['diskon_nominal'] = 0;
        }
        if (! empty($pembulatan)) {
            $discountInput['pembulatan'] = $pembulatan;
        }

        $calc = $this->receiptDiscountService->preview($discountInput);

        $diskonFinal = (float) $calc['diskon_nominal'];
        $diskonPersenFinal = $calc['diskon_persen'] !== null ? (float) $calc['diskon_persen'] : null;
        $total = (float) $calc['total_akhir'];

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
            'diskon' => $diskonFinal,
            'diskon_persen' => $diskonPersenFinal,
            'diskon_rounding_mode' => $calc['pembulatan']['mode'] ?? null,
            'diskon_rounding_unit' => $calc['pembulatan']['unit'] ?? null,
            'diskon_rounding_delta' => $calc['pembulatan']['diskon_delta'] ?? 0,
            'pajak' => $pajak,
            'total' => $total,
            'status' => 'selesai',
            'catatan' => $catatan,
            'waktu_selesai' => Carbon::now(),
        ]);

        $this->auditDiscountIfNeeded('Transaksi', (int) $transaksi->id, [
            'subtotal' => $subtotal,
            'pajak' => $pajak,
            'diskon_nominal' => $calc['diskon_nominal'],
            'diskon_persen' => $calc['diskon_persen'],
            'total_akhir' => $calc['total_akhir'],
            'pembulatan' => $calc['pembulatan'],
        ]);

        foreach ($itemTransaksi as $item) {
            $potonganBundling = 0;
            if ($item['produk']->tipe === 'bundling') {
                $item['produk']->loadMissing('bundleItems.produk');
                $realTotal = 0;
                foreach ($item['produk']->bundleItems as $bundleItem) {
                    if ($bundleItem->produk) {
                        $realTotal += $bundleItem->produk->harga_jual * $bundleItem->jumlah;
                    }
                }
                $potonganBundling = ($realTotal - $item['produk']->harga_jual) * $item['jumlah'];
            }

            ItemTransaksi::create([
                'transaksi_id' => $transaksi->id,
                'produk_id' => $item['produk']->id,
                'jumlah' => $item['jumlah'],
                'harga_satuan' => $item['harga_satuan'],
                'subtotal' => $item['subtotal'],
                'potongan_bundling' => max(0, $potonganBundling),
                'catatan' => $item['catatan'],
            ]);
            /* TODO: Temporarily disable stock deduction as requested
            if ($item['produk']->tipe === 'minuman') {
                $this->kurangiStokMinuman($shift, $item['produk'], $item['jumlah']);
            }
            if ($item['produk']->tipe === 'beans') {
                $stokEtalase = StokEtalase::where('cabang_id', $shift->cabang_id)
                    ->where('produk_id', $item['produk']->id)
                    ->where('tipe_stok', 'penjualan_retail')
                    ->first();
                if (! $stokEtalase) {
                    continue;
                }
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
            if (in_array($item['produk']->tipe, ['snack', 'makanan'])) {
                $stokEtalase = StokEtalase::where('cabang_id', $shift->cabang_id)
                    ->where('produk_id', $item['produk']->id)
                    ->where('tipe_stok', 'produksi_minuman')
                    ->first();
                if (! $stokEtalase) {
                    continue;
                }
                $this->stokService->kurangiStok(
                    $stokEtalase,
                    $item['jumlah'],
                    $shift->user,
                    $shift->id,
                    'keluar',
                    "Transaksi #{$nomorInvoice}"
                );
            }
            */
        }
        foreach ($pembayaran as $bayar) {
            Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'metode_pembayaran' => $bayar['metode'],
                'jumlah' => $bayar['jumlah'],
                'nomor_referensi' => $bayar['referensi'] ?? null,
            ]);
        }
        return $transaksi->load(['item.produk', 'pembayaran']);
    }

    public function buatOpenBill(
        Shift $shift,
        array $items,
        ?float $diskonNominal = null,
        ?float $diskonPersen = null,
        array $pembulatan = [],
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

            $discountInput = [
                'total_awal' => $subtotal,
                'pajak' => $pajak,
            ];
            if ($diskonNominal !== null) {
                $discountInput['diskon_nominal'] = $diskonNominal;
            }
            if ($diskonPersen !== null) {
                $discountInput['diskon_persen'] = $diskonPersen;
            }
            if (! array_key_exists('diskon_nominal', $discountInput) && ! array_key_exists('diskon_persen', $discountInput)) {
                $discountInput['diskon_nominal'] = 0;
            }
            if (! empty($pembulatan)) {
                $discountInput['pembulatan'] = $pembulatan;
            }

            $calc = $this->receiptDiscountService->preview($discountInput);
            $diskonFinal = (float) $calc['diskon_nominal'];
            $diskonPersenFinal = $calc['diskon_persen'] !== null ? (float) $calc['diskon_persen'] : null;
            $total = (float) $calc['total_akhir'];

            $openBill = OpenBill::create([
                'shift_id' => $shift->id,
                'cabang_id' => $shift->cabang_id,
                'user_id' => $shift->user_id,
                'nama_pelanggan' => $namaPelanggan,
                'nomor_open_bill' => $nomorOpenBill,
                'subtotal' => $subtotal,
                'diskon' => $diskonFinal,
                'diskon_persen' => $diskonPersenFinal,
                'diskon_rounding_mode' => $calc['pembulatan']['mode'] ?? null,
                'diskon_rounding_unit' => $calc['pembulatan']['unit'] ?? null,
                'diskon_rounding_delta' => $calc['pembulatan']['diskon_delta'] ?? 0,
                'pajak' => $pajak,
                'total' => $total,
                'status' => 'open',
                'catatan' => $catatan,
            ]);

            $this->auditDiscountIfNeeded('OpenBill', (int) $openBill->id, [
                'subtotal' => $subtotal,
                'pajak' => $pajak,
                'diskon_nominal' => $calc['diskon_nominal'],
                'diskon_persen' => $calc['diskon_persen'],
                'total_akhir' => $calc['total_akhir'],
                'pembulatan' => $calc['pembulatan'],
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
        ?float $diskonNominal = null,
        ?float $diskonPersen = null,
        array $pembulatan = [],
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

            $discountInput = [
                'total_awal' => $subtotal,
                'pajak' => $pajak,
            ];
            if ($diskonNominal !== null) {
                $discountInput['diskon_nominal'] = $diskonNominal;
            }
            if ($diskonPersen !== null) {
                $discountInput['diskon_persen'] = $diskonPersen;
            }
            if (! array_key_exists('diskon_nominal', $discountInput) && ! array_key_exists('diskon_persen', $discountInput)) {
                $discountInput['diskon_nominal'] = 0;
            }
            if (! empty($pembulatan)) {
                $discountInput['pembulatan'] = $pembulatan;
            }

            $calc = $this->receiptDiscountService->preview($discountInput);
            $diskonFinal = (float) $calc['diskon_nominal'];
            $diskonPersenFinal = $calc['diskon_persen'] !== null ? (float) $calc['diskon_persen'] : null;
            $total = (float) $calc['total_akhir'];

            $openBill->update([
                'subtotal' => $subtotal,
                'diskon' => $diskonFinal,
                'diskon_persen' => $diskonPersenFinal,
                'diskon_rounding_mode' => $calc['pembulatan']['mode'] ?? null,
                'diskon_rounding_unit' => $calc['pembulatan']['unit'] ?? null,
                'diskon_rounding_delta' => $calc['pembulatan']['diskon_delta'] ?? 0,
                'pajak' => $pajak,
                'total' => $total,
                'status' => 'open',
                'catatan' => $catatan,
                'nama_pelanggan' => $namaPelanggan,
            ]);

            $this->auditDiscountIfNeeded('OpenBill', (int) $openBill->id, [
                'subtotal' => $subtotal,
                'pajak' => $pajak,
                'diskon_nominal' => $calc['diskon_nominal'],
                'diskon_persen' => $calc['diskon_persen'],
                'total_akhir' => $calc['total_akhir'],
                'pembulatan' => $calc['pembulatan'],
            ]);
            $openBill->addAuditLog('content_update', [
                'subtotal' => $subtotal,
                'diskon' => $diskonFinal,
                'diskon_persen' => $diskonPersenFinal,
                'diskon_rounding_mode' => $calc['pembulatan']['mode'] ?? null,
                'diskon_rounding_unit' => $calc['pembulatan']['unit'] ?? null,
                'diskon_rounding_delta' => $calc['pembulatan']['diskon_delta'] ?? 0,
                'pajak' => $pajak,
                'total' => $total,
                'catatan' => $catatan,
                'nama_pelanggan' => $namaPelanggan,
                'items_count' => count($itemOpenBill),
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

    private function auditDiscountIfNeeded(string $subjectType, int $subjectId, array $payload): void
    {
        $diskonNominal = isset($payload['diskon_nominal']) ? (float) $payload['diskon_nominal'] : 0.0;
        $diskonPersen = isset($payload['diskon_persen']) ? (float) $payload['diskon_persen'] : 0.0;
        $pembulatan = is_array(($payload['pembulatan'] ?? null)) ? $payload['pembulatan'] : [];
        $applied = (bool) ($pembulatan['applied'] ?? false);
        if ($diskonNominal <= 0.0 && $diskonPersen <= 0.0 && ! $applied) {
            return;
        }

        $actorId = auth()->id();
        if (! $actorId) {
            return;
        }

        try {
            DB::table('audit_logs')->insert([
                'actor_user_id' => (int) $actorId,
                'method' => 'DISCOUNT',
                'path' => 'pos/discount',
                'ip' => null,
                'user_agent' => null,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'payload' => json_encode($payload),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
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
            ->first();
        if (! $stokEtalase) {
            return;
        }

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
                /* TODO: Temporarily disable stock return as requested
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
                */
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
        ?float $diskonNominal = null,
        ?float $diskonPersen = null,
        array $pembulatan = [],
        ?float $pajak = null,
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

        return DB::transaction(function () use ($openBill, $shift, $pembayaran, $diskonNominal, $diskonPersen, $pembulatan, $pajak, $catatan) {
            $items = $openBill->items()->with('produk')->get()->map(function ($item) {
                return [
                    'produk_id' => $item->produk_id,
                    'jumlah' => $item->jumlah,
                    'catatan' => $item->catatan,
                ];
            })->toArray();

            $diskonNominalFinal = $diskonNominal ?? ($openBill->diskon !== null ? (float) $openBill->diskon : null);
            $diskonPersenFinal = $diskonPersen ?? ($openBill->diskon_persen !== null ? (float) $openBill->diskon_persen : null);
            $pembulatanFinal = ! empty($pembulatan) ? $pembulatan : [
                'mode' => $openBill->diskon_rounding_mode,
                'unit' => $openBill->diskon_rounding_unit,
            ];
            $pajakFinal = $pajak ?? ($openBill->pajak !== null ? (float) $openBill->pajak : 0);

            $transaksi = $this->buatTransaksiTanpaTransaksi(
                $shift,
                $items,
                $pembayaran,
                $diskonNominalFinal,
                $diskonPersenFinal,
                $pembulatanFinal,
                $pajakFinal,
                $catatan ?? $openBill->catatan,
                $openBill->nama_pelanggan
            );
            $openBill->update(['status' => 'closed']);
            $openBill->addAuditLog('status_update', [
                'from' => 'open',
                'to' => 'closed',
                'transaksi_id' => $transaksi->id ?? null,
                'diskon' => $diskonNominalFinal,
                'diskon_persen' => $diskonPersenFinal,
                'pembulatan' => $pembulatanFinal,
                'pajak' => $pajakFinal,
                'pembayaran' => $pembayaran,
            ]);
            return $transaksi;
        });
    }
}
