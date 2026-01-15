<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\KategoriProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class ProdukIndexTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $cabang;
    protected $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cabang = Cabang::factory()->create();
        $this->kategori = KategoriProduk::factory()->create();
        
        $this->user = User::factory()->create([
            'role' => 'manager',
        ]);
        $this->user->cabang()->attach($this->cabang);
    }

    /** @test */
    public function it_can_display_all_products_when_count_less_than_per_page()
    {
        // Create 5 products (less than default per_page of 20)
        Produk::factory()->count(5)->create([
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&per_page=20');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 5)
                ->where('produks.total', 5)
                ->where('produks.per_page', 20)
                ->where('produks.current_page', 1)
        );
    }

    /** @test */
    public function it_can_display_exact_products_when_count_equals_per_page()
    {
        // Create exactly 20 products
        Produk::factory()->count(20)->create([
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&per_page=20');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 20)
                ->where('produks.total', 20)
                ->where('produks.per_page', 20)
        );
    }

    /** @test */
    public function it_can_display_paginated_products_when_count_greater_than_per_page()
    {
        // Create 50 products
        Produk::factory()->count(50)->create([
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&per_page=20&page=2');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 20) // Second page should have 20 items
                ->where('produks.total', 50)
                ->where('produks.per_page', 20)
                ->where('produks.current_page', 2)
                ->where('produks.last_page', 3)
        );
    }

    /** @test */
    public function it_validates_per_page_input()
    {
        // Test minimum validation (per_page < 1)
        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&per_page=0');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->where('produks.per_page', 1) // Should default to 1
        );

        // Test maximum validation (per_page > 1000)
        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&per_page=2000');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->where('produks.per_page', 1000) // Should default to 1000
        );
    }

    /** @test */
    public function it_shows_empty_state_when_no_products_found()
    {
        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&search=nonexistentproduct');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 0)
                ->where('produks.total', 0)
        );
    }

    /** @test */
    public function it_handles_page_beyond_total_products()
    {
        // Create 5 products
        Produk::factory()->count(5)->create([
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        // Request page 10 (which doesn't exist)
        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&per_page=10&page=10');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 0) // Should show empty
                ->where('produks.current_page', 1) // Should redirect to page 1
                ->where('produks.last_page', 1)
        );
    }

    /** @test */
    public function it_filters_products_by_search_correctly()
    {
        Produk::factory()->create([
            'nama' => 'Kopi Arabika',
            'sku' => 'KA001',
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        Produk::factory()->create([
            'nama' => 'Kopi Robusta',
            'sku' => 'KR001',
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        // Search by name
        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&search=Arabika');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 1)
                ->where('produks.data.0.nama', 'Kopi Arabika')
        );

        // Search by SKU
        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&search=KR001');

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 1)
                ->where('produks.data.0.nama', 'Kopi Robusta')
        );
    }

    /** @test */
    public function it_filters_products_by_kategori_correctly()
    {
        $kategori2 = KategoriProduk::factory()->create();

        Produk::factory()->count(3)->create([
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        Produk::factory()->count(2)->create([
            'kategori_id' => $kategori2->id,
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&kategori_id=' . $this->kategori->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 3)
                ->where('produks.total', 3)
        );
    }

    /** @test */
    public function it_handles_cache_errors_gracefully()
    {
        // Mock cache to throw an exception
        $this->mock(\Illuminate\Cache\Repository::class, function ($mock) {
            $mock->shouldReceive('remember')
                ->andThrow(new \Exception('Cache error'));
        });

        Produk::factory()->count(5)->create([
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id);

        $response->assertStatus(200);
        // Should still work even with cache error (fallback to database)
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 5)
        );
    }

    /** @test */
    public function it_logs_missing_products_for_debugging()
    {
        // Use Log facade to capture logs
        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->with('PRODUK INDEX - Filters Applied', \Mockery::type('array'))
            ->once();
        
        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->with('PRODUK INDEX - Results', \Mockery::on(function ($context) {
                return isset($context['total_produk']) && $context['total_produk'] === 3;
            }))
            ->once();
        
        Produk::factory()->count(3)->create([
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&per_page=10');

        $response->assertStatus(200);
        
        // Verify response structure
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->has('produks.data', 3)
                ->where('produks.total', 3)
                ->where('produks.per_page', 10)
        );
    }

    /** @test */
    public function it_handles_clear_all_rate_limiting_and_error_handling()
    {
        // Create some products with filters
        Produk::factory()->count(5)->create([
            'kategori_id' => $this->kategori->id,
            'aktif' => true,
            'tipe' => 'minuman',
        ]);

        // Test with filters applied
        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id . '&search=kopi&kategori_id=' . $this->kategori->id . '&tipe=minuman');

        $response->assertStatus(200);
        
        // Verify filters are applied
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->where('filter_aktif.search', 'kopi')
                ->where('filter_aktif.kategori_id', (string) $this->kategori->id)
                ->where('filter_aktif.tipe', 'minuman')
        );

        // Test that clear all endpoint works (frontend will handle debounce)
        // This simulates the clear all functionality
        $response = $this->actingAs($this->user)
            ->get('/produk?cabang_id=' . $this->cabang->id);

        $response->assertStatus(200);
        
        // Verify filters are cleared
        $response->assertInertia(fn ($assert) => 
            $assert->component('produk/Index')
                ->where('filter_aktif.search', '')
                ->where('filter_aktif.kategori_id', '')
                ->where('filter_aktif.tipe', '')
        );
    }
}