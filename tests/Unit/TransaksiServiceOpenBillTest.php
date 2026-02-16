<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Cabang;
use App\Models\Shift;
use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\OpenBill;
use App\Models\OpenBillItem;
use App\Models\StokEtalase;
use App\Services\TransaksiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransaksiServiceOpenBillTest extends TestCase
{
    use RefreshDatabase;

    public function test_convert_open_bill_to_transaksi_succeeds_and_closes_bill()
    {
        $user = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@unit.test',
            'password' => Hash::make('password123'),
            'role' => 'kasir',
            'aktif' => true,
        ]);
        $cabang = Cabang::create([
            'kode' => 'CBG001',
            'nama' => 'Cabang Unit',
            'alamat' => 'Jl Unit',
            'telepon' => '0800',
            'aktif' => true,
        ]);
        $user->cabang()->attach($cabang->id);

        $kategori = KategoriProduk::create([
            'nama' => 'Kategori',
            'slug' => 'kategori',
        ]);
        $produk = Produk::create([
            'sku' => 'SKU-U-1',
            'nama' => 'Produk U',
            'kategori_id' => $kategori->id,
            'tipe' => 'beans',
            'harga_beli' => 10000,
            'harga_jual' => 25000,
            'aktif' => true,
        ]);
        StokEtalase::create([
            'produk_id' => $produk->id,
            'cabang_id' => $cabang->id,
            'tipe_stok' => 'penjualan_retail',
            'jumlah' => 10,
            'stok_minimum' => 1,
        ]);

        $shift = Shift::create([
            'user_id' => $user->id,
            'cabang_id' => $cabang->id,
            'saldo_awal' => 100000,
            'status' => 'buka',
            'nama_kasir' => 'Kasir',
            'waktu_buka' => now(),
        ]);

        $openBill = OpenBill::create([
            'shift_id' => $shift->id,
            'cabang_id' => $cabang->id,
            'user_id' => $user->id,
            'nomor_open_bill' => 'OB-UNIT-0001',
            'subtotal' => 25000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 25000,
            'status' => 'open',
        ]);
        OpenBillItem::create([
            'open_bill_id' => $openBill->id,
            'produk_id' => $produk->id,
            'jumlah' => 1,
            'harga_satuan' => 25000,
            'subtotal' => 25000,
        ]);

        $service = $this->app->make(TransaksiService::class);
        $transaksi = $service->convertOpenBillToTransaksi($openBill, [
            ['metode' => 'tunai', 'jumlah' => 25000],
        ]);

        $this->assertNotNull($transaksi->id);
        $this->assertEquals('selesai', $transaksi->status);
        $this->assertDatabaseHas('open_bills', [
            'id' => $openBill->id,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('open_bill_items', [
            'open_bill_id' => $openBill->id,
        ]);
    }
}
