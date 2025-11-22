<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\KategoriProduk;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run()
    {
        $katBeans = KategoriProduk::where('slug', 'kopi-beans')->first();
        $katMinuman = KategoriProduk::where('slug', 'minuman-kopi')->first();
        $katSnack = KategoriProduk::where('slug', 'snack')->first();

        $arabica = Produk::create([
            'kategori_id' => $katBeans->id,
            'sku' => 'BEAN-ARB-001',
            'nama' => 'Arabica Premium',
            'deskripsi' => 'Biji kopi arabica pilihan dari pegunungan',
            'tipe' => 'beans',
            'satuan_dasar' => 'gram',
            'harga_modal' => 150000,
            'harga_jual' => 180000,
            'aktif' => true,
            'perlu_kalibrasi' => true,
        ]);

        $robusta = Produk::create([
            'kategori_id' => $katBeans->id,
            'sku' => 'BEAN-ROB-001',
            'nama' => 'Robusta Original',
            'deskripsi' => 'Biji kopi robusta khas Indonesia',
            'tipe' => 'beans',
            'satuan_dasar' => 'gram',
            'harga_modal' => 100000,
            'harga_jual' => 130000,
            'aktif' => true,
            'perlu_kalibrasi' => true,
        ]);

        Produk::create([
            'kategori_id' => $katMinuman->id,
            'sku' => 'BEV-ESP-001',
            'nama' => 'Espresso',
            'deskripsi' => 'Single shot espresso',
            'tipe' => 'minuman',
            'satuan_dasar' => 'pcs',
            'harga_modal' => 8000,
            'harga_jual' => 15000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        Produk::create([
            'kategori_id' => $katMinuman->id,
            'sku' => 'BEV-LAT-001',
            'nama' => 'Latte',
            'deskripsi' => 'Espresso dengan susu steamed',
            'tipe' => 'minuman',
            'satuan_dasar' => 'pcs',
            'harga_modal' => 12000,
            'harga_jual' => 25000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        Produk::create([
            'kategori_id' => $katMinuman->id,
            'sku' => 'BEV-CAP-001',
            'nama' => 'Cappuccino',
            'deskripsi' => 'Espresso dengan foam susu',
            'tipe' => 'minuman',
            'satuan_dasar' => 'pcs',
            'harga_modal' => 12000,
            'harga_jual' => 25000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        Produk::create([
            'kategori_id' => $katMinuman->id,
            'sku' => 'BEV-AME-001',
            'nama' => 'Americano',
            'deskripsi' => 'Espresso dengan air panas',
            'tipe' => 'minuman',
            'satuan_dasar' => 'pcs',
            'harga_modal' => 10000,
            'harga_jual' => 20000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        Produk::create([
            'kategori_id' => $katSnack->id,
            'sku' => 'SNK-CRO-001',
            'nama' => 'Croissant',
            'deskripsi' => 'Pastry butter klasik',
            'tipe' => 'snack',
            'satuan_dasar' => 'pcs',
            'harga_modal' => 5000,
            'harga_jual' => 15000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        Produk::create([
            'kategori_id' => $katSnack->id,
            'sku' => 'SNK-BRW-001',
            'nama' => 'Brownies',
            'deskripsi' => 'Kue cokelat lembut',
            'tipe' => 'snack',
            'satuan_dasar' => 'pcs',
            'harga_modal' => 6000,
            'harga_jual' => 18000,
            'aktif' => true,
            'perlu_kalibrasi' => false,
        ]);

        echo "======== Produk berhasil dibuat ========\n";
    }
}
