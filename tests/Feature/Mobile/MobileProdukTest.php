<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\StokEtalase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class MobileProdukTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $cabang;
    protected $token;
    protected $produk;
    protected $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create kategori
        $this->kategori = KategoriProduk::create([
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

        // Create another cabang for testing
        $otherCabang = Cabang::create([
            'kode' => 'CBG002',
            'nama' => 'Cabang Lain',
            'alamat' => 'Jl. Lain No. 1',
            'telepon' => '08123456780',
            'aktif' => true
        ]);

        // Create produk
        $this->produk = Produk::create([
            'sku' => 'SKU001',
            'nama' => 'Kopi Arabica',
            'kategori_id' => $this->kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 50000,
            'harga_jual' => 75000,
            'aktif' => true
        ]);

        // Create another produk
        $produk2 = Produk::create([
            'sku' => 'SKU002',
            'nama' => 'Kopi Robusta',
            'kategori_id' => $this->kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 40000,
            'harga_jual' => 65000,
            'aktif' => true
        ]);

        // Create stok for user's cabang
        StokEtalase::create([
            'produk_id' => $this->produk->id,
            'cabang_id' => $this->cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 100,
            'stok_minimum' => 10
        ]);

        StokEtalase::create([
            'produk_id' => $produk2->id,
            'cabang_id' => $this->cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 50,
            'stok_minimum' => 5
        ]);

        // Create stok for other cabang
        StokEtalase::create([
            'produk_id' => $this->produk->id,
            'cabang_id' => $otherCabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 200,
            'stok_minimum' => 20
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
    }

    /**
     * Test getting produk for assigned cabang
     */
    public function test_get_produk_for_assigned_cabang()
    {
        Log::info('Testing get produk for assigned cabang', [
            'user_id' => $this->user->id,
            'cabang_id' => $this->cabang->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/produk/mobile');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'produk' => [
                        '*' => ['id', 'sku', 'nama', 'tipe', 'harga_jual', 'kategori_id', 'stok_etalase']
                    ],
                    'kategori_list',
                    'total'
                ])
                ->assertJson([
                    'success' => true
                ]);

        // Verify only produk with stok in user's cabang are returned
        $produkData = $response->json('produk');
        $this->assertCount(2, $produkData); // Should have 2 produk

        // Verify stok information
        foreach ($produkData as $produk) {
            $this->assertNotEmpty($produk['stok_etalase']);
            // Since we're filtering by cabang, all returned products should have stock in this cabang
            $this->assertNotNull($produk['stok_etalase']);
        }

        Log::info('Produk retrieved successfully for assigned cabang');
    }

    /**
     * Test produk search functionality
     */
    public function test_produk_search()
    {
        Log::info('Testing produk search functionality');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/produk/mobile?search=Arabica');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $produkData = $response->json('produk');
        $this->assertCount(1, $produkData);
        $this->assertEquals('Kopi Arabica', $produkData[0]['nama']);

        Log::info('Produk search working correctly');
    }

    /**
     * Test produk filtering by kategori
     */
    public function test_produk_filter_by_kategori()
    {
        Log::info('Testing produk filter by kategori');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/produk/mobile?kategori_id=' . $this->kategori->id);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $produkData = $response->json('produk');
        $this->assertCount(2, $produkData); // Both produk are in the same kategori

        foreach ($produkData as $produk) {
            $this->assertEquals($this->kategori->id, $produk['kategori_id']);
        }

        Log::info('Produk filter by kategori working correctly');
    }

    /**
     * Test produk filtering by tipe
     */
    public function test_produk_filter_by_tipe()
    {
        Log::info('Testing produk filter by tipe');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/produk/mobile?tipe=beans');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $produkData = $response->json('produk');
        $this->assertCount(2, $produkData); // Both produk are beans type

        foreach ($produkData as $produk) {
            $this->assertEquals('beans', $produk['tipe']);
        }

        Log::info('Produk filter by tipe working correctly');
    }

    /**
     * Test stok rendah detection
     */
    public function test_stok_rendah_detection()
    {
        Log::info('Testing stok rendah detection');

        // Create produk with low stok
        $lowStokProduk = Produk::create([
            'sku' => 'SKU003',
            'nama' => 'Kopi Premium',
            'kategori_id' => $this->kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 60000,
            'harga_jual' => 85000,
            'aktif' => true
        ]);

        StokEtalase::create([
            'produk_id' => $lowStokProduk->id,
            'cabang_id' => $this->cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 3, // Below minimum of 10
            'stok_minimum' => 10
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/produk/mobile');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $produkData = $response->json('produk');
        
        // Find the low stok produk
        $lowStokData = collect($produkData)->firstWhere('id', $lowStokProduk->id);
        $this->assertNotNull($lowStokData);
        
        // Verify stok rendah is detected
        $this->assertNotNull($lowStokData['stok_etalase']);
        $this->assertEquals(3, $lowStokData['stok_etalase']['jumlah']);
        $this->assertEquals(10, $lowStokData['stok_etalase']['stok_minimum']);

        Log::info('Stok rendah detection working correctly');
    }

    /**
     * Test unauthorized access to produk
     */
    public function test_unauthorized_produk_access()
    {
        Log::info('Testing unauthorized produk access');

        // Test without token
        $response = $this->getJson('/api/pos/produk/mobile');
        $response->assertStatus(401);

        // Test with invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->getJson('/api/pos/produk/mobile');
        $response->assertStatus(401);

        Log::info('Unauthorized access correctly rejected');
    }

    /**
     * Test produk pagination
     */
    public function test_produk_pagination()
    {
        Log::info('Testing produk pagination');

        // Create many produk
        for ($i = 4; $i <= 20; $i++) {
            $produk = Produk::create([
                'sku' => 'SKU' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nama' => 'Kopi ' . $i,
                'kategori_id' => $this->kategori->id,
                'tipe' => 'beans',
                'harga_beli' => 50000,
                'harga_jual' => 75000,
                'aktif' => true
            ]);

            StokEtalase::create([
                'produk_id' => $produk->id,
                'cabang_id' => $this->cabang->id,
                'tipe_stok' => 'penjualan_retail',
                'jumlah' => 100,
                'stok_minimum' => 10
            ]);
        }

        // Test with per_page parameter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/produk/mobile?per_page=5');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);

        $produkData = $response->json('produk');
        $this->assertCount(5, $produkData); // Should have 5 items per page

        Log::info('Produk pagination working correctly');
    }

    /**
     * Test cache functionality
     */
    public function test_produk_cache()
    {
        Log::info('Testing produk cache functionality');

        // First request - should cache
        $startTime = microtime(true);
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/produk/mobile');
        $firstRequestTime = microtime(true) - $startTime;

        // Second request - should be faster due to cache
        $startTime = microtime(true);
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/produk/mobile');
        $secondRequestTime = microtime(true) - $startTime;

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Both responses should be identical
        $this->assertEquals($response1->json(), $response2->json());

        Log::info('Produk cache test completed', [
            'first_request_time' => $firstRequestTime,
            'second_request_time' => $secondRequestTime,
            'cache_improvement' => $firstRequestTime - $secondRequestTime
        ]);
    }
}