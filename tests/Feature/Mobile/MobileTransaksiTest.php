<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\StokEtalase;
use App\Models\Shift;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class MobileTransaksiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $cabang;
    protected $token;
    protected $produk;
    protected $shift;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create kategori
        $kategori = KategoriProduk::create([
            'nama' => 'Kopi',
            'slug' => 'kopi',
            'deskripsi' => 'Kategori kopi'
        ]);

        // Create cabang
        $this->cabang = Cabang::create([
            'kode' => 'CBG001',
            'nama' => 'Cabang Test',
            'alamat' => 'Jl. Test No. 1',
            'telepon' => '08123456789',
            'aktif' => true
        ]);

        // Create produk
        $this->produk = Produk::create([
            'sku' => 'SKU001',
            'nama' => 'Kopi Arabica',
            'kategori_id' => $kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 50000,
            'harga_jual' => 75000,
            'aktif' => true
        ]);

        // Create stok
        StokEtalase::create([
            'produk_id' => $this->produk->id,
            'cabang_id' => $this->cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 100,
            'stok_minimum' => 10
        ]);

        // Create user
        $this->user = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir@test.com',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true
        ]);

        // Assign user to cabang
        $this->user->cabang()->attach($this->cabang->id);

        // Login and get token
        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@test.com',
            'password' => 'password123'
        ]);

        $this->token = $response->json('token');

        // Open shift
        $shiftResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 500000
        ]);

        $this->shift = $shiftResponse->json('shift');
    }

    /**
     * Test successful transaction creation
     */
    public function test_successful_transaction_creation()
    {
        Log::info('Testing successful transaction creation', [
            'user_id' => $this->user->id,
            'shift_id' => $this->shift['id'],
            'produk_id' => $this->produk->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 2,
                    'catatan' => 'Test transaction'
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 150000, // 2 * 75000
                    'referensi' => 'CASH001'
                ]
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'Test transaction'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'nomor_invoice',
                    'shift_id',
                    'total',
                    'status',
                    'items',
                    'pembayaran'
                ]);

        // Verify transaction was created
        $this->assertDatabaseHas('transaksi', [
            'shift_id' => $this->shift['id'],
            'status' => 'selesai'
        ]);

        // Verify stok was reduced
        $stok = StokEtalase::where('produk_id', $this->produk->id)
                           ->where('cabang_id', $this->cabang->id)
                           ->first();
        
        $this->assertEquals(98, $stok->jumlah); // 100 - 2

        Log::info('Transaction created successfully', [
            'transaction_id' => $response->json('id'),
            'nomor_invoice' => $response->json('nomor_invoice')
        ]);
    }

    /**
     * Test transaction with multiple items
     */
    public function test_transaction_with_multiple_items()
    {
        // Create another produk
        $produk2 = Produk::create([
            'sku' => 'SKU002',
            'nama' => 'Kopi Robusta',
            'kategori_id' => $this->produk->kategori_id,
            'tipe' => 'beans',
            'harga_beli' => 40000,
            'harga_jual' => 65000,
            'aktif' => true
        ]);

        StokEtalase::create([
            'produk_id' => $produk2->id,
            'cabang_id' => $this->cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 50,
            'stok_minimum' => 5
        ]);

        Log::info('Testing transaction with multiple items', [
            'shift_id' => $this->shift['id']
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 1,
                    'catatan' => 'Arabica'
                ],
                [
                    'produk_id' => $produk2->id,
                    'jumlah' => 2,
                    'catatan' => 'Robusta'
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 205000, // 75000 + 2 * 65000
                    'referensi' => 'CASH002'
                ]
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'Multiple items test'
        ]);

        $response->assertStatus(200);

        // Verify both items were processed
        $items = $response->json('items');
        $this->assertCount(2, $items);

        // Verify stok reduction for both produk
        $stok1 = StokEtalase::where('produk_id', $this->produk->id)
                           ->where('cabang_id', $this->cabang->id)
                           ->first();
        $stok2 = StokEtalase::where('produk_id', $produk2->id)
                           ->where('cabang_id', $this->cabang->id)
                           ->first();
        
        $this->assertEquals(99, $stok1->jumlah); // 100 - 1
        $this->assertEquals(48, $stok2->jumlah); // 50 - 2

        Log::info('Multiple items transaction completed successfully');
    }

    /**
     * Test transaction with discount and tax
     */
    public function test_transaction_with_discount_and_tax()
    {
        Log::info('Testing transaction with discount and tax');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 2,
                    'catatan' => 'With discount and tax'
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 165000, // (150000 - 10000) + 25000 (tax)
                    'referensi' => 'CASH003'
                ]
            ],
            'diskon' => 10000, // 10k discount
            'pajak' => 25000, // 25k tax
            'catatan' => 'Discount and tax test'
        ]);

        $response->assertStatus(200);

        // Verify calculations
        $transaction = $response->json();
        $this->assertEquals(150000, $transaction['subtotal']); // 2 * 75000
        $this->assertEquals(10000, $transaction['diskon']);
        $this->assertEquals(25000, $transaction['pajak']);
        $this->assertEquals(165000, $transaction['total']); // 150000 - 10000 + 25000

        Log::info('Transaction with discount and tax completed successfully');
    }

    /**
     * Test transaction with QRIS payment
     */
    public function test_transaction_with_qris_payment()
    {
        Log::info('Testing transaction with QRIS payment');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 1,
                    'catatan' => 'QRIS payment'
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'qris',
                    'jumlah' => 75000,
                    'referensi' => 'QRIS123456789'
                ]
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'QRIS payment test'
        ]);

        $response->assertStatus(200);

        // Verify payment method
        $pembayaran = $response->json('pembayaran');
        $this->assertCount(1, $pembayaran);
        $this->assertEquals('qris', $pembayaran[0]['metode']);
        $this->assertEquals('QRIS123456789', $pembayaran[0]['referensi']);

        Log::info('QRIS payment transaction completed successfully');
    }

    /**
     * Test transaction with insufficient stok
     */
    public function test_transaction_with_insufficient_stok()
    {
        Log::info('Testing transaction with insufficient stok');

        // Try to buy more than available stok
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 150, // More than available 100
                    'catatan' => 'Insufficient stok test'
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 11250000, // 150 * 75000
                    'referensi' => 'CASH004'
                ]
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'Should fail due to insufficient stok'
        ]);

        $response->assertStatus(400)
                ->assertJsonStructure(['error']);

        Log::info('Transaction with insufficient stok correctly rejected');
    }

    /**
     * Test transaction with closed shift
     */
    public function test_transaction_with_closed_shift()
    {
        Log::info('Testing transaction with closed shift');

        // Close the shift first
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->putJson('/api/pos/shift/' . $this->shift['id'] . '/tutup', [
            'saldo_akhir' => 600000
        ]);

        // Try to create transaction with closed shift
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 1,
                    'catatan' => 'Closed shift test'
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 75000,
                    'referensi' => 'CASH005'
                ]
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'Should fail due to closed shift'
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Shift tidak terbuka'
                ]);

        Log::info('Transaction with closed shift correctly rejected');
    }

    /**
     * Test transaction with another user's shift
     */
    public function test_transaction_with_other_user_shift()
    {
        Log::info('Testing transaction with other user shift');

        // Create another user and shift
        $otherUser = User::create([
            'name' => 'Other Kasir',
            'email' => 'other@test.com',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true
        ]);

        $otherUser->cabang()->attach($this->cabang->id);

        $otherShift = Shift::create([
            'user_id' => $otherUser->id,
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 400000,
            'status' => 'buka'
        ]);

        // Try to create transaction with other user's shift
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $otherShift->id,
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 1,
                    'catatan' => 'Other user shift test'
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 75000,
                    'referensi' => 'CASH006'
                ]
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'Should fail due to unauthorized shift'
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'error' => 'Tidak memiliki akses'
                ]);

        Log::info('Transaction with other user shift correctly rejected');
    }

    /**
     * Test transaction validation
     */
    public function test_transaction_validation()
    {
        Log::info('Testing transaction validation');

        // Test missing shift_id
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 1
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 75000
                ]
            ]
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['shift_id']);

        // Test missing items
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 75000
                ]
            ]
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['items']);

        // Test missing pembayaran
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 1
                ]
            ]
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['pembayaran']);

        Log::info('Transaction validation tests completed');
    }

    /**
     * Test getting transaction list
     */
    public function test_get_transaction_list()
    {
        Log::info('Testing get transaction list');

        // Create a transaction first
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $this->shift['id'],
            'items' => [
                [
                    'produk_id' => $this->produk->id,
                    'jumlah' => 1,
                    'catatan' => 'Test transaction'
                ]
            ],
            'pembayaran' => [
                [
                    'metode' => 'tunai',
                    'jumlah' => 75000,
                    'referensi' => 'CASH007'
                ]
            ],
            'diskon' => 0,
            'pajak' => 0,
            'catatan' => 'List test'
        ]);

        // Get transaction list
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/shift/' . $this->shift['id'] . '/transaksi');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'nomor_invoice', 'total', 'status', 'created_at']
                    ],
                    'current_page',
                    'last_page',
                    'total'
                ]);

        Log::info('Transaction list retrieved successfully');
    }
}