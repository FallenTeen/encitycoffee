<?php
namespace Database\Seeders;

use App\Models\Produk;
use App\Models\SatuanProduk;
use Illuminate\Database\Seeder;

class SatuanProdukSeeder extends Seeder
{
    public function run()
    {
        $arabica = Produk::where('sku', 'BEAN-ARB-001')->first();
        SatuanProduk::create([
            'produk_id'      => $arabica->id,
            'nama_satuan'    => '100 gram',
            'nilai_konversi' => 100,
        ]);
        SatuanProduk::create([
            'produk_id'      => $arabica->id,
            'nama_satuan'    => '250 gram',
            'nilai_konversi' => 250,
        ]);
        SatuanProduk::create([
            'produk_id'      => $arabica->id,
            'nama_satuan'    => '500 gram',
            'nilai_konversi' => 500,
        ]);
        SatuanProduk::create([
            'produk_id'      => $arabica->id,
            'nama_satuan'    => '1 kg',
            'nilai_konversi' => 1000,
        ]);

        $robusta = Produk::where('sku', 'BEAN-ROB-001')->first();
        SatuanProduk::create([
            'produk_id'      => $robusta->id,
            'nama_satuan'    => '100 gram',
            'nilai_konversi' => 100,
        ]);
        SatuanProduk::create([
            'produk_id'      => $robusta->id,
            'nama_satuan'    => '250 gram',
            'nilai_konversi' => 250,
        ]);
        SatuanProduk::create([
            'produk_id'      => $robusta->id,
            'nama_satuan'    => '500 gram',
            'nilai_konversi' => 500,
        ]);
        SatuanProduk::create([
            'produk_id'      => $robusta->id,
            'nama_satuan'    => '1 kg',
            'nilai_konversi' => 1000,
        ]);

        echo "======== Satuan produk berhasil dibuat ========\n";
    }
}
