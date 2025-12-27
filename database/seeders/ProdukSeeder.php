<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\StokEtalase;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run()
    {
        // ========================================
        // PRODUK BEANS (BAHAN BAKU)
        // ========================================
        $katBeans = KategoriProduk::where('slug', 'kopi-beans')->first();
        if ($katBeans) {
            Produk::updateOrCreate(
                ['sku' => 'BEAN-ARB-001'],
                [
                    'kategori_id' => $katBeans->id,
                    'nama' => 'Arabica Beans',
                    'deskripsi' => 'Biji kopi Arabica premium',
                    'tipe' => 'beans',
                    'satuan_dasar' => 'gram',
                    'harga_modal' => 80000,
                    'harga_jual' => 100000,
                    'aktif' => true,
                    'perlu_kalibrasi' => false,
                ]
            );

            Produk::updateOrCreate(
                ['sku' => 'BEAN-ROB-001'],
                [
                    'kategori_id' => $katBeans->id,
                    'nama' => 'Robusta Beans',
                    'deskripsi' => 'Biji kopi Robusta pilihan',
                    'tipe' => 'beans',
                    'satuan_dasar' => 'gram',
                    'harga_modal' => 60000,
                    'harga_jual' => 75000,
                    'aktif' => true,
                    'perlu_kalibrasi' => false,
                ]
            );
        }

        // ========================================
        // PRODUK MINUMAN
        // ========================================
        $katMinuman = KategoriProduk::where('slug', 'minuman-kopi')->first();
        if (!$katMinuman) {
            echo "Kategori 'Minuman Kopi' tidak ditemukan, lewati seeding produk minuman.\n";
            return;
        }

        $menuItems = [
            // ========================================
            // ESPRESSO BASED (HOT)
            // ========================================
            [
                'sku' => 'BEV-AME-HOT-001',
                'nama' => 'Americano',
                'harga_jual' => 15000,
                'image_path' => 'foto-produk/americano-hot.jpeg',
                'deskripsi' => 'Espresso dengan air panas.',
            ],
            [
                'sku' => 'BEV-LBK-HOT-001',
                'nama' => 'Long Black',
                'harga_jual' => 18000,
                'image_path' => 'foto-produk/long-black-hot.jpeg',
                'deskripsi' => 'Air panas dengan espresso di atasnya.',
            ],
            [
                'sku' => 'BEV-CAP-HOT-001',
                'nama' => 'Cappuccino',
                'harga_jual' => 22000,
                'image_path' => 'foto-produk/cappuccino-hot.jpeg',
                'deskripsi' => 'Espresso dengan steamed milk dan foam.',
            ],
            [
                'sku' => 'BEV-LAT-HOT-001',
                'nama' => 'Latte',
                'harga_jual' => 22000,
                'image_path' => 'foto-produk/latte-hot.jpeg',
                'deskripsi' => 'Espresso dengan steamed milk.',
            ],
            [
                'sku' => 'BEV-FLW-HOT-001',
                'nama' => 'Flat White',
                'harga_jual' => 24000,
                'image_path' => 'foto-produk/flat-white-hot.jpeg',
                'deskripsi' => 'Double shot espresso dengan microfoam milk.',
            ],
            [
                'sku' => 'BEV-PIC-HOT-001',
                'nama' => 'Piccolo',
                'harga_jual' => 21000,
                'image_path' => 'foto-produk/piccolo-hot.jpeg',
                'deskripsi' => 'Single ristretto shot dengan steamed milk.',
            ],
            [
                'sku' => 'BEV-MOC-HOT-001',
                'nama' => 'Mocha',
                'harga_jual' => 23000,
                'image_path' => 'foto-produk/mocha-hot.jpeg',
                'deskripsi' => 'Espresso dengan cokelat dan steamed milk.',
            ],

            // ========================================
            // MANUAL BREW (HOT)
            // ========================================
            [
                'sku' => 'BEV-TUB-HOT-001',
                'nama' => 'Tubruk',
                'harga_jual' => 20000,
                'image_path' => 'foto-produk/tubruk-hot.jpeg',
                'deskripsi' => 'Kopi tubruk tradisional.',
            ],
            [
                'sku' => 'BEV-V60-HOT-001',
                'nama' => 'V60',
                'harga_jual' => 20000,
                'image_path' => 'foto-produk/v60-hot.jpeg',
                'deskripsi' => 'Manual brew dengan V60 dripper.',
            ],
            [
                'sku' => 'BEV-POS-HOT-001',
                'nama' => 'Pour Over Signature',
                'harga_jual' => 35000,
                'image_path' => 'foto-produk/pour-over-signature.jpeg',
                'deskripsi' => 'Pour over dengan biji kopi signature.',
            ],

            // ========================================
            // MILK & FLAVORED COFFEE (HOT)
            // ========================================
            [
                'sku' => 'BEV-KSGA-HOT-001',
                'nama' => 'Kopi Susu Gula Aren',
                'harga_jual' => 23000,
                'image_path' => 'foto-produk/kopi-susu-gula-aren-hot.jpeg',
                'deskripsi' => 'Kopi susu dengan gula aren.',
            ],
            [
                'sku' => 'BEV-VLA-HOT-001',
                'nama' => 'Vanilla Latte',
                'harga_jual' => 22000,
                'image_path' => 'foto-produk/vanilla-latte-hot.jpeg',
                'deskripsi' => 'Latte dengan vanilla syrup.',
            ],
            [
                'sku' => 'BEV-CLA-HOT-001',
                'nama' => 'Caramel Latte',
                'harga_jual' => 22000,
                'image_path' => 'foto-produk/caramel-latte-hot.jpeg',
                'deskripsi' => 'Latte dengan caramel syrup.',
            ],
            [
                'sku' => 'BEV-BLA-HOT-001',
                'nama' => 'Butterscotch Latte',
                'harga_jual' => 22000,
                'image_path' => 'foto-produk/butterscotch-latte-hot.jpeg',
                'deskripsi' => 'Latte dengan butterscotch syrup.',
            ],

            // ========================================
            // ESPRESSO BASED (ICE)
            // ========================================
            [
                'sku' => 'BEV-AME-ICE-001',
                'nama' => 'Iced Americano',
                'harga_jual' => 17000,
                'image_path' => 'foto-produk/iced-americano.jpeg',
                'deskripsi' => 'Espresso dengan air dingin dan es.',
            ],
            [
                'sku' => 'BEV-LBK-ICE-001',
                'nama' => 'Iced Long Black',
                'harga_jual' => 18000,
                'image_path' => 'foto-produk/iced-long-black.jpeg',
                'deskripsi' => 'Air dingin dengan espresso di atasnya.',
            ],
            [
                'sku' => 'BEV-LAT-ICE-001',
                'nama' => 'Iced Latte',
                'harga_jual' => 24000,
                'image_path' => 'foto-produk/iced-latte.jpeg',
                'deskripsi' => 'Espresso dengan cold milk dan es.',
            ],
            [
                'sku' => 'BEV-CAP-ICE-001',
                'nama' => 'Iced Cappuccino',
                'harga_jual' => 24000,
                'image_path' => 'foto-produk/iced-cappuccino.jpeg',
                'deskripsi' => 'Espresso dengan cold milk foam dan es.',
            ],
            [
                'sku' => 'BEV-FLW-ICE-001',
                'nama' => 'Iced Flat White',
                'harga_jual' => 24000,
                'image_path' => 'foto-produk/iced-flat-white.jpeg',
                'deskripsi' => 'Double shot espresso dengan cold microfoam.',
            ],
            [
                'sku' => 'BEV-MOC-ICE-001',
                'nama' => 'Iced Mocha',
                'harga_jual' => 25000,
                'image_path' => 'foto-produk/iced-mocha.jpeg',
                'deskripsi' => 'Espresso dengan cokelat, cold milk dan es.',
            ],

            // ========================================
            // COFFEE MILK (BEST SELLER - ICE)
            // ========================================
            [
                'sku' => 'BEV-KSGA-ICE-001',
                'nama' => 'Es Kopi Susu Gula Aren',
                'harga_jual' => 28000,
                'image_path' => 'foto-produk/es-kopi-susu-gula-aren.jpeg',
                'deskripsi' => 'Es kopi susu dengan gula aren - Best Seller!',
            ],
            [
                'sku' => 'BEV-KSV-ICE-001',
                'nama' => 'Es Kopi Susu Vanilla',
                'harga_jual' => 23000,
                'image_path' => 'foto-produk/es-kopi-susu-vanilla.jpeg',
                'deskripsi' => 'Es kopi susu dengan vanilla.',
            ],
            [
                'sku' => 'BEV-KSC-ICE-001',
                'nama' => 'Es Kopi Susu Caramel',
                'harga_jual' => 22000,
                'image_path' => 'foto-produk/es-kopi-susu-caramel.jpeg',
                'deskripsi' => 'Es kopi susu dengan caramel.',
            ],

            // ========================================
            // FLAVORED ICED COFFEE
            // ========================================
            [
                'sku' => 'BEV-VLA-ICE-001',
                'nama' => 'Iced Vanilla Latte',
                'harga_jual' => 25000,
                'image_path' => 'foto-produk/iced-vanilla-latte.jpeg',
                'deskripsi' => 'Iced latte dengan vanilla syrup.',
            ],
            [
                'sku' => 'BEV-CLA-ICE-001',
                'nama' => 'Iced Caramel Latte',
                'harga_jual' => 25000,
                'image_path' => 'foto-produk/iced-caramel-latte.jpeg',
                'deskripsi' => 'Iced latte dengan caramel syrup.',
            ],
            [
                'sku' => 'BEV-HLA-ICE-001',
                'nama' => 'Iced Hazelnut Latte',
                'harga_jual' => 25000,
                'image_path' => 'foto-produk/iced-hazelnut-latte.jpeg',
                'deskripsi' => 'Iced latte dengan hazelnut syrup.',
            ],

            // ========================================
            // MANUAL BREW (ICE)
            // ========================================
            [
                'sku' => 'BEV-V60-ICE-001',
                'nama' => 'Ice V60',
                'harga_jual' => 24000,
                'image_path' => 'foto-produk/ice-v60.jpeg',
                'deskripsi' => 'Manual brew V60 dingin dengan es.',
            ],

            // ========================================
            // SHAKER COFFEE SERIES
            // ========================================
            [
                'sku' => 'BEV-SHV-ICE-001',
                'nama' => 'Shaker Vanilla',
                'harga_jual' => 20000,
                'image_path' => 'foto-produk/shaker-vanilla.jpeg',
                'deskripsi' => 'Shaken coffee dengan vanilla.',
            ],
            [
                'sku' => 'BEV-SHC-ICE-001',
                'nama' => 'Shaker Caramel',
                'harga_jual' => 20000,
                'image_path' => 'foto-produk/shaker-caramel.jpeg',
                'deskripsi' => 'Shaken coffee dengan caramel.',
            ],
            [
                'sku' => 'BEV-SHB-ICE-001',
                'nama' => 'Shaker Butterscotch',
                'harga_jual' => 20000,
                'image_path' => 'foto-produk/shaker-butterscotch.jpeg',
                'deskripsi' => 'Shaken coffee dengan butterscotch.',
            ],

            // ========================================
            // MILK BASED (HOT)
            // ========================================
            [
                'sku' => 'BEV-MAT-HOT-001',
                'nama' => 'Matcha Latte',
                'harga_jual' => 22000,
                'image_path' => 'foto-produk/matcha-latte-hot.jpeg',
                'deskripsi' => 'Matcha dengan steamed milk.',
            ],
            [
                'sku' => 'BEV-CHO-HOT-001',
                'nama' => 'Chocolate Latte',
                'harga_jual' => 22000,
                'image_path' => 'foto-produk/chocolate-latte-hot.jpeg',
                'deskripsi' => 'Cokelat dengan steamed milk.',
            ],

            // ========================================
            // MILK BASED (ICE)
            // ========================================
            [
                'sku' => 'BEV-MAT-ICE-001',
                'nama' => 'Matcha Latte Ice',
                'harga_jual' => 24000,
                'image_path' => 'foto-produk/matcha-latte-ice.jpeg',
                'deskripsi' => 'Matcha dengan cold milk dan es.',
            ],
            [
                'sku' => 'BEV-CHO-ICE-001',
                'nama' => 'Chocolate Latte Ice',
                'harga_jual' => 24000,
                'image_path' => 'foto-produk/chocolate-latte-ice.jpeg',
                'deskripsi' => 'Cokelat dengan cold milk dan es.',
            ],
        ];

        foreach ($menuItems as $item) {
            Produk::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'kategori_id' => $katMinuman->id,
                    'nama' => $item['nama'],
                    'deskripsi' => $item['deskripsi'],
                    'tipe' => 'minuman',
                    'satuan_dasar' => 'pcs',
                    'harga_modal' => max(0, $item['harga_jual'] - 5000),
                    'harga_jual' => $item['harga_jual'],
                    'aktif' => true,
                    'perlu_kalibrasi' => false,
                    'image_path' => $item['image_path'] ?? null,
                ]
            );
        }

        echo "======== Produk beans dan minuman berhasil dibuat ========\n";
        echo "Total produk minuman: " . count($menuItems) . "\n";

        // ========================================
        // STOK ETALASE (HANYA UNTUK BEANS)
        // ========================================
        $produkBeans = Produk::where('tipe', 'beans')->get();

        foreach ($produkBeans as $produk) {
            // Cabang 1 - Stok awal
            StokEtalase::updateOrCreate(
                ['produk_id' => $produk->id, 'cabang_id' => 1],
                [
                    'jumlah' => 100,
                    'stok_minimum' => 20,
                ]
            );

            // Cabang 2 - Stok awal (jika ada)
            StokEtalase::updateOrCreate(
                ['produk_id' => $produk->id, 'cabang_id' => 2],
                [
                    'jumlah' => 80,
                    'stok_minimum' => 20,
                ]
            );
        }

        echo "======== Stok etalase berhasil dibuat untuk produk beans ========\n";
    }
}