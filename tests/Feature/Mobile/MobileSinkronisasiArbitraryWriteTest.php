<?php

namespace Tests\Feature\Mobile;

use App\Models\AntrianSinkronisasi;
use App\Models\Cabang;
use App\Models\KategoriProduk;
use App\Models\MutasiStok;
use App\Models\Produk;
use App\Models\Shift;
use App\Models\StokEtalase;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 4 contract: the offline sync queue must never be an arbitrary-write channel.
 *
 * SinkronisasiController::prosesAntrian used to run Transaksi::create($payload),
 * MutasiStok::create($payload) and Shift::where(...)->update($payload) on raw client
 * JSON, so a kasir token could set any total, shift_id, cabang_id, user_id or status
 * on any of three financial tables. It also keyed the queue on a client-supplied
 * id_perangkat with no owner column, so any authenticated user could drain and read
 * another device's queue.
 */
class MobileSinkronisasiArbitraryWriteTest extends TestCase
{
    use RefreshDatabase;

    protected Cabang $cabang;

    protected Cabang $cabangLain;

    protected Produk $produk;

    protected Shift $shiftLain;

    protected function setUp(): void
    {
        parent::setUp();

        $kategori = KategoriProduk::create([
            'nama' => 'Kopi',
            'slug' => 'kopi',
            'deskripsi' => 'Kategori kopi',
        ]);

        $this->cabang = Cabang::create([
            'kode' => 'SYN1', 'nama' => 'Cabang Sinkron', 'alamat' => 'Jl. Sinkron',
            'telepon' => '08123', 'aktif' => true,
        ]);

        $this->cabangLain = Cabang::create([
            'kode' => 'SYN2', 'nama' => 'Cabang Lain', 'alamat' => 'Jl. Lain',
            'telepon' => '08124', 'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'sku' => 'SKU-SYN', 'nama' => 'Kopi Arabica', 'kategori_id' => $kategori->id,
            'tipe' => 'beans', 'harga_beli' => 50000, 'harga_jual' => 75000, 'aktif' => true,
        ]);
    }

    private function makeKasir(string $email, ?array $cabangIds = null): array
    {
        StokEtalase::firstOrCreate(
            ['produk_id' => $this->produk->id, 'cabang_id' => $this->cabang->id],
            ['tipe_stok' => 'penjualan_retail', 'jumlah' => 100, 'stok_minimum' => 10]
        );

        $user = User::create([
            'name' => 'Kasir '.$email,
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $user->cabang()->attach($cabangIds ?? [$this->cabang->id]);

        $login = $this->postJson('/api/pos/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $this->assertSame(200, $login->status(), 'Login gagal untuk '.$email);

        $this->app['auth']->forgetGuards();

        return [$user, (string) $login->json('token')];
    }

    private function bukaShift(string $token, ?Cabang $cabang = null): Shift
    {
        $this->app['auth']->forgetGuards();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/shift/buka', [
                'cabang_id' => ($cabang ?? $this->cabang)->id,
                'saldo_awal' => 500000,
            ]);

        $response->assertStatus(200);

        return Shift::find($response->json('shift.id'));
    }

    private function antrean(string $token, array $payload): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/sinkronisasi/antrian', $payload)
            ->assertStatus(200);
    }

    private function proses(string $token, string $idPerangkat)
    {
        // The previous request in this test may have authenticated as a different
        // cashier, and the Sanctum guard memoises the resolved user for the rest of
        // the PHP process. Without this reset the second identity silently reuses the
        // first one.
        $this->app['auth']->forgetGuards();

        return $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/sinkronisasi/proses', ['id_perangkat' => $idPerangkat]);
    }

    // ------------------------------------------- arbitrary column write is refused

    public function test_queued_shift_payload_cannot_overwrite_shift_columns()
    {
        [$user, $token] = $this->makeKasir('kasir@syn.test');
        $shift = $this->bukaShift($token);

        $this->antrean($token, [
            'id_perangkat' => 'dev-shift',
            'tipe_entitas' => 'shift',
            'id_entitas' => $shift->id,
            'payload' => [
                'status' => 'tutup',
                'saldo_akhir' => 999999,
                'selisih' => 499999,
            ],
        ]);

        $response = $this->proses($token, 'dev-shift');
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('jumlah_ditolak'));

        $shift->refresh();
        $this->assertSame('buka', $shift->status, 'Shift tidak boleh ditutup lewat sinkronisasi.');
        $this->assertEqualsWithDelta(0.0, (float) $shift->selisih, 0.001, 'Selisih tidak boleh ditulis dari client.');
        $this->assertNotEqualsWithDelta(999999.0, (float) $shift->saldo_akhir, 0.001);
    }

    public function test_queued_mutasi_stok_payload_does_not_write_stock()
    {
        [$user, $token] = $this->makeKasir('kasir@stok.test');
        $etalase = StokEtalase::where('produk_id', $this->produk->id)
            ->where('cabang_id', $this->cabang->id)->firstOrFail();
        $jumlahAwal = (int) $etalase->jumlah;

        $this->antrean($token, [
            'id_perangkat' => 'dev-stok',
            'tipe_entitas' => 'mutasi_stok',
            'id_entitas' => $etalase->id,
            'payload' => [
                'stok_etalase_id' => $etalase->id,
                'tipe' => 'keluar',
                'jumlah_perubahan' => -5,
                'jumlah_sebelum' => 100,
                'jumlah_sesudah' => 95,
                'catatan' => 'inJEK',
            ],
        ]);

        $response = $this->proses($token, 'dev-stok');
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('jumlah_ditolak'));

        $this->assertSame(0, MutasiStok::where('catatan', 'inJEK')->count());
        $this->assertSame($jumlahAwal, (int) $etalase->fresh()->jumlah, 'Stok tidak boleh berkurang dari sinkronisasi.');
    }

    public function test_queued_transaksi_cannot_force_total_status_or_ownership()
    {
        [$user, $token] = $this->makeKasir('kasir@trx.test');
        $shift = $this->bukaShift($token);

        // A payload shaped like a direct row write: no items, no payments, but
        // server-owned columns filled in by the client.
        $this->antrean($token, [
            'id_perangkat' => 'dev-trx',
            'tipe_entitas' => 'transaksi',
            'id_entitas' => 0,
            'payload' => [
                'shift_id' => $shift->id,
                'user_id' => 999999,
                'cabang_id' => $this->cabangLain->id,
                'status' => 'selesai',
                'total' => 1,
                'total_bayar' => 1,
            ],
        ]);

        $response = $this->proses($token, 'dev-trx');
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('jumlah_ditolak'));

        $this->assertSame(0, Transaksi::count(), 'Transaksi tidak boleh dibuat dari payload bebas.');
    }

    public function test_queued_transaksi_without_contract_fields_is_rejected_not_guessed()
    {
        [$user, $token] = $this->makeKasir('kasir@trx2.test');
        $shift = $this->bukaShift($token);

        $this->antrean($token, [
            'id_perangkat' => 'dev-trx2',
            'tipe_entitas' => 'transaksi',
            'id_entitas' => 0,
            'payload' => ['shift_id' => $shift->id, 'total' => 10],
        ]);

        $this->proses($token, 'dev-trx2')->assertStatus(200);

        $this->assertSame(0, Transaksi::count());
    }

    public function test_valid_queued_transaksi_replays_through_the_normal_contract()
    {
        [$user, $token] = $this->makeKasir('kasir@trx3.test');
        $shift = $this->bukaShift($token);

        $this->antrean($token, [
            'id_perangkat' => 'dev-trx3',
            'tipe_entitas' => 'transaksi',
            'id_entitas' => 0,
            'payload' => [
                'shift_id' => $shift->id,
                'client_transaction_id' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
                'items' => [['produk_id' => $this->produk->id, 'jumlah' => 1]],
                'pembayaran' => [['metode' => 'tunai', 'jumlah' => 75000]],
            ],
        ]);

        $response = $this->proses($token, 'dev-trx3');
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('jumlah_berhasil'));

        $transaksi = Transaksi::sole();
        $this->assertSame(75000.0, (float) $transaksi->total);
        $this->assertSame('selesai', $transaksi->status);
        // Ownership still comes from the authenticated user, not the queue payload.
        $this->assertSame($user->id, $transaksi->user_id);
        $this->assertSame($this->cabang->id, $transaksi->cabang_id);
    }

    public function test_queued_transaksi_cannot_write_to_another_cashiers_shift()
    {
        [$penyerang, $token] = $this->makeKasir('kasir@a.test');
        [$korban, $tokenB] = $this->makeKasir('kasir@b.test');

        $shiftKorban = $this->bukaShift($tokenB);

        $this->antrean($token, [
            'id_perangkat' => 'dev-x',
            'tipe_entitas' => 'transaksi',
            'id_entitas' => 0,
            'payload' => [
                'shift_id' => $shiftKorban->id,
                'client_transaction_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
                'items' => [['produk_id' => $this->produk->id, 'jumlah' => 1]],
                'pembayaran' => [['metode' => 'tunai', 'jumlah' => 75000]],
            ],
        ]);

        $response = $this->proses($token, 'dev-x');
        $response->assertStatus(200);
        $this->assertSame(1, $response->json('jumlah_ditolak'));
        $this->assertSame(0, Transaksi::count());
    }

    // ------------------------------------------------------- queue ownership

    public function test_cannot_process_another_devices_queue()
    {
        [$a, $tokenA] = $this->makeKasir('kasir@q1.test');
        [$b, $tokenB] = $this->makeKasir('kasir@q2.test');

        $this->app['auth']->forgetGuards();

        $this->antrean($tokenA, [
            'id_perangkat' => 'shared-device',
            'tipe_entitas' => 'mutasi_stok',
            'id_entitas' => 1,
            'payload' => ['jumlah' => 1],
        ]);

        $response = $this->proses($tokenB, 'shared-device');
        $response->assertStatus(200);

        $this->assertSame(0, $response->json('jumlah_berhasil'));
        $this->assertSame('pending', AntrianSinkronisasi::sole()->status);
    }

    public function test_status_does_not_leak_another_users_pending_payload()
    {
        [$a, $tokenA] = $this->makeKasir('kasir@s1.test');
        [$b, $tokenB] = $this->makeKasir('kasir@s2.test');

        $this->antrean($tokenA, [
            'id_perangkat' => 'dev-rahasia',
            'tipe_entitas' => 'transaksi',
            'id_entitas' => 1,
            'payload' => ['rahasia' => 'jangan-bocorkan'],
        ]);

        $this->app['auth']->forgetGuards();

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$tokenB])
            ->getJson('/api/pos/sinkronisasi/status?id_perangkat=dev-rahasia');

        $response->assertStatus(200);
        $this->assertSame(0, $response->json('statistik.pending'));
        $this->assertCount(0, $response->json('item_pending'));
    }

    public function test_own_queue_is_scoped_to_the_caller()
    {
        [$a, $tokenA] = $this->makeKasir('kasir@s3.test');

        $this->antrean($tokenA, [
            'id_perangkat' => 'dev-saya',
            'tipe_entitas' => 'transaksi',
            'id_entitas' => 1,
            'payload' => ['x' => 1],
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->getJson('/api/pos/sinkronisasi/status?id_perangkat=dev-saya');

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('statistik.pending'));
        $this->assertCount(1, $response->json('item_pending'));
    }

    // ------------------------------------------- transaksiPerShift branch access

    public function test_transaksi_per_shift_rejects_another_branches_shift()
    {
        [$a, $tokenA] = $this->makeKasir('kasir@t1.test');
        [$b, $tokenB] = $this->makeKasir('kasir@t2.test', [$this->cabangLain->id]);

        $shiftLain = $this->bukaShift($tokenB, $this->cabangLain);

        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->getJson("/api/viewer/transaksi/shift/{$shiftLain->id}")
            ->assertStatus(403);
    }

    public function test_transaksi_per_shift_allows_own_branch()
    {
        [$a, $tokenA] = $this->makeKasir('kasir@t3.test');
        $shift = $this->bukaShift($tokenA);

        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->getJson("/api/pos/transaksi/shift/{$shift->id}")
            ->assertStatus(200);
    }

    // -------------------------------------------------------------- input rules

    public function test_queue_requires_a_json_object_payload()
    {
        [$user, $token] = $this->makeKasir('kasir@v1.test');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/sinkronisasi/antrian', [
                'id_perangkat' => 'dev-json',
                'tipe_entitas' => 'transaksi',
                'id_entitas' => 1,
                'payload' => 'bukan-json',
            ])
            ->assertStatus(422);
    }

    public function test_queue_rejects_unknown_entity_type()
    {
        [$user, $token] = $this->makeKasir('kasir@v2.test');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/sinkronisasi/antrian', [
                'id_perangkat' => 'dev-unknown',
                'tipe_entitas' => 'user',
                'id_entitas' => 1,
                'payload' => ['role' => 'manager'],
            ])
            ->assertStatus(422);
    }

    public function test_queue_rejects_unauthenticated_caller()
    {
        $this->postJson('/api/pos/sinkronisasi/antrian', [
            'id_perangkat' => 'dev-anon',
            'tipe_entitas' => 'transaksi',
            'id_entitas' => 1,
            'payload' => ['a' => 1],
        ])->assertStatus(401);

        $this->postJson('/api/pos/sinkronisasi/proses', ['id_perangkat' => 'dev-anon'])
            ->assertStatus(401);

        $this->assertSame(0, AntrianSinkronisasi::count());
    }

    public function test_rejected_item_is_marked_failed_and_not_retried()
    {
        [$user, $token] = $this->makeKasir('kasir@v3.test');
        $shift = $this->bukaShift($token);

        $this->antrean($token, [
            'id_perangkat' => 'dev-fail',
            'tipe_entitas' => 'shift',
            'id_entitas' => $shift->id,
            'payload' => ['status' => 'tutup'],
        ]);

        $this->proses($token, 'dev-fail')->assertStatus(200);

        $item = AntrianSinkronisasi::sole();
        $this->assertSame('gagal', $item->status);
        // A deterministic rejection must not sit in the retry loop for 3 attempts.
        $this->assertSame(0, $item->jumlah_percobaan);
    }
}
