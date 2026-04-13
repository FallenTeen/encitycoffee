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

class MobileDiscountFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cabang $cabang;
    protected string $token;
    protected Produk $produk;
    protected array $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $kategori = KategoriProduk::create([
            'nama' => 'Kopi',
            'slug' => 'kopi',
            'deskripsi' => 'Kategori kopi',
        ]);

        $this->cabang = Cabang::create([
            'kode' => 'CBG001',
            'nama' => 'Cabang Test',
            'alamat' => 'Jl. Test No. 1',
            'telepon' => '08123456789',
            'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'sku' => 'SKU001',
            'nama' => 'Kopi Arabica',
            'kategori_id' => $kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 50000,
            'harga_jual' => 75000,
            'aktif' => true,
        ]);

        StokEtalase::create([
            'produk_id' => $this->produk->id,
            'cabang_id' => $this->cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 100,
            'stok_minimum' => 10,
        ]);

        $this->user = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir@test.com',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $this->user->cabang()->attach($this->cabang->id);

        $login = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@test.com',
            'password' => 'password123',
        ]);

        $this->token = (string) $login->json('token');

        $shiftResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 500000,
        ]);

        $this->shift = (array) $shiftResponse->json('shift');
    }

    public function test_discount_preview_endpoint_returns_consistent_payload()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/discount/preview', [
            'total_awal' => 100000,
            'diskon_nominal' => 20000,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_awal',
                    'diskon_nominal',
                    'diskon_persen',
                    'total_akhir',
                    'pembulatan' => [
                        'applied',
                        'mode',
                        'unit',
                        'total_sebelum',
                        'total_setelah',
                        'delta_total',
                        'diskon_delta',
                    ],
                ],
            ]);
    }

    public function test_transaction_can_be_created_using_discount_percent()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 2,
                ],
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 150000,
                ],
            ],
            'diskon_persen' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'status' => 'selesai',
            ]);

        $transaksi = Transaksi::query()->latest('id')->firstOrFail();
        $this->assertEquals(150000.0, (float) $transaksi->subtotal);
        $this->assertEquals(15000.0, (float) $transaksi->diskon);
        $this->assertEquals(10.0, (float) $transaksi->diskon_persen);
        $this->assertEquals(135000.0, (float) $transaksi->total);
    }
}

