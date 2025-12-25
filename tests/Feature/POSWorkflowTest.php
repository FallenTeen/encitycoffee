<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Cabang;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\StokEtalase;
use App\Models\BatchStok;
use App\Models\Shift;
use App\Models\Kalibrasi;
use App\Models\MutasiStok;
use Laravel\Sanctum\Sanctum;

class POSWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_pos_flow_simulation()
    {
        // create user and cabang
        $user = User::create([
            'name' => 'Kasir Test',
            'email' => 'kasir@example.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $cabang = Cabang::create([
            'kode' => 'CBG1',
            'nama' => 'Cabang 1',
            'alamat' => 'Alamat',
            'telepon' => '08123456789',
            'aktif' => true,
        ]);

        // attach user to cabang pivot
        $user->cabang()->attach($cabang->id);

        // create kategori
        $kategori = KategoriProduk::create(['nama' => 'Minuman', 'slug' => 'minuman', 'deskripsi' => '']);

        // create products: minuman (needs kalibrasi), beans (retail), snack
        $produkMinuman = Produk::create([
            'kategori_id' => $kategori->id,
            'sku' => 'MIN-001',
            'nama' => 'Espresso',
            'deskripsi' => 'Minuman espresso',
            'tipe' => 'minuman',
            'satuan_dasar' => 'cup',
            'harga_modal' => 5000,
            'harga_jual' => 15000,
            'aktif' => true,
            'perlu_kalibrasi' => true,
        ]);

        $produkBeans = Produk::create([
            'kategori_id' => $kategori->id,
            'sku' => 'BEANS-01',
            'nama' => 'Beans Arabica',
            'deskripsi' => 'Kopi beans',
            'tipe' => 'beans',
            'satuan_dasar' => 'kg',
            'harga_modal' => 80000,
            'harga_jual' => 100000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        $produkSnack = Produk::create([
            'kategori_id' => $kategori->id,
            'sku' => 'SNACK-01',
            'nama' => 'Cookie',
            'deskripsi' => 'Snack',
            'tipe' => 'snack',
            'satuan_dasar' => 'pcs',
            'harga_modal' => 2000,
            'harga_jual' => 5000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        // create stock etalase: for minuman (production beans), beans (retail), snack (production)
        $stokMinuman = StokEtalase::create([
            'cabang_id' => $cabang->id,
            'produk_id' => $produkMinuman->id,
            'tipe_stok' => 'produksi_minuman',
            'jumlah' => 10.0, // in kg
            'stok_minimum' => 1.0,
        ]);

        $stokBeans = StokEtalase::create([
            'cabang_id' => $cabang->id,
            'produk_id' => $produkBeans->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 5.0, // kg
            'stok_minimum' => 0.5,
        ]);

        $stokSnack = StokEtalase::create([
            'cabang_id' => $cabang->id,
            'produk_id' => $produkSnack->id,
            'tipe_stok' => 'produksi_minuman',
            'jumlah' => 50,
            'stok_minimum' => 5,
        ]);

        // create a batch for beans with near expiry
        $batch = BatchStok::create([
            'stok_etalase_id' => $stokBeans->id,
            'nomor_batch' => 'BATCH-1',
            'jumlah' => 2.0,
            'tanggal_kadaluarsa' => now()->addDays(10),
            'harga_beli' => 80000,
            'waktu_terima' => now(),
        ]);

        // authenticate as user (use session guard)
        $this->actingAs($user);

        // open shift via API
        $response = $this->postJson('/pos/shift/buka', [
            'cabang_id' => $cabang->id,
            'saldo_awal' => 100000,
        ]);

        $response->assertStatus(200);
        $shiftId = $response->json('shift.id');
        $this->assertEquals($user->id, $response->json('shift.user_id'), json_encode($response->json()));

        // create kalibrasi for minuman
        $resp = $this->postJson('/pos/kalibrasi', [
            'shift_id' => $shiftId,
            'produk_id' => $produkMinuman->id,
            'nomor_percobaan' => 1,
            'berat_beans_gram' => 20, // 20 grams per cup
            'terpilih' => true,
        ]);
        $resp->assertStatus(200);

        // sell one cup espresso (should deduct 20g = 0.02kg from produksi_minuman stok for produkMinuman)
        $pay = $this->postJson('/pos/transaksi', [
            'shift_id' => $shiftId,
            'items' => [[ 'produk_id' => $produkMinuman->id, 'jumlah' => 1 ]],
            'pembayaran' => [[ 'metode' => 'tunai', 'jumlah' => 15000 ]],
        ]);
        $pay->assertStatus(200);

        // reload stok and check decrease (kalibrasi already consumed 0.02kg, plus this sale 0.02kg => 0.04kg)
        $stokMinuman->refresh();
        $this->assertEqualsWithDelta(9.96, (float) $stokMinuman->jumlah, 0.001);

        // sell beans retail 1 kg
        $pay2 = $this->postJson('/pos/transaksi', [
            'shift_id' => $shiftId,
            'items' => [[ 'produk_id' => $produkBeans->id, 'jumlah' => 1 ]],
            'pembayaran' => [[ 'metode' => 'tunai', 'jumlah' => 100000 ]],
        ]);
        $this->assertEquals(200, $pay2->status(), $pay2->getContent());

        $stokBeans->refresh();
        $this->assertEqualsWithDelta(4.0, (float) $stokBeans->jumlah, 0.01);

        // sell snack 2 pcs
        $pay3 = $this->postJson('/pos/transaksi', [
            'shift_id' => $shiftId,
            'items' => [[ 'produk_id' => $produkSnack->id, 'jumlah' => 2 ]],
            'pembayaran' => [[ 'metode' => 'qris', 'jumlah' => 10000 ]],
        ]);
        $pay3->assertStatus(200);

        $stokSnack->refresh();
        $this->assertEquals(48, (int) $stokSnack->jumlah);

        // close shift: compute expected saldo akhir = saldo_awal + total_tunai
        // total_tunai is sum of tunai payments: 15000 + 100000 = 115000
        $respClose = $this->postJson("/pos/shift/{$shiftId}/tutup", [
            'saldo_akhir' => 100000 + 115000,
            'catatan' => 'Penutupan shift test'
        ]);
        $this->assertEquals(200, $respClose->status(), $respClose->getContent());

        $closedShift = Shift::find($shiftId);
        $this->assertEquals('tutup', $closedShift->status);
        $this->assertEqualsWithDelta(115000.0, (float) $closedShift->total_tunai, 0.01);

        // fetch ringkasan
        $report = $this->getJson("/pos/laporan/shift/{$shiftId}/ringkasan");
        $report->assertStatus(200);
        $data = $report->json();
        $this->assertArrayHasKey('keuangan', $data);

        // verify barang mendekati kadaluarsa endpoint in viewer
        $near = $this->getJson("/viewer/stok/cabang/{$cabang->id}/mendekati-kadaluarsa?hari=15");
        $near->assertStatus(200);
        $this->assertNotEmpty($near->json('batches'));
    }

    public function test_mobile_kasir_can_access_produk_and_stok_via_api()
    {
        $user = User::create([
            'name' => 'Kasir API',
            'email' => 'kasir.api@example.test',
            'password' => bcrypt('secret'),
            'role' => 'kasir',
            'aktif' => true,
        ]);

        $cabang = Cabang::create([
            'kode' => 'CBG2',
            'nama' => 'Cabang 2',
            'alamat' => 'Alamat',
            'telepon' => '08123456780',
            'aktif' => true,
        ]);

        $user->cabang()->attach($cabang->id);

        $kategori = KategoriProduk::create(['nama' => 'Minuman', 'slug' => 'minuman', 'deskripsi' => '']);

        $produk = Produk::create([
            'kategori_id' => $kategori->id,
            'sku' => 'MIN-API-01',
            'nama' => 'Latte API',
            'deskripsi' => 'Minuman latte',
            'tipe' => 'minuman',
            'satuan_dasar' => 'cup',
            'harga_modal' => 7000,
            'harga_jual' => 20000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        StokEtalase::create([
            'cabang_id' => $cabang->id,
            'produk_id' => $produk->id,
            'tipe_stok' => 'produksi_minuman',
            'jumlah' => 5.0,
            'stok_minimum' => 1.0,
        ]);

        Sanctum::actingAs($user);

        $respProduk = $this->getJson("/api/pos/produk");
        $respProduk->assertStatus(200);

        $respStok = $this->getJson("/api/pos/stok/cabang/{$cabang->id}");
        $respStok->assertStatus(200);
    }
}
