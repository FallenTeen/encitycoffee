<?php

use App\Models\User;
use App\Models\Cabang;
use App\Models\Shift;
use App\Models\Transaksi;
use Illuminate\Support\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function seedTransaksiForPeriod(): array {
    $user = User::factory()->create(['role' => 'it_support']);
    $cabang = Cabang::create(['kode' => 'CBG', 'nama' => 'Toko Pusat', 'aktif' => true]);
    $shift = Shift::create([
        'user_id' => $user->id,
        'cabang_id' => $cabang->id,
        'waktu_buka' => Carbon::now()->startOfDay(),
        'status' => 'buka',
        'nama_kasir' => $user->name,
        'saldo_awal' => 0,
        'saldo_diharapkan' => 0,
        'total_tunai' => 0,
        'total_qris' => 0,
    ]);
    // Hari ini
    $t1 = Transaksi::create([
        'shift_id' => $shift->id, 'cabang_id' => $cabang->id, 'user_id' => $user->id,
        'nomor_invoice' => 'INV-TODAY', 'subtotal' => 10000, 'total' => 10000, 'status' => 'selesai',
    ]);
    \DB::table('transaksi')->where('id', $t1->id)->update(['created_at' => Carbon::today()->setHour(10)]);
    // Kemarin
    $t2 = Transaksi::create([
        'shift_id' => $shift->id, 'cabang_id' => $cabang->id, 'user_id' => $user->id,
        'nomor_invoice' => 'INV-YEST', 'subtotal' => 20000, 'total' => 20000, 'status' => 'selesai',
    ]);
    \DB::table('transaksi')->where('id', $t2->id)->update(['created_at' => Carbon::yesterday()->setHour(10)]);
    // 8 hari lalu
    $t3 = Transaksi::create([
        'shift_id' => $shift->id, 'cabang_id' => $cabang->id, 'user_id' => $user->id,
        'nomor_invoice' => 'INV-OLD', 'subtotal' => 30000, 'total' => 30000, 'status' => 'selesai',
    ]);
    \DB::table('transaksi')->where('id', $t3->id)->update(['created_at' => Carbon::today()->subDays(8)->setHour(10)]);
    return [$user, $cabang, $shift];
}

test('filter hari ini hanya menampilkan transaksi hari ini', function () {
    [$user] = seedTransaksiForPeriod();
    $this->actingAs($user);
    $today = Carbon::today()->toDateString();
    $response = $this->get('/transaksi?tanggal_mulai='.$today.'&tanggal_selesai='.$today);
    $response->assertOk();
    $response->assertSee('INV-TODAY');
    $response->assertDontSee('INV-YEST');
    $response->assertDontSee('INV-OLD');
});

test('filter 7 hari terakhir range benar', function () {
    [$user] = seedTransaksiForPeriod();
    $this->actingAs($user);
    $start = Carbon::today()->subDays(6)->toDateString();
    $end = Carbon::today()->toDateString();
    $response = $this->get('/transaksi?tanggal_mulai='.$start.'&tanggal_selesai='.$end);
    $response->assertOk();
    $response->assertSee('INV-TODAY');
    $response->assertSee('INV-YEST');
    $response->assertDontSee('INV-OLD');
});

test('filter custom invalid ditolak (end < start)', function () {
    [$user] = seedTransaksiForPeriod();
    $this->actingAs($user);
    $start = Carbon::today()->toDateString();
    $end = Carbon::yesterday()->toDateString();
    $response = $this->get('/transaksi?tanggal_mulai='.$start.'&tanggal_selesai='.$end);
    $response->assertSessionHasErrors(['tanggal_selesai']);
});

test('hari tanpa transaksi menampilkan pesan di export', function () {
    [$user] = seedTransaksiForPeriod();
    $this->actingAs($user);
    $start = Carbon::today()->subDays(30)->toDateString();
    $end = Carbon::today()->subDays(25)->toDateString();
    $response = $this->get('/transaksi/export-pdf?tanggal_mulai='.$start.'&tanggal_selesai='.$end);
    $response->assertOk();
    $response->assertSee('Tidak ada transaksi pada periode ini');
});

test('export PDF menghormati filter aktif', function () {
    [$user] = seedTransaksiForPeriod();
    $this->actingAs($user);
    $today = Carbon::today()->toDateString();
    $response = $this->get('/transaksi/export-pdf?tanggal_mulai='.$today.'&tanggal_selesai='.$today);
    $response->assertOk();
    $response->assertSee('INV-TODAY');
    $response->assertDontSee('INV-YEST');
});

test('export Excel berisi header dan data', function () {
    [$user] = seedTransaksiForPeriod();
    $this->actingAs($user);
    $today = Carbon::today()->toDateString();
    $response = $this->get('/transaksi/export-excel?tanggal_mulai='.$today.'&tanggal_selesai='.$today);
    $response->assertOk();
    $response->assertHeader('Content-Disposition');
    $response->assertSee('Invoice,Cabang,Kasir,Total,Status,Waktu');
    $response->assertSee('INV-TODAY');
});

test('ganti filter cepat berurutan tidak menimbulkan race (server returns latest)', function () {
    [$user] = seedTransaksiForPeriod();
    $this->actingAs($user);
    $start1 = Carbon::today()->toDateString();
    $end1 = Carbon::today()->toDateString();
    $start2 = Carbon::today()->subDays(6)->toDateString();
    $end2 = Carbon::today()->toDateString();
    // First narrow filter
    $r1 = $this->get('/transaksi?per_page=15&tanggal_mulai='.$start1.'&tanggal_selesai='.$end1);
    $r1->assertOk();
    // Then broader filter
    $r2 = $this->get('/transaksi?per_page=15&tanggal_mulai='.$start2.'&tanggal_selesai='.$end2);
    $r2->assertOk();
    $r2->assertSee('INV-TODAY');
    $r2->assertSee('INV-YEST');
});

test('filter tanggal masa depan ditolak', function () {
    [$user] = seedTransaksiForPeriod();
    $this->actingAs($user);
    $future = Carbon::today()->addDays(1)->toDateString();
    $response = $this->get('/transaksi?tanggal_mulai='.$future.'&tanggal_selesai='.$future);
    $response->assertSessionHasErrors(['tanggal_mulai']);
});
