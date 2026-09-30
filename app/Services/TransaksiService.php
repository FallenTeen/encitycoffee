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
use App\Policies\BranchAccessPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        ?string $namaPelanggan = null,
        ?string $clientTransactionId = null,
        ?User $actor = null
    ) {
        if (empty($items)) {
            abort(422, 'Items tidak boleh kosong');
        }
        if (empty($pembayaran)) {
            abort(422, 'Pembayaran tidak boleh kosong');
        }

        // === IDEMPOTENCY CHECK ===
        // Scope the lookup to this caller and this shift. Filtering on
        // client_transaction_id alone would hand back another cashier's entire
        // sale (items, payments, customer name) to anyone who reused the UUID.
        //
        // This runs before the shift is validated on purpose: a retry that arrives
        // after the shift was closed must still replay the original sale instead of
        // failing on a shift that no longer accepts transactions.
        if ($clientTransactionId) {
            $existing = $this->cariTransaksiIdempoten($clientTransactionId, $shift, $actor);
            if ($existing) {
                $existing->idempotent_replay = true; // flag untuk controller
                return $existing;
            }
        }

        try {
            return DB::transaction(function () use ($shift, $items, $pembayaran, $diskonNominal, $diskonPersen, $pembulatan, $pajak, $catatan, $namaPelanggan, $clientTransactionId, $actor) {
                // Lock the shift, then re-read it. The Shift model handed in by the
                // controller was loaded before this transaction started, so its status
                // is stale: a close that commits while we wait for the lock would
                // otherwise be invisible and the sale would land in a closed shift.
                //
                // Closing a shift takes the very same row lock before it sums the
                // transactions, so a sale and a close can never interleave: whichever
                // gets the lock first completes fully before the other re-reads.
                $lockedShift = Shift::where('id', $shift->id)->lockForUpdate()->first();

                if (! $lockedShift) {
                    abort(404, 'Shift tidak ditemukan');
                }

                $actor = $actor ?? auth()->user();

                // Cashier A must not sell into cashier B's shift, even while it is open.
                if ((int) $lockedShift->user_id !== (int) $actor?->id) {
                    Log::warning('Transaksi ditolak: shift bukan milik user', [
                        'shift_id' => (int) $lockedShift->id,
                        'shift_user_id' => (int) $lockedShift->user_id,
                        'actor_user_id' => (int) $actor?->id,
                    ]);

                    abort(403, 'Shift ini bukan milik Anda.');
                }

                if (! BranchAccessPolicy::canAccessCabang($actor, (int) $lockedShift->cabang_id)) {
                    Log::warning('Transaksi ditolak: cabang tidak diizinkan', [
                        'shift_id' => (int) $lockedShift->id,
                        'shift_cabang_id' => (int) $lockedShift->cabang_id,
                        'actor_user_id' => (int) $actor?->id,
                    ]);

                    abort(403, 'Anda tidak memiliki akses ke cabang shift ini.');
                }

                if ($lockedShift->status !== 'buka') {
                    Log::warning('Transaksi ditolak: shift sudah ditutup', [
                        'shift_id' => (int) $lockedShift->id,
                        'status' => $lockedShift->status,
                    ]);

                    abort(409, 'Shift sudah ditutup, transaksi tidak dapat diproses.');
                }

                return $this->buatTransaksiTanpaTransaksi($lockedShift, $items, $pembayaran, $diskonNominal, $diskonPersen, $pembulatan, $pajak, $catatan, $namaPelanggan, $clientTransactionId, $actor);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Lost the race: a concurrent request with the same key committed first.
            // The transaction above has already rolled back, so replaying its result
            // is safe. Returning 400 here used to be indistinguishable from bad input.
            if (! str_contains($e->getMessage(), 'client_transaction_id')) {
                throw $e;
            }

            $existing = $this->cariTransaksiIdempoten($clientTransactionId, $shift, $actor);

            if ($existing) {
                Log::info('Transaksi idempotent direplay setelah race', [
                    'client_transaction_id' => $clientTransactionId,
                    'transaksi_id' => (int) $existing->id,
                    'actor_user_id' => (int) $actor?->id,
                ]);

                $existing->idempotent_replay = true;

                return $existing;
            }

            // The key is taken but not by this cashier on this shift, so it cannot
            // be replayed: refuse instead of exposing someone else's sale.
            Log::warning('client_transaction_id bentrok dengan transaksi milik lain', [
                'client_transaction_id' => $clientTransactionId,
                'actor_user_id' => (int) $actor?->id,
                'shift_id' => (int) $shift->id,
            ]);

            abort(409, 'Idempotency key sudah dipakai untuk transaksi lain.');
        }
    }

    /**
     * Find a replayable transaction for this exact caller and shift.
     *
     * The client_transaction_id index is global, so it cannot be relied on alone:
     * a UUID reused across cashiers or shifts must resolve to nothing here rather
     * than leak the other sale.
     */
    private function cariTransaksiIdempoten(?string $clientTransactionId, Shift $shift, ?User $actor): ?Transaksi
    {
        if (! $clientTransactionId || ! $actor) {
            return null;
        }

        $existing = Transaksi::where('client_transaction_id', $clientTransactionId)
            ->where('user_id', $actor->id)
            ->where('shift_id', $shift->id)
            ->where('cabang_id', $shift->cabang_id)
            ->first();

        if ($existing) {
            $existing->load(['item.produk', 'pembayaran']);
        }

        return $existing;
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
        ?string $namaPelanggan = null,
        ?string $clientTransactionId = null,
        ?User $actor = null
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
            abort(422, 'Jumlah pembayaran tidak mencukupi');
        }

        $metodes = array_unique(array_column($pembayaran, 'metode'));
        $tipePembayaran = 'tunai';
        if (count($metodes) > 0) {
            $tipePembayaran = reset($metodes);
        }

        $transaksi = Transaksi::create([
            'shift_id' => $shift->id,
            'cabang_id' => $shift->cabang_id,
            // Attribute the sale to the cashier who rang it. The shift owner is only
            // a fallback for internal callers (open-bill conversion) that have no
            // authenticated actor; buatTransaksi() guarantees the two are the same.
            'user_id' => $actor?->id ?? $shift->user_id,
            'nama_pelanggan' => $namaPelanggan,
            'nomor_invoice' => $nomorInvoice,
            'client_transaction_id' => $clientTransactionId,  // idempotency key
            'tipe_pembayaran' => $tipePembayaran,
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
                'harga_modal' => $item['produk']->harga_modal ?? 0,
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
        $sisaKembalian = $totalPembayaran - $total;
        $adjustmentsMade = []; // Kumpulkan semua penyesuaian untuk di-log dan dikirim ke client

        // Sesuaikan jumlah pembayaran agar tidak melebihi total transaksi.
        // Prioritaskan mengurangi kembalian dari metode 'tunai' terlebih dahulu.
        foreach ($pembayaran as &$bayar) {
            if ($sisaKembalian > 0 && $bayar['metode'] === 'tunai') {
                $nominalInput = $bayar['jumlah'];
                $potongan = min($bayar['jumlah'], $sisaKembalian);
                $bayar['jumlah'] -= $potongan;
                $sisaKembalian -= $potongan;
                if ($potongan > 0) {
                    $adjustmentsMade[] = [
                        'metode' => $bayar['metode'],
                        'nominal_input' => $nominalInput,
                        'nominal_tercatat' => $bayar['jumlah'],
                        'potongan' => $potongan,
                        'alasan' => 'Kembalian tunai dikurangi dari input kasir',
                    ];
                }
            }
        }

        // Jika masih ada sisa kembalian (misal overpay dengan QRIS/Transfer),
        // kurangi secara paksa dari sisa pembayaran non-tunai.
        if ($sisaKembalian > 0) {
            foreach ($pembayaran as &$bayar) {
                if ($sisaKembalian > 0) {
                    $nominalInput = $bayar['jumlah'];
                    $potongan = min($bayar['jumlah'], $sisaKembalian);
                    $bayar['jumlah'] -= $potongan;
                    $sisaKembalian -= $potongan;
                    if ($potongan > 0) {
                        $adjustmentsMade[] = [
                            'metode' => $bayar['metode'],
                            'nominal_input' => $nominalInput,
                            'nominal_tercatat' => $bayar['jumlah'],
                            'potongan' => $potongan,
                            'alasan' => "Overpay pada metode {$bayar['metode']} disesuaikan otomatis ke nominal tagihan",
                        ];
                    }
                }
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

        // Catat semua penyesuaian ke audit log
        foreach ($adjustmentsMade as $adj) {
            $this->auditOverpayIfNeeded(
                (int) $transaksi->id,
                $adj['metode'],
                $adj['nominal_input'],
                $adj['nominal_tercatat'],
                $adj['potongan'],
                $adj['alasan']
            );
        }

        $loaded = $transaksi->load(['item.produk', 'pembayaran']);

        // Sertakan info penyesuaian di response agar Flutter bisa menampilkan notifikasi
        if (!empty($adjustmentsMade)) {
            $loaded->payment_adjustments = $adjustmentsMade;
        }

        return $loaded;
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

    /**
     * Catat ke tabel payment_adjustment_logs setiap kali nominal pembayaran
     * disesuaikan oleh backend (overpay correction).
     */
    private function auditOverpayIfNeeded(
        int $transaksiId,
        string $metode,
        float $nominalInput,
        float $nominalTercatat,
        float $potongan,
        string $alasan = ''
    ): void {
        if ($potongan <= 0) {
            return;
        }
        try {
            DB::table('payment_adjustment_logs')->insert([
                'transaksi_id'     => $transaksiId,
                'metode_pembayaran' => $metode,
                'nominal_input'    => $nominalInput,
                'nominal_tercatat' => $nominalTercatat,
                'potongan'         => $potongan,
                'alasan'           => $alasan,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            // Gagal audit tidak boleh menggagalkan transaksi utama
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
            abort(409, 'Transaksi sudah dibatalkan');
        }
        if ($transaksi->status !== 'selesai') {
            abort(422, 'Hanya transaksi selesai yang dapat dibatalkan');
        }
        $transaksi->loadMissing('shift');
        if (($transaksi->shift->status ?? null) !== 'buka') {
            abort(409, 'Shift sudah ditutup, transaksi tidak dapat dibatalkan.');
        }
        if (!in_array($user->role, ['manager', 'it_support'])) {
            abort(403, 'Tidak berwenang membatalkan transaksi');
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
            abort(409, 'Shift sudah ditutup, open bill tidak dapat dibayar.');
        }
        if ($openBill->status !== 'open') {
            abort(409, 'Open bill sudah tidak aktif');
        }
        if (empty($pembayaran)) {
            abort(422, 'Pembayaran tidak boleh kosong');
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

            // Open Bill convert tidak menggunakan client_transaction_id (idempotency ditangani
            // di level open bill ID itu sendiri — satu open bill hanya bisa di-convert sekali)
            $transaksi = $this->buatTransaksiTanpaTransaksi(
                $shift,
                $items,
                $pembayaran,
                $diskonNominalFinal,
                $diskonPersenFinal,
                $pembulatanFinal,
                $pajakFinal,
                $catatan ?? $openBill->catatan,
                $openBill->nama_pelanggan,
                null  // client_transaction_id = null for open bill conversions
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
