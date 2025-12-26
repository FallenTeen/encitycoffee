<?php

namespace Tests\Feature\Mobile;

use App\Models\User;
use App\Models\Cabang;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class MobileShiftTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $cabang;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->cabang = Cabang::create([
            'kode' => 'CBG001',
            'nama' => 'Cabang Test',
            'alamat' => 'Jl. Test No. 1',
            'telepon' => '08123456789',
            'aktif' => true
        ]);

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
     * Test successful shift opening
     */
    public function test_successful_shift_opening()
    {
        Log::info('Testing successful shift opening', [
            'user_id' => $this->user->id,
            'cabang_id' => $this->cabang->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 500000
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'shift' => ['id', 'user_id', 'cabang_id', 'saldo_awal', 'status']
                ]);

        // Verify shift was created
        $this->assertDatabaseHas('shift', [
            'user_id' => $this->user->id,
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 500000,
            'status' => 'buka'
        ]);

        Log::info('Shift opened successfully', ['shift_id' => $response->json('shift.id')]);
    }

    /**
     * Test shift opening with invalid cabang
     */
    public function test_shift_opening_with_invalid_cabang()
    {
        Log::info('Testing shift opening with invalid cabang');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => 999, // Invalid cabang ID
            'saldo_awal' => 500000
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Cabang tidak ditemukan'
                ]);

        Log::info('Invalid cabang correctly rejected');
    }

    /**
     * Test shift opening with cabang not assigned to user
     */
    public function test_shift_opening_with_unauthorized_cabang()
    {
        // Create another cabang not assigned to user
        $otherCabang = Cabang::create([
            'kode' => 'CBG002',
            'nama' => 'Cabang Lain',
            'alamat' => 'Jl. Lain No. 1',
            'telepon' => '08123456780',
            'aktif' => true
        ]);

        Log::info('Testing shift opening with unauthorized cabang', [
            'cabang_id' => $otherCabang->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $otherCabang->id,
            'saldo_awal' => 500000
        ]);

        $response->assertStatus(403)
                ->assertJsonFragment([
                    'Tidak memiliki akses ke cabang ini'
                ]);

        Log::info('Unauthorized cabang correctly rejected');
    }

    /**
     * Test opening duplicate shift
     */
    public function test_duplicate_shift_opening()
    {
        Log::info('Testing duplicate shift opening');

        // Open first shift
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 500000
        ]);

        // Try to open another shift
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 600000
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'User sudah memiliki shift yang masih buka'
                ]);

        Log::info('Duplicate shift opening correctly prevented');
    }

    /**
     * Test shift validation
     */
    public function test_shift_validation()
    {
        Log::info('Testing shift validation');

        // Test missing cabang_id
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'saldo_awal' => 500000
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure(['error']);

        // Test missing saldo_awal
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure(['error']);

        // Test invalid saldo_awal (negative)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => -1000
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure(['error']);

        Log::info('Shift validation tests completed');
    }

    /**
     * Test getting active shift
     */
    public function test_get_active_shift()
    {
        Log::info('Testing get active shift');

        // Open a shift first
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 500000
        ]);

        // Get active shift
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/shift/aktif');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'shift' => ['id', 'user_id', 'cabang_id', 'saldo_awal', 'status', 'cabang', 'user']
                ]);

        Log::info('Active shift retrieved successfully');
    }

    /**
     * Test getting active shift when none exists
     */
    public function test_get_active_shift_when_none_exists()
    {
        Log::info('Testing get active shift when none exists');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/pos/shift/aktif');

        $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Tidak ada shift aktif'
                ]);

        Log::info('No active shift correctly reported');
    }

    /**
     * Test unauthorized access to shift endpoints
     */
    public function test_unauthorized_shift_access()
    {
        Log::info('Testing unauthorized shift access');

        // Test without token
        $response = $this->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 500000
        ]);

        $response->assertStatus(401);

        // Test with invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token'
        ])->postJson('/api/pos/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 500000
        ]);

        $response->assertStatus(401);

        Log::info('Unauthorized access correctly rejected');
    }
}