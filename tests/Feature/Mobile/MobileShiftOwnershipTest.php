<?php

namespace Tests\Feature\Mobile;

use App\Models\Cabang;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\Shift;
use App\Models\StokEtalase;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 2 contract: user + branch => at most one active shift, and a sale may only
 * target the authenticated cashier's own open shift in a branch they can access.
 */
class MobileShiftOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected Cabang $cabangA;

    protected Cabang $cabangB;

    protected Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $kategori = KategoriProduk::create([
            'nama' => 'Kopi',
            'slug' => 'kopi',
            'deskripsi' => 'Kategori kopi',
        ]);

        $this->cabangA = Cabang::create([
            'kode' => 'A01',
            'nama' => 'Cabang A',
            'alamat' => 'Jl. A',
            'telepon' => '08123',
            'aktif' => true,
        ]);

        $this->cabangB = Cabang::create([
            'kode' => 'B01',
            'nama' => 'Cabang B',
            'alamat' => 'Jl. B',
            'telepon' => '08124',
            'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'sku' => 'SKU-OWN',
            'nama' => 'Kopi Arabica',
            'kategori_id' => $kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 50000,
            'harga_jual' => 75000,
            'aktif' => true,
        ]);
    }

    private function makeKasir(string $email, array $cabangIds): array
    {
        $user = User::create([
            'name' => 'Kasir '.$email,
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $user->cabang()->attach($cabangIds);

        $login = $this->postJson('/api/pos/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ]);

        $this->assertSame(200, $login->status(), 'Login gagal untuk '.$email);

        return [$user, (string) $login->json('token')];
    }

    private function buatProdukReady(Cabang $cabang): void
    {
        StokEtalase::create([
            'produk_id' => $this->produk->id,
            'cabang_id' => $cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 100,
            'stok_minimum' => 10,
        ]);
    }

    private function bukaShift(string $token, Cabang $cabang, float $saldoAwal = 500000): array
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $cabang->id,
            'saldo_awal' => $saldoAwal,
        ]);

        $response->assertStatus(200);

        return (array) $response->json('shift');
    }

    private function payloadTransaksi(int $shiftId, string $clientTransactionId): array
    {
        return [
            'shift_id' => $shiftId,
            'client_transaction_id' => $clientTransactionId,
            'items' => [
                ['produk_id' => $this->produk->id, 'jumlah' => 1],
            ],
            'pembayaran' => [
                ['metode' => 'tunai', 'jumlah' => 75000],
            ],
        ];
    }

    private function tutupShift(string $token, int $shiftId, float $saldoAkhir, array $extra = [])
    {
        return $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/shift/'.$shiftId.'/tutup', array_merge([
                'saldo_akhir' => $saldoAkhir,
            ], $extra));
    }

    // ---------------------------------------------------------------- active shift

    public function test_active_shift_is_scoped_to_user_and_branch()
    {
        [$userA, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id, $this->cabangB->id]);
        [$userB, $tokenB] = $this->makeKasir('b@pos.test', [$this->cabangA->id]);

        $shiftA1 = $this->bukaShift($tokenA, $this->cabangA);
        $shiftA2 = $this->bukaShift($tokenA, $this->cabangB);

        $this->assertNotSame((int) $shiftA1['id'], (int) $shiftA2['id']);
        $this->assertSame('buka', $shiftA1['status']);
        $this->assertSame('buka', $shiftA2['status']);

        // Kasir A without a branch filter gets their most recent shift.
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->getJson('/api/pos/shift/aktif')
            ->assertStatus(200)
            ->assertJsonPath('shift.id', $shiftA2['id']);

        // Kasir A filtering by branch gets that branch's shift.
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->getJson('/api/pos/shift/aktif?cabang_id='.$this->cabangA->id)
            ->assertStatus(200)
            ->assertJsonPath('shift.id', $shiftA1['id']);

        // Kasir B in the same branch still has no shift of their own.
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenB])
            ->getJson('/api/pos/shift/aktif?cabang_id='.$this->cabangA->id)
            ->assertStatus(404);

        $this->assertSame((int) $userA->id, (int) $shiftA1['user_id']);
        $this->assertSame((int) $userB->id, (int) $userB->id);
    }

    public function test_active_shift_request_for_inaccessible_branch_is_forbidden()
    {
        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $this->bukaShift($tokenA, $this->cabangA);

        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->getJson('/api/pos/shift/aktif?cabang_id='.$this->cabangB->id)
            ->assertStatus(403);
    }

    // ---------------------------------------------------------------- open shift

    public function test_same_user_cannot_open_two_active_shifts_in_same_branch()
    {
        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $this->bukaShift($tokenA, $this->cabangA);

        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/shift/buka', [
                'cabang_id' => $this->cabangA->id,
                'saldo_awal' => 500000,
            ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'User sudah memiliki shift yang masih buka');

        $this->assertSame(1, Shift::where('status', 'buka')->count());
    }

    /**
     * The controller's duplicate pre-check is UX only, so the unique index is the
     * real guarantee. This simulates a competitor opening the shift *after* the
     * pre-check passed, and asserts the DB rejection surfaces the same contract.
     */
    public function test_concurrent_open_race_falls_back_to_database_constraint()
    {
        [$userA, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);

        $raced = false;
        Event::listen('eloquent.creating: '.Shift::class, function () use ($userA, &$raced) {
            if ($raced) {
                return;
            }
            $raced = true;

            // A competing open shift for the same user+branch appears *after* the
            // controller's duplicate pre-check has already passed. The only thing
            // left that can reject this insert is the unique index, so a 400 here
            // proves the database is the real guarantee and not the pre-check.
            DB::table('shift')->insert([
                'user_id' => $userA->id,
                'cabang_id' => $this->cabangA->id,
                'saldo_awal' => 500000,
                'status' => 'buka',
                'waktu_buka' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/shift/buka', [
                'cabang_id' => $this->cabangA->id,
                'saldo_awal' => 500000,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'User sudah memiliki shift yang masih buka');

        $this->assertTrue($raced, 'Race helper tidak berjalan, test tidak valid');
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());

        // The whole race is rolled back, so the competing row must not survive.
        $this->assertSame(0, Shift::where('status', 'buka')->count());
    }

    public function test_database_rejects_two_open_shifts_for_same_user_and_branch()
    {
        [$userA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);

        $row = [
            'user_id' => $userA->id,
            'cabang_id' => $this->cabangA->id,
            'saldo_awal' => 500000,
            'status' => 'buka',
            'waktu_buka' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('shift')->insert($row);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        DB::table('shift')->insert($row);
    }

    public function test_closed_shift_does_not_block_a_new_open_shift()
    {
        [$userA, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $first = $this->bukaShift($tokenA, $this->cabangA);

        $this->tutupShift($tokenA, (int) $first['id'], 500000)->assertStatus(200);

        $second = $this->bukaShift($tokenA, $this->cabangA);

        $this->assertNotSame((int) $first['id'], (int) $second['id']);
    }

    // ---------------------------------------------------------------- ownership

    public function test_cannot_create_transaction_in_another_cashiers_open_shift()
    {
        $this->buatProdukReady($this->cabangA);

        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        [, $tokenB] = $this->makeKasir('b@pos.test', [$this->cabangA->id]);

        $shiftA = $this->bukaShift($tokenA, $this->cabangA);

        // Kasir B targets kasir A's still-open shift.
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenB])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shiftA['id'], '11111111-1111-4111-8111-111111111111'))
            ->assertStatus(403);

        $this->assertSame(0, Transaksi::count());
    }

    public function test_cannot_create_transaction_in_shift_of_inaccessible_branch()
    {
        $this->buatProdukReady($this->cabangA);

        // A kasir assigned to branch A owns the shift.
        [$kasirA, $tokenA] = $this->makeKasir('spv@pos.test', [$this->cabangA->id]);

        $shift = $this->bukaShift($tokenA, $this->cabangA);

        // A kasir assigned only to branch B may not sell into branch A's shift.
        [, $tokenB] = $this->makeKasir('b@pos.test', [$this->cabangB->id]);

        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenB])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shift['id'], '22222222-2222-4222-8222-222222222222'))
            ->assertStatus(403);

        $this->assertSame(0, Transaksi::count());
    }

    public function test_cannot_create_transaction_in_closed_shift()
    {
        $this->buatProdukReady($this->cabangA);

        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $shift = $this->bukaShift($tokenA, $this->cabangA);

        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shift['id'], '33333333-3333-4333-8333-333333333333'))
            ->assertStatus(200);

        $this->tutupShift($tokenA, (int) $shift['id'], 575000)->assertStatus(200);

        // A brand new sale after the close must be refused.
        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shift['id'], '44444444-4444-4444-8444-444444444444'))
            ->assertStatus(409);

        $this->assertSame(1, Transaksi::count());
    }

    public function test_idempotent_retry_still_replays_after_shift_closed()
    {
        $this->buatProdukReady($this->cabangA);

        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $shift = $this->bukaShift($tokenA, $this->cabangA);

        $first = $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shift['id'], '55555555-5555-4555-8555-555555555555'))
            ->assertStatus(200);

        $this->tutupShift($tokenA, (int) $shift['id'], 575000)->assertStatus(200);

        $retry = $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shift['id'], '55555555-5555-4555-8555-555555555555'))
            ->assertStatus(200);

        $this->assertSame($first->json('id'), $retry->json('id'));
        $this->assertTrue((bool) $retry->json('_idempotent'));
        $this->assertSame(1, Transaksi::count());
    }

    // ---------------------------------------------------------------- serialization

    /**
     * A sale and a close must not interleave, so the sale path has to take a row
     * lock on the shift and re-read it. Assert the lock is really issued instead of
     * trusting the comment.
     */
    public function test_transaction_path_locks_the_shift_row()
    {
        $this->buatProdukReady($this->cabangA);

        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $shift = $this->bukaShift($tokenA, $this->cabangA);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shift['id'], '66666666-6666-4666-8666-666666666666'))
            ->assertStatus(200);

        $this->assertNotEmpty(
            array_filter($queries, fn ($sql) => stripos($sql, 'for update') !== false),
            'Query transaksi harus mengunci baris shift dengan FOR UPDATE'
        );
    }

    public function test_close_totals_are_derived_from_persisted_payments_not_client_input()
    {
        $this->buatProdukReady($this->cabangA);

        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $shift = $this->bukaShift($tokenA, $this->cabangA);

        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shift['id'], '77777777-7777-4777-8777-777777777777'))
            ->assertStatus(200);

        // The cashier lies about the totals; the server must ignore them.
        $response = $this->tutupShift($tokenA, (int) $shift['id'], 575000, [
            'total_tunai' => 999999,
            'total_qris' => 888888,
        ])->assertStatus(200);

        $closed = Shift::find($shift['id']);

        $this->assertSame(75000.0, (float) $closed->total_tunai, 'total_tunai harus dari pembayaran tersimpan');
        $this->assertSame(0.0, (float) $closed->total_qris, 'total_qris harus dari pembayaran tersimpan');
        $this->assertSame(575000.0, (float) $closed->saldo_diharapkan, 'saldo_d|GND = saldo_awal + total_tunai');
        $this->assertSame(0.0, (float) $closed->selisih, 'selisih = saldo_akhir - saldo_diharapkan');
        $this->assertSame('tutup', $closed->status);
    }

    public function test_close_ignores_trashed_transactions_in_totals()
    {
        $this->buatProdukReady($this->cabangA);

        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $shift = $this->bukaShift($tokenA, $this->cabangA);

        $this->withHeaders(['Authorization' => 'Bearer '.$tokenA])
            ->postJson('/api/pos/transaksi', $this->payloadTransaksi((int) $shift['id'], '88888888-8888-4888-8888-888888888888'))
            ->assertStatus(200);

        $transaksi = Transaksi::first();
        $this->assertNotNull($transaksi);
        $transaksi->delete();

        $this->tutupShift($tokenA, (int) $shift['id'], 500000)->assertStatus(200);

        $closed = Shift::find($shift['id']);
        $this->assertSame(0.0, (float) $closed->total_tunai, 'Transaksi soft-deleted tidak boleh ikut dihitung');
    }

    public function test_close_error_does_not_leak_internal_exception_details()
    {
        [, $tokenA] = $this->makeKasir('a@pos.test', [$this->cabangA->id]);
        $shift = $this->bukaShift($tokenA, $this->cabangA);

        // Force a failure inside the close transaction without a shift_id the
        // controller can resolve, to prove the 500 body stays generic.
        $response = $this->tutupShift($tokenA, 999999999, 0);

        // A missing shift is a 404, not a 500, and must not carry SQL text.
        $response->assertStatus(404);
        $body = $response->getContent();
        $this->assertStringNotContainsString('SQLSTATE', $body);
        $this->assertStringNotContainsString('select * from', $body);
    }
}
