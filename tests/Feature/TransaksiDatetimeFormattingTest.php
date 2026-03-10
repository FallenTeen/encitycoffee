<?php

namespace Tests\Feature;

use App\Models\Transaksi;
use App\Models\User;
use App\Models\Cabang;
use App\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiDatetimeFormattingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cabang $cabang;
    protected Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cabang = Cabang::create([
            'kode' => 'TEST001',
            'nama' => 'Cabang Test',
            'alamat' => 'Alamat Test',
            'telepon' => '08123456789',
            'aktif' => true,
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $this->shift = Shift::create([
            'cabang_id' => $this->cabang->id,
            'user_id' => $this->user->id,
            'status' => 'buka',
            'saldo_awal' => 100000,
            'waktu_buka' => now(),
        ]);
    }

    public function test_transaksi_has_formatted_datetime_attributes()
    {
        $transaksi = Transaksi::create([
            'shift_id' => $this->shift->id,
            'cabang_id' => $this->cabang->id,
            'user_id' => $this->user->id,
            'nomor_invoice' => 'INV-001',
            'subtotal' => 50000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 50000,
            'status' => 'selesai',
            'waktu_selesai' => now(),
        ]);

        // Test waktu_selesai_formatted
        $this->assertNotNull($transaksi->waktu_selesai_formatted);
        $this->assertIsString($transaksi->waktu_selesai_formatted);
        
        // Format should be "d M Y H:i" (e.g., "10 Mar 2024 14:30")
        $this->assertMatchesRegularExpression(
            '/^\d{1,2} \w{3} \d{4} \d{1,2}:\d{2}$/',
            $transaksi->waktu_selesai_formatted
        );

        // Test created_at_formatted
        $this->assertNotNull($transaksi->created_at_formatted);
        $this->assertIsString($transaksi->created_at_formatted);
        
        // Format should be "d M Y H:i" (e.g., "10 Mar 2024 14:30")
        $this->assertMatchesRegularExpression(
            '/^\d{1,2} \w{3} \d{4} \d{1,2}:\d{2}$/',
            $transaksi->created_at_formatted
        );

        // Test updated_at_formatted
        $this->assertNotNull($transaksi->updated_at_formatted);
        $this->assertIsString($transaksi->updated_at_formatted);
        
        // Format should be "d M Y H:i" (e.g., "10 Mar 2024 14:30")
        $this->assertMatchesRegularExpression(
            '/^\d{1,2} \w{3} \d{4} \d{1,2}:\d{2}$/',
            $transaksi->updated_at_formatted
        );
    }

    public function test_waktu_selesai_formatted_returns_null_when_null()
    {
        $transaksi = Transaksi::create([
            'shift_id' => $this->shift->id,
            'cabang_id' => $this->cabang->id,
            'user_id' => $this->user->id,
            'nomor_invoice' => 'INV-002',
            'subtotal' => 50000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 50000,
            'status' => 'pending',
            'waktu_selesai' => null,
        ]);

        $this->assertNull($transaksi->waktu_selesai_formatted);
    }

    public function test_api_endpoint_includes_formatted_datetime_fields()
    {
        $transaksi = Transaksi::create([
            'shift_id' => $this->shift->id,
            'cabang_id' => $this->cabang->id,
            'user_id' => $this->user->id,
            'nomor_invoice' => 'INV-003',
            'subtotal' => 50000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 50000,
            'status' => 'selesai',
            'waktu_selesai' => now(),
        ]);

        // Use existing user and assign to same cabang as transaction
        $this->user->cabang()->attach($this->cabang->id);
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson("/api/pos/transaksi/{$transaksi->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'nomor_invoice',
            'waktu_selesai',
            'waktu_selesai_formatted',
            'created_at',
            'created_at_formatted',
            'updated_at',
            'updated_at_formatted',
        ]);

        // Verify the formatted fields are strings and match expected format
        $response->assertJsonFragment([
            'waktu_selesai_formatted' => $transaksi->waktu_selesai_formatted,
            'created_at_formatted' => $transaksi->created_at_formatted,
            'updated_at_formatted' => $transaksi->updated_at_formatted,
        ]);
    }
}