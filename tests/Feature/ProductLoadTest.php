<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProductLoadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Cabang $cabang;
    private Kategori $kategori;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create([
            'role' => 'kasir',
            'email' => 'kasir@test.com'
        ]);
        
        $this->cabang = Cabang::factory()->create([
            'nama' => 'Cabang Test',
            'aktif' => true
        ]);
        
        $this->kategori = Kategori::factory()->create([
            'nama' => 'Kategori Test'
        ]);

        // Login and get token
        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@test.com',
            'password' => 'password'
        ]);

        $this->token = $response->json('access_token');
    }

    /** @test */
    public function test_load_products_with_1000_items()
    {
        Log::info('Starting load test with 1000 products');
        
        // Create 1000 products
        $products = [];
        for ($i = 1; $i <= 1000; $i++) {
            $products[] = [
                'nama' => "Produk $i",
                'sku' => "SKU$i",
                'kategori_id' => $this->kategori->id,
                'harga_jual' => rand(10000, 100000),
                'harga_beli' => rand(5000, 50000),
                'deskripsi' => "Deskripsi produk $i",
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            if ($i % 100 === 0) {
                Produk::insert(array_splice($products, -100));
                Log::info("Created $i products");
            }
        }

        // Create stock for each product at branch
        foreach (Produk::all() as $produk) {
            $produk->stokEtalase()->create([
                'cabang_id' => $this->cabang->id,
                'stok' => rand(10, 100),
                'stok_minimal' => 5
            ]);
        }

        Log::info('All products created, starting performance tests');

        // Test 1: Load all products with pagination
        $startTime = microtime(true);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?page=1&per_page=50');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nama', 'sku', 'harga_jual', 'harga_beli']
            ],
            'current_page',
            'last_page',
            'total'
        ]);

        $this->assertLessThan(2000, $responseTime, 
            "Response time should be less than 2 seconds, got: {$responseTime}ms");
        
        Log::info("First page loaded in {$responseTime}ms");

        // Test 2: Search functionality
        $startTime = microtime(true);
        $searchResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?search=Produk%20500');
        
        $endTime = microtime(true);
        $searchTime = ($endTime - $startTime) * 1000;
        
        $searchResponse->assertStatus(200);
        $this->assertLessThan(1000, $searchTime, 
            "Search should be less than 1 second, got: {$searchTime}ms");
        
        Log::info("Search completed in {$searchTime}ms");

        // Test 3: Filter by category
        $startTime = microtime(true);
        $categoryResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?kategori_id=' . $this->kategori->id);
        
        $endTime = microtime(true);
        $categoryTime = ($endTime - $startTime) * 1000;
        
        $categoryResponse->assertStatus(200);
        $this->assertLessThan(1500, $categoryTime, 
            "Category filter should be less than 1.5 seconds, got: {$categoryTime}ms");
        
        Log::info("Category filter completed in {$categoryTime}ms");

        // Test 4: Cache performance
        Cache::flush();
        
        // First request (no cache)
        $startTime = microtime(true);
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?page=1&per_page=50');
        $firstRequestTime = (microtime(true) - $startTime) * 1000;
        
        // Second request (with cache)
        $startTime = microtime(true);
        $cachedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?page=1&per_page=50');
        $cachedTime = (microtime(true) - $startTime) * 1000;
        
        Log::info("First request: {$firstRequestTime}ms, Cached request: {$cachedTime}ms");
        $this->assertLessThan($firstRequestTime * 0.5, $cachedTime, 
            "Cached request should be at least 50% faster");

        // Test 5: Memory usage
        $memoryBefore = memory_get_usage(true);
        
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?page=1&per_page=100');
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB
        
        $this->assertLessThan(50, $memoryUsed, 
            "Memory usage should be less than 50MB, got: {$memoryUsed}MB");
        
        Log::info("Memory usage: {$memoryUsed}MB");
    }

    /** @test */
    public function test_concurrent_product_requests()
    {
        Log::info('Testing concurrent requests');
        
        // Create 100 products for this test
        Produk::factory()->count(100)->create([
            'kategori_id' => $this->kategori->id
        ]);

        $startTime = microtime(true);
        
        // Simulate 10 concurrent requests
        $promises = [];
        for ($i = 1; $i <= 10; $i++) {
            $promises[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
            ])->getJson('/api/pos/produk?page=' . $i . '&per_page=10');
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        Log::info("10 concurrent requests completed in {$totalTime}ms");
        
        // Each request should still be reasonably fast even under load
        $this->assertLessThan(5000, $totalTime, 
            "All concurrent requests should complete within 5 seconds");
    }

    /** @test */
    public function test_product_detail_performance()
    {
        Log::info('Testing product detail performance');
        
        // Create product with relationships
        $produk = Produk::factory()->create([
            'kategori_id' => $this->kategori->id,
            'nama' => 'Test Product Detail',
            'sku' => 'TEST-SKU-001'
        ]);

        $produk->stokEtalase()->create([
            'cabang_id' => $this->cabang->id,
            'stok' => 50,
            'stok_minimal' => 5
        ]);

        $startTime = microtime(true);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk/' . $produk->id);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id', 'nama', 'sku', 'harga_jual', 'harga_beli',
            'kategori', 'stok_etalase'
        ]);

        $this->assertLessThan(500, $responseTime, 
            "Product detail should load in less than 500ms, got: {$responseTime}ms");
        
        Log::info("Product detail loaded in {$responseTime}ms");
    }

    /** @test */
    public function test_error_handling_under_load()
    {
        Log::info('Testing error handling under load');
        
        // Test with invalid parameters
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?page=invalid&per_page=abc');
        
        $response->assertStatus(400);
        
        // Test with non-existent category
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?kategori_id=99999');
        
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('total'));
        
        // Test without authentication
        $response = $this->getJson('/api/pos/produk');
        $response->assertStatus(401);
    }
}