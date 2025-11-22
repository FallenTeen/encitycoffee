<?php
namespace Database\Seeders;

use App\Models\KategoriProduk;
use Illuminate\Database\Seeder;

class KategoriProdukSeeder extends Seeder
{
    public function run()
    {
        $kategori = [
            ['nama' => 'Kopi Beans', 'slug' => 'kopi-beans', 'deskripsi' => 'Biji kopi untuk dijual retail'],
            ['nama' => 'Minuman Kopi', 'slug' => 'minuman-kopi', 'deskripsi' => 'Minuman berbahan dasar kopi'],
            ['nama' => 'Snack', 'slug' => 'snack', 'deskripsi' => 'Makanan ringan pelengkap'],
        ];

        foreach ($kategori as $kat) {
            KategoriProduk::create($kat);
        }

        echo "======== Kategori produk berhasil dibuat ========\n";
    }
}
