<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cabang;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShiftControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected $user;
    protected $cabang;
    protected $token;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'kasir'
        ]);
        
        $this->cabang = Cabang::factory()->create([
            'aktif' => true
        ]);
        
        $this->user->cabang()->attach($this->cabang->id);
        
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }
    
    /** @test */
    public function test_buka_shift_success()
    {
        Log::fake();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000.00
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'shift' => [
                    'id', 'user_id', 'cabang_id', 'saldo_awal', 'status'
                ]
            ]);
        
        $this->assertDatabaseHas('shift', [
            'user_id' => $this->user->id,
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000.00,
            'status' => 'buka'
        ]);
        
        Log::assertLogged('info', function ($message, $context) {
            return str_contains($message, 'Shift berhasil dibuka');
        });
    }
    
    /** @test */
    public function test_buka_shift_with_invalid_saldo_awal()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => -1000
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['saldo_awal']);
    }
    
    /** @test */
    public function test_buka_shift_with_null_saldo_awal()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => null
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['saldo_awal']);
    }
    
    /** @test */
    public function test_buka_shift_with_invalid_cabang_id()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/shift/buka', [
            'cabang_id' => 99999,
            'saldo_awal' => 100000.00
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cabang_id']);
    }
    
    /** @test */
    public function test_buka_shift_duplicate_active_shift()
    {
        // Buat shift aktif terlebih dahulu
        Shift::create([
            'user_id' => $this->user->id,
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 50000.00,
            'waktu_buka' => now(),
            'status' => 'buka'
        ]);
        
        // Coba buka shift lagi
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000.00
        ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'User sudah memiliki shift yang masih buka'
            ]);
    }
    
    /** @test */
    public function test_buka_shift_transaction_rollback_on_error()
    {
        DB::spy();
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        
        // Mock untuk memicu exception
        $this->mock(\App\Models\Shift::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \Exception('Database error'));
        });
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000.00
        ]);
        
        $response->assertStatus(500);
    }
    
    /** @test */
    public function test_ambil_shift_aktif_success()
    {
        $shift = Shift::create([
            'user_id' => $this->user->id,
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000.00,
            'waktu_buka' => now(),
            'status' => 'buka'
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/shift/aktif');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'shift' => [
                    'id', 'user_id', 'cabang_id', 'saldo_awal', 'status'
                ]
            ]);
    }
    
    /** @test */
    public function test_ambil_shift_aktif_not_found()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/shift/aktif');
        
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Tidak ada shift aktif'
            ]);
    }
    
    /** @test */
    public function test_tutup_shift_success()
    {
        $shift = Shift::create([
            'user_id' => $this->user->id,
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000.00,
            'waktu_buka' => now(),
            'status' => 'buka'
        ]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/shift/{$shift->id}/tutup", [
            'saldo_akhir' => 150000.00,
            'catatan' => 'Shift ditutup dengan baik'
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'shift' => [
                    'id', 'user_id', 'cabang_id', 'saldo_awal', 'saldo_akhir', 'status'
                ]
            ]);
        
        $this->assertDatabaseHas('shift', [
            'id' => $shift->id,
            'status' => 'tutup',
            'saldo_akhir' => 150000.00
        ]);
    }
    
    /** @test */
    public function test_audit_trail_created()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/shift/buka', [
            'cabang_id' => $this->cabang->id,
            'saldo_awal' => 100000.00
        ]);
        
        $response->assertStatus(200);
        
        $shift = Shift::find($response->json('shift.id'));
        $this->assertNotNull($shift->audit_log);
        $this->assertCount(1, $shift->audit_log);
        $this->assertEquals('buka_shift', $shift->audit_log[0]['action']);
    }
}