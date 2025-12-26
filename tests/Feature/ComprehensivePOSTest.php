<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\Shift;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\PerformanceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ComprehensivePOSTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Cabang $cabang;
    private Kategori $kategori;
    private string $token;
    private PerformanceMonitoringService $performanceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->performanceService = new PerformanceMonitoringService();
        
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
    public function test_complete_pos_workflow()
    {
        Log::info('Starting complete POS workflow test');
        
        $report = [
            'test_name' => 'Complete POS Workflow',
            'start_time' => now()->toDateTimeString(),
            'results' => []
        ];

        // Step 1: Login validation
        $startTime = microtime(true);
        $loginResponse = $this->postJson('/api/pos/auth/login', [
            'email' => 'kasir@test.com',
            'password' => 'password'
        ]);
        $loginTime = (microtime(true) - $startTime) * 1000;
        
        $report['results']['login'] = [
            'status' => $loginResponse->status(),
            'response_time_ms' => $loginTime,
            'token_generated' => !empty($loginResponse->json('access_token')),
            'user_data' => $loginResponse->json('user') !== null
        ];

        $this->performanceService->trackApiResponse(
            '/api/pos/auth/login', 
            $loginTime, 
            $loginResponse->status()
        );

        // Step 2: Branch selection and data loading
        $startTime = microtime(true);
        $cabangResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/cabang');
        $cabangTime = (microtime(true) - $startTime) * 1000;
        
        $report['results']['cabang_loading'] = [
            'status' => $cabangResponse->status(),
            'response_time_ms' => $cabangTime,
            'cabang_count' => count($cabangResponse->json('data') ?? [])
        ];

        // Step 3: Shift opening
        $startTime = microtime(true);
        $shiftResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000.00
        ]);
        $shiftTime = (microtime(true) - $startTime) * 1000;
        
        $report['results']['shift_opening'] = [
            'status' => $shiftResponse->status(),
            'response_time_ms' => $shiftTime,
            'shift_created' => $shiftResponse->status() === 200,
            'saldo_validated' => $shiftResponse->json('shift.saldo_awal') === 100000.00
        ];

        $this->performanceService->trackApiResponse(
            '/api/pos/shift/buka', 
            $shiftTime, 
            $shiftResponse->status()
        );

        // Step 4: Product loading
        // Create test products
        Produk::factory()->count(50)->create([
            'kategori_id' => $this->kategori->id
        ]);

        $startTime = microtime(true);
        $produkResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/pos/produk?page=1&per_page=20');
        $produkTime = (microtime(true) - $startTime) * 1000;
        
        $report['results']['product_loading'] = [
            'status' => $produkResponse->status(),
            'response_time_ms' => $produkTime,
            'products_loaded' => count($produkResponse->json('data') ?? []),
            'pagination_working' => $produkResponse->json('current_page') === 1
        ];

        $this->performanceService->trackApiResponse(
            '/api/pos/produk', 
            $produkTime, 
            $produkResponse->status()
        );

        // Step 5: Transaction creation
        $products = Produk::take(3)->get();
        $startTime = microtime(true);
        $transaksiResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => $shiftResponse->json('shift.id'),
            'items' => $products->map(function ($product) {
                return [
                    'produk_id' => $product->id,
                    'jumlah' => 2
                ];
            })->toArray(),
            'pembayaran' => [
                ['metode' => 'tunai', 'jumlah' => 150000]
            ]
        ]);
        $transaksiTime = (microtime(true) - $startTime) * 1000;
        
        $report['results']['transaction_creation'] = [
            'status' => $transaksiResponse->status(),
            'response_time_ms' => $transaksiTime,
            'transaction_created' => $transaksiResponse->status() === 200,
            'invoice_generated' => !empty($transaksiResponse->json('transaksi.nomor_invoice'))
        ];

        $this->performanceService->trackApiResponse(
            '/api/pos/transaksi', 
            $transaksiTime, 
            $transaksiResponse->status()
        );

        // Step 6: Shift closing
        $startTime = microtime(true);
        $closeShiftResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/shift/' . $shiftResponse->json('shift.id') . '/tutup', [
            'saldo_akhir' => 120000.00,
            'total_tunai' => 150000,
            'total_qris' => 0
        ]);
        $closeShiftTime = (microtime(true) - $startTime) * 1000;
        
        $report['results']['shift_closing'] = [
            'status' => $closeShiftResponse->status(),
            'response_time_ms' => $closeShiftTime,
            'shift_closed' => $closeShiftResponse->status() === 200
        ];

        // Final report
        $report['end_time'] = now()->toDateTimeString();
        $report['total_time'] = array_sum(array_column($report['results'], 'response_time_ms'));
        $report['success_rate'] = $this->calculateSuccessRate($report['results']);

        Log::info('Complete POS Workflow Test Results', $report);

        // Assertions
        $this->assertGreaterThan(80, $report['success_rate'], 'Success rate should be > 80%');
        $this->assertLessThan(10000, $report['total_time'], 'Total workflow time should be < 10 seconds');

        return $report;
    }

    /** @test */
    public function test_error_scenarios()
    {
        Log::info('Testing error scenarios');
        
        $report = [
            'test_name' => 'Error Scenarios',
            'scenarios' => []
        ];

        // Scenario 1: Invalid login credentials
        $response = $this->postJson('/api/pos/auth/login', [
            'email' => 'invalid@email.com',
            'password' => 'wrongpassword'
        ]);
        
        $report['scenarios']['invalid_login'] = [
            'status' => $response->status(),
            'expected_error' => $response->status() === 401,
            'error_message' => $response->json('message')
        ];

        // Scenario 2: Opening shift without authentication
        $response = $this->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000
        ]);
        
        $report['scenarios']['unauthorized_shift'] = [
            'status' => $response->status(),
            'expected_error' => $response->status() === 401
        ];

        // Scenario 3: Invalid saldo_awal format
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 'invalid_number'
        ]);
        
        $report['scenarios']['invalid_saldo'] = [
            'status' => $response->status(),
            'expected_error' => $response->status() === 422,
            'validation_error' => $response->json('error.saldo_awal') !== null
        ];

        // Scenario 4: Creating transaction without active shift
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/pos/transaksi', [
            'shift_id' => 99999,
            'items' => [],
            'pembayaran' => []
        ]);
        
        $report['scenarios']['invalid_shift'] = [
            'status' => $response->status(),
            'expected_error' => $response->status() === 400
        ];

        Log::info('Error Scenarios Test Results', $report);

        // Assertions
        $this->assertTrue($report['scenarios']['invalid_login']['expected_error']);
        $this->assertTrue($report['scenarios']['unauthorized_shift']['expected_error']);
        $this->assertTrue($report['scenarios']['invalid_saldo']['expected_error']);
    }

    /** @test */
    public function test_performance_under_load()
    {
        Log::info('Testing performance under load');
        
        // Create test data
        Produk::factory()->count(100)->create([
            'kategori_id' => $this->kategori->id
        ]);

        $results = [];
        
        // Test multiple concurrent operations
        for ($i = 0; $i < 10; $i++) {
            $startTime = microtime(true);
            
            // Simulate concurrent requests
            $responses = [
                'products' => $this->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                ])->getJson('/api/pos/produk?page=1&per_page=20'),
                
                'cabang' => $this->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                ])->getJson('/api/pos/cabang'),
                
                'active_shift' => $this->withHeaders([
                    'Authorization' => 'Bearer ' . $this->token,
                ])->getJson('/api/pos/shift/aktif')
            ];
            
            $endTime = microtime(true);
            $totalTime = ($endTime - $startTime) * 1000;
            
            $results[] = [
                'iteration' => $i + 1,
                'total_time_ms' => $totalTime,
                'all_success' => collect($responses)->every(fn($res) => $res->status() === 200)
            ];
        }

        $avgTime = collect($results)->avg('total_time_ms');
        $successRate = collect($results)->where('all_success', true)->count() / count($results) * 100;

        $report = [
            'test_name' => 'Performance Under Load',
            'iterations' => $results,
            'average_time_ms' => $avgTime,
            'success_rate' => $successRate
        ];

        Log::info('Performance Under Load Test Results', $report);

        // Assertions
        $this->assertLessThan(3000, $avgTime, 'Average response time should be < 3 seconds');
        $this->assertGreaterThan(90, $successRate, 'Success rate should be > 90%');
    }

    private function calculateSuccessRate(array $results): float
    {
        $total = count($results);
        $successful = collect($results)->filter(function ($result) {
            return isset($result['status']) && $result['status'] === 200;
        })->count();
        
        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }
}