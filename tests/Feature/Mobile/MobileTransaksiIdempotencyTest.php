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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 3 contract: idempotency integrity (INVARIANT-02) and error semantics
 * (INVARIANT-10) on POST /api/pos/transaksi.
 *
 * DatabaseMigrations rather than RefreshDatabase on purpose: the retryability test
 * needs a second connection to hold a real row lock, which is impossible while the
 * test's own writes sit uncommitted inside RefreshDatabase's wrapping transaction.
 */
class MobileTransaksiIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected Cabang $cabang;

    protected Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $kategori = KategoriProduk::create([
            'nama' => 'Kopi',
            'slug' => 'kopi',
            'deskripsi' => 'Kategori kopi',
        ]);

        $this->cabang = Cabang::create([
            'kode' => 'IDEM1',
            'nama' => 'Cabang Idempotensi',
            'alamat' => 'Jl. Idem',
            'telepon' => '08123',
            'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'sku' => 'SKU-IDEM',
            'nama' => 'Kopi Arabica',
            'kategori_id' => $kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 50000,
            'harga_jual' => 75000,
            'aktif' => true,
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

        // The Sanctum guard caches the user resolved from the previous request, so
        // switching identity in a test requires resetting it explicitly.
        $this->app['auth']->forgetGuards();

        return [$user, (string) $login->json('token')];
    }

    private function bukaShift(string $token, ?Cabang $cabang = null): Shift
    {
        $cabang = $cabang ?? $this->cabang;

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/shift/buka', [
                'cabang_id' => $cabang->id,
                'saldo_awal' => 500000,
            ]);

        $response->assertStatus(200);

        return Shift::find($response->json('shift.id'));
    }

    private function postTransaksi(string $token, Shift $shift, array $overrides = [])
    {
        return $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/transaksi', array_merge([
                'shift_id' => $shift->id,
                'client_transaction_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'items' => [
                    ['produk_id' => $this->produk->id, 'jumlah' => 1],
                ],
                'pembayaran' => [
                    ['metode' => 'tunai', 'jumlah' => 75000],
                ],
            ], $overrides));
    }

    // ------------------------------------------------------------ required key

    public function test_client_transaction_id_is_required()
    {
        [, $token] = $this->makeKasir('a@pos.test');
        $shift = $this->bukaShift($token);

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/transaksi', [
                'shift_id' => $shift->id,
                'items' => [
                    ['produk_id' => $this->produk->id, 'jumlah' => 1],
                ],
                'pembayaran' => [
                    ['metode' => 'tunai', 'jumlah' => 75000],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('client_transaction_id');

        $this->assertSame(0, Transaksi::count());
    }

    public function test_client_transaction_id_must_be_a_valid_uuid()
    {
        [, $token] = $this->makeKasir('a@pos.test');
        $shift = $this->bukaShift($token);

        $this->postTransaksi($token, $shift, ['client_transaction_id' => 'not-a-uuid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('client_transaction_id');

        $this->assertSame(0, Transaksi::count());
    }

    // ------------------------------------------------------------ replay

    public function test_same_key_replays_the_original_sale_instead_of_duplicating()
    {
        [, $token] = $this->makeKasir('a@pos.test');
        $shift = $this->bukaShift($token);

        $first = $this->postTransaksi($token, $shift)
            ->assertStatus(200)
            ->assertJsonPath('_idempotent', null);

        $retry = $this->postTransaksi($token, $shift)
            ->assertStatus(200)
            ->assertJsonPath('_idempotent', true);

        $this->assertSame($first->json('id'), $retry->json('id'));
        $this->assertSame(1, Transaksi::count());
    }

    // ------------------------------------------------------------ cross-cashier leak

    /**
     * The core F-05 regression. The client_transaction_id index is global, so a
     * reused UUID must not hand cashier B a full copy of cashier A's sale.
     */
    public function test_reused_key_from_another_cashier_does_not_leak_the_sale()
    {
        [, $tokenA] = $this->makeKasir('a@pos.test');
        $shiftA = $this->bukaShift($tokenA);

        $key = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

        $first = $this->postTransaksi($tokenA, $shiftA, [
            'client_transaction_id' => $key,
            'nama_pelanggan' => 'Rahasia Pty',
        ])->assertStatus(200);

        [$userB, $tokenB] = $this->makeKasir('b@pos.test');
        $shiftB = $this->bukaShift($tokenB);
        $response = $this->postTransaksi($tokenB, $shiftB, [
            'client_transaction_id' => $key,
            'nama_pelanggan' => 'Penyerang',
        ]);

        $response->assertStatus(409);

        $body = $response->getContent();
        $this->assertStringNotContainsString('Rahasia Pty', $body);
        $this->assertStringNotContainsString((string) $first->json('id'), $body);
        $this->assertStringNotContainsString('SQLSTATE', $body);

        // Only cashier A's original sale exists; B's attempt created nothing.
        $this->assertSame(1, Transaksi::count());
        $this->assertSame('Rahasia Pty', Transaksi::first()->nama_pelanggan);
    }

    public function test_reused_key_on_another_shift_of_the_same_cashier_is_a_conflict()
    {
        [$user, $token] = $this->makeKasir('a@pos.test', [$this->cabang->id]);

        $cabangLain = Cabang::create([
            'kode' => 'IDEM2',
            'nama' => 'Cabang Kedua',
            'alamat' => 'Jl. 2',
            'telepon' => '08124',
            'aktif' => true,
        ]);
        $user->cabang()->attach($cabangLain->id);

        $key = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

        $shiftA = $this->bukaShift($token);
        $this->postTransaksi($token, $shiftA, ['client_transaction_id' => $key])->assertStatus(200);

        $this->app['auth']->forgetGuards();
        $shiftB = $this->bukaShift($token, $cabangLain);

        $this->postTransaksi($token, $shiftB, ['client_transaction_id' => $key])
            ->assertStatus(409);

        $this->assertSame(1, Transaksi::count());
    }

    // ------------------------------------------------------------ attribution

    public function test_sale_is_attributed_to_the_authenticated_cashier()
    {
        [$user, $token] = $this->makeKasir('a@pos.test');
        $shift = $this->bukaShift($token);

        $this->postTransaksi($token, $shift)->assertStatus(200);

        $transaksi = Transaksi::first();

        $this->assertSame((int) $user->id, (int) $transaksi->user_id);
        $this->assertSame((int) $shift->id, (int) $transaksi->shift_id);
    }

    // ------------------------------------------------------------ error semantics

    public function test_underpayment_is_unprocessable_not_bad_request()
    {
        [, $token] = $this->makeKasir('a@pos.test');
        $shift = $this->bukaShift($token);

        $this->postTransaksi($token, $shift, [
            'pembayaran' => [
                ['metode' => 'tunai', 'jumlah' => 1000],
            ],
        ])->assertStatus(422);

        $this->assertSame(0, Transaksi::count());
    }

    public function test_sale_into_missing_shift_is_rejected_before_any_write()
    {
        [, $token] = $this->makeKasir('a@pos.test');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/pos/transaksi', [
                'shift_id' => 987654321,
                'client_transaction_id' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
                'items' => [
                    ['produk_id' => $this->produk->id, 'jumlah' => 1],
                ],
                'pembayaran' => [
                    ['metode' => 'tunai', 'jumlah' => 75000],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('shift_id');

        $this->assertSame(0, Transaksi::count());
    }

    /**
     * INVARIANT-10: an infrastructure failure must be classified retryable so the
     * endpoint answers 5xx, never 400.
     *
     * Exercised against the classifier rather than a real deadlock: RefreshDatabase
     * holds the test's writes inside an uncommitted transaction, so a second
     * connection cannot take the shift row lock needed to provoke a genuine
     * lock-wait timeout. This asserts the same decision the controller makes.
     */
    public function test_transient_infrastructure_failures_are_classified_retryable()
    {
        $method = new \ReflectionMethod(\App\Http\Controllers\TransaksiController::class, 'isTransientDatabaseFailure');
        $method->setAccessible(true);
        $controller = app(\App\Http\Controllers\TransaksiController::class);

        // PDOException forbids a string code, so the SQLSTATE is carried on the
        // public errorInfo array, exactly as the driver reports it.
        $pdoWithState = static function (string $sqlState, int $driverCode) {
            $e = new \PDOException('driver failure', $driverCode);
            $e->errorInfo = [$sqlState, $driverCode, 'MySQL server failure'];

            return $e;
        };

        $retryable = [
            // DeadlockException covers MySQL 1213 and 1205 in this framework version.
            new \Illuminate\Database\DeadlockException('Deadlock found', 40001, new \RuntimeException('prev')),
            new \Illuminate\Database\LostConnectionException('Connection refused', 2002, new \RuntimeException('prev')),
            $pdoWithState('HY000', 2006),   // server has gone away
            $pdoWithState('40001', 1213),  // serialization / deadlock failure
            $pdoWithState('08S01', 2003),   // connection failure during transaction
        ];

        foreach ($retryable as $i => $e) {
            $this->assertTrue(
                $method->invoke($controller, $e),
                'Harus retryable: '.$e::class.' #'.$i
            );
        }

        $notRetryable = [
            new \Illuminate\Database\UniqueConstraintViolationException(
                'mysql', 'insert into transaksi values (?)', [], $pdoWithState('23000', 1062)
            ),
            $pdoWithState('23000', 1062),   // duplicate entry is handled elsewhere, not retried
            new \RuntimeException('Shift tidak ditemukan'),
            new \Symfony\Component\HttpKernel\Exception\ConflictHttpException('Shift sudah ditutup'),
        ];

        foreach ($notRetryable as $i => $e) {
            $this->assertFalse(
                $method->invoke($controller, $e),
                'Tidak boleh retryable: '.$e::class.' #'.$i
            );
        }
    }
}
