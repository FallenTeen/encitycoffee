<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\StokEtalase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OpenBillFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $cabang;
    protected $produk;
    protected $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $kategori = KategoriProduk::create([
            'nama' => 'Minuman',
            'slug' => 'minuman',
            'deskripsi' => 'Kategori minuman',
        ]);

        $this->cabang = Cabang::create([
            'kode' => 'CBG001',
            'nama' => 'Cabang A',
            'alamat' => 'Alamat',
            'telepon' => '0800000',
            'aktif' => true,
        ]);

        $this->produk = Produk::create([
            'sku' => 'SKU-OB-1',
            'nama' => 'Americano',
            'kategori_id' => $kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 10000,
            'harga_jual' => 20000,
            'aktif' => true,
        ]);

        StokEtalase::create([
            'produk_id' => $this->produk->id,
            'cabang_id' => $this->cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 100,
            'stok_minimum' => 5,
        ]);

        $this->user = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@example.com',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true,
        ]);
        $this->user->cabang()->attach($this->cabang->id);

        $login = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@example.com',
            'password' => 'password123',
        ]);
        $this->token = $login->json('token');

        $shiftResp = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000,
        ]);
        $this->shift = $shiftResp->json('shift');
    }

    public function test_open_bill_to_close_bill_payment_success()
    {
        $openBillResp = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/transaksi/open-bill', [
            'shift_id' => $this->shift['id'],
            'items' => [
                ['produk_id' => $this->produk->id, 'jumlah' => 2],
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'OB test',
            'nama_pelanggan' => 'Budi',
        ]);
        $openBillResp->assertStatus(200);
        $openBillId = $openBillResp->json('id');

        $payResp = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/pos/transaksi/open-bill/{$openBillId}/bayar", [
            'pembayaran' => [
                ['metode' => 'tunai', 'jumlah' => 40000, 'referensi' => 'CASH-OB-1'],
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'Bayar OB',
        ]);

        $payResp->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'transaksi' => [
                    'id', 'nomor_invoice', 'item', 'pembayaran', 'status',
                ],
            ]);

        $this->assertDatabaseHas('open_bills', [
            'id' => $openBillId,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('open_bill_items', [
            'open_bill_id' => $openBillId,
        ]);
    }

    public function test_payment_insufficient_amount_keeps_open_bill_and_no_transaction()
    {
        $openBillResp = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/transaksi/open-bill', [
            'shift_id' => $this->shift['id'],
            'items' => [
                ['produk_id' => $this->produk->id, 'jumlah' => 2],
            ],
        ]);
        $openBillResp->assertStatus(200);
        $openBillId = $openBillResp->json('id');

        $payResp = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/pos/transaksi/open-bill/{$openBillId}/bayar", [
            'pembayaran' => [
                ['metode' => 'tunai', 'jumlah' => 10000],
            ],
        ]);

        // Underpayment is a business-rule rejection, so it is 422 like the direct sale
        // path, not the 400 this test previously asserted.
        $payResp->assertStatus(422)
            ->assertJsonStructure(['error']);

        $this->assertDatabaseHas('open_bills', [
            'id' => $openBillId,
            'status' => 'open',
        ]);
        $this->assertDatabaseMissing('transaksi', [
            'nomor_invoice' => $payResp->json('transaksi.nomor_invoice'),
        ]);
    }
}

