<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Shift;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTransaksiTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Cabang $cabangA;
    private Cabang $cabangB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cabangA = Cabang::factory()->create();
        $this->cabangB = Cabang::factory()->create();

        $this->manager = User::factory()->create([
            'role' => 'manager',
            'aktif' => true,
        ]);

        $this->manager->cabang()->attach([$this->cabangA->id, $this->cabangB->id]);
    }

    private function buatTransaksiSample(): array
    {
        $kasir1 = User::factory()->create([
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $kasir2 = User::factory()->create([
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $shiftA = Shift::create([
            'user_id' => $kasir1->id,
            'cabang_id' => $this->cabangA->id,
            'saldo_awal' => 100000,
            'waktu_buka' => Carbon::parse('2025-01-01 08:00:00'),
            'status' => 'buka',
        ]);

        $shiftB = Shift::create([
            'user_id' => $kasir2->id,
            'cabang_id' => $this->cabangB->id,
            'saldo_awal' => 200000,
            'waktu_buka' => Carbon::parse('2025-02-01 08:00:00'),
            'status' => 'buka',
        ]);

        $t1 = Transaksi::create([
            'shift_id' => $shiftA->id,
            'cabang_id' => $this->cabangA->id,
            'user_id' => $kasir1->id,
            'nomor_invoice' => 'INV-A-1',
            'subtotal' => 50000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 50000,
            'status' => 'selesai',
            'waktu_selesai' => Carbon::parse('2025-01-01 10:00:00'),
        ]);

        $t2 = Transaksi::create([
            'shift_id' => $shiftB->id,
            'cabang_id' => $this->cabangB->id,
            'user_id' => $kasir2->id,
            'nomor_invoice' => 'INV-B-1',
            'subtotal' => 75000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 75000,
            'status' => 'pending',
            'waktu_selesai' => Carbon::parse('2025-02-01 12:00:00'),
        ]);

        return [
            'kasir1' => $kasir1,
            'kasir2' => $kasir2,
            'shiftA' => $shiftA,
            'shiftB' => $shiftB,
            't1' => $t1,
            't2' => $t2,
        ];
    }

    public function test_laporan_transaksi_bisa_diakses_dengan_otentikasi()
    {
        $this->actingAs($this->manager);

        $this->get('/laporan/transaksi')
            ->assertStatus(200);
    }

    public function test_filter_berdasarkan_cabang()
    {
        $data = $this->buatTransaksiSample();
        $this->actingAs($this->manager);

        $query = http_build_query([
            'cabang_id' => $this->cabangA->id,
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
        ]);

        $response = $this->getJson('/laporan/transaksi?' . $query);

        $response->assertStatus(200);

        $payload = $response->json('transaksi.data');
        $ids = collect($payload)->pluck('id')->all();

        $this->assertContains($data['t1']->id, $ids);
        $this->assertNotContains($data['t2']->id, $ids);
    }

    public function test_filter_berdasarkan_shift()
    {
        $data = $this->buatTransaksiSample();
        $this->actingAs($this->manager);

        $query = http_build_query([
            'shift_id' => $data['shiftB']->id,
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
        ]);

        $response = $this->getJson('/laporan/transaksi?' . $query);

        $response->assertStatus(200);

        $payload = $response->json('transaksi.data');
        $ids = collect($payload)->pluck('id')->all();

        $this->assertContains($data['t2']->id, $ids);
        $this->assertNotContains($data['t1']->id, $ids);
    }

    public function test_filter_berdasarkan_kasir()
    {
        $data = $this->buatTransaksiSample();
        $this->actingAs($this->manager);

        $query = http_build_query([
            'user_id' => $data['kasir1']->id,
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
        ]);

        $response = $this->getJson('/laporan/transaksi?' . $query);

        $response->assertStatus(200);

        $payload = $response->json('transaksi.data');
        $ids = collect($payload)->pluck('id')->all();

        $this->assertContains($data['t1']->id, $ids);
        $this->assertNotContains($data['t2']->id, $ids);
    }

    public function test_filter_berdasarkan_status()
    {
        $data = $this->buatTransaksiSample();
        $this->actingAs($this->manager);

        $query = http_build_query([
            'status' => 'selesai',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
        ]);

        $response = $this->getJson('/laporan/transaksi?' . $query);

        $response->assertStatus(200);

        $payload = $response->json('transaksi.data');
        $ids = collect($payload)->pluck('id')->all();

        $this->assertContains($data['t1']->id, $ids);
        $this->assertNotContains($data['t2']->id, $ids);
    }

    public function test_filter_kombinasi_tanggal_dan_cabang()
    {
        $data = $this->buatTransaksiSample();
        $this->actingAs($this->manager);

        $query = http_build_query([
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-01-31',
            'cabang_id' => $this->cabangA->id,
        ]);

        $response = $this->getJson('/laporan/transaksi?' . $query);

        $response->assertStatus(200);

        $payload = $response->json('transaksi.data');
        $ids = collect($payload)->pluck('id')->all();

        $this->assertContains($data['t1']->id, $ids);
        $this->assertNotContains($data['t2']->id, $ids);
    }
}
