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
        // Tambahkan produk beans untuk SatuanProdukSeeder
        $katBeans = KategoriProduk::where('slug', 'kopi-beans')->first();
        if ($katBeans) {
            Produk::updateOrCreate(
                ['sku' => 'BEAN-ARB-001'],
                [
                    'kategori_id' => $katBeans->id,
                    'nama' => 'Arabica Beans',
                    'deskripsi' => 'Biji kopi Arabica premium',
                    'tipe' => 'beans', // Ubah dari 'bahan_baku' ke 'beans'
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
                    'tipe' => 'beans', // Ubah dari 'bahan_baku' ke 'beans'
                    'satuan_dasar' => 'gram',
                    'harga_modal' => 60000,
                    'harga_jual' => 75000,
                    'aktif' => true,
                    'perlu_kalibrasi' => false,
                ]
            );
        }

        // Produk minuman
        $katMinuman = KategoriProduk::where('slug', 'minuman-kopi')->first();
        if (! $katMinuman) {
            echo "Kategori 'Minuman Kopi' tidak ditemukan, lewati seeding produk minuman.\n";
            return;
        }

        $menuItems = [
            [
                'sku' => 'BEV-AME-HOT-001',
                'nama' => 'Americano Hot',
                'harga_jual' => 15000,
                'image_path' => 'foto-produk/americano-hot.jpeg',
                'deskripsi' => 'Americano panas.',
            ],
            [
                'sku' => 'BEV-AME-ICE-001',
                'nama' => 'Americano Ice',
                'harga_jual' => 18000,
                'image_path' => 'foto-produk/americano-ice.jpeg',
                'deskripsi' => 'Americano dingin.',
            ],
            [
                'sku' => 'BEV-SLC-HOT-001',
                'nama' => 'Summer Lemon Coffee Hot',
                'harga_jual' => 20000,
                'image_path' => 'foto-produk/summer-lemon-coffee-hot.jpeg',
                'deskripsi' => 'Kopi lemon segar panas.',
            ],
            [
                'sku' => 'BEV-SLC-ICE-001',
                'nama' => 'Summer Lemon Coffee Ice',
                'harga_jual' => 23000,
                'image_path' => 'foto-produk/summer-lemon-coffee-ice.jpeg',
                'deskripsi' => 'Kopi lemon segar dingin.',
            ],
            [
                'sku' => 'BEV-MCL-ICE-001',
                'nama' => 'Matcha Coffee Latte Ice',
                'harga_jual' => 24000,
                'image_path' => 'foto-produk/matcha-coffee-latte-ice.jpeg',
                'deskripsi' => 'Matcha coffee latte dingin.',
            ],
            [
                'sku' => 'BEV-MCL-HOT-001',
                'nama' => 'Matcha Coffee Latte Hot',
                'harga_jual' => 21000,
                'image_path' => 'foto-produk/matcha-coffee-latte-hot.jpeg',
                'deskripsi' => 'Matcha coffee latte panas.',
            ],
            [
                'sku' => 'BEV-SVA-ICE-001',
                'nama' => 'Shaken Vanilla Americano Ice',
                'harga_jual' => 20000,
                'image_path' => 'foto-produk/shaken-vanilla-americano-ice.jpeg',
                'deskripsi' => 'Shaken vanilla americano dingin.',
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

        // Tambahkan stok etalase untuk produk beans saja (tidak untuk minuman)
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
