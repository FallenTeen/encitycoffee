<?php

// Setup Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Produk;
use Illuminate\Support\Facades\Log;

echo "=== Debugging ProdukController Index ===\n\n";

// Clear cache
\Illuminate\Support\Facades\Cache::clear();
echo "✓ Cache cleared\n\n";

// Login sebagai it_support
$user = User::where('role', 'it_support')->first();
if (!$user) {
    echo "✗ it_support user tidak ditemukan\n";
    exit(1);
}

auth()->login($user);
echo "✓ Logged in as: " . $user->name . "\n";
echo "  Role: " . $user->role . "\n";
echo "  Assigned cabang: " . $user->cabang->pluck('nama')->implode(', ') . "\n\n";

// Test getCabangId logic
echo "=== Testing getCabangId Logic ===\n";
if ($user->role === 'it_support') {
    $cabangId = 1; // it_support bisa akses semua, default ke cabang 1
    echo "✓ IT Support - Default cabang: $cabangId\n";
} else {
    echo "✗ Not IT Support\n";
}

// Test getProdukByCabang
echo "\n=== Testing getProdukByCabang ===\n";
$produk = Produk::aktif()
    ->with(['stokEtalase' => function ($q) use ($cabangId) {
        $q->where('cabang_id', $cabangId);
    }, 'kategori'])
    ->select([
        'produk.id',
        'produk.sku',
        'produk.nama',
        'produk.deskripsi',
        'produk.harga_jual',
        'produk.tipe',
        'produk.image_path',
        'produk.kategori_id',
        'produk.harga_modal',
        'produk.satuan_dasar',
        'produk.perlu_kalibrasi'
    ])
    ->orderBy('produk.nama')
    ->get();

echo "Produk count: " . $produk->count() . "\n";
foreach ($produk as $p) {
    $stok = $p->stokEtalase->first();
    echo "  - {$p->nama} (ID: {$p->id}, Stok: " . ($stok ? $stok->jumlah : 'N/A') . ")\n";
}

// Simulate pagination like controller
echo "\n=== Testing Pagination Structure ===\n";
$perPage = 20;
$page = 1;
$total = $produk->count();
$offset = ($page - 1) * $perPage;
$items = $produk->slice($offset, $perPage)->values();

$produks = [
    'data' => $items,
    'total' => $total,
    'current_page' => $page,
    'per_page' => $perPage,
    'last_page' => $total > 0 ? (int) ceil($total / $perPage) : 1,
    'from' => $total > 0 ? $offset + 1 : null,
    'to' => $total > 0 ? min($offset + $perPage, $total) : null,
];

echo "Pagination structure:\n";
echo json_encode($produks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Check if categories loaded
echo "=== Testing Kategori Loading ===\n";
$kategoriList = \App\Models\KategoriProduk::select('id','nama')->get();
echo "Kategori count: " . $kategoriList->count() . "\n";
foreach ($kategoriList as $k) {
    echo "  - {$k->nama} (ID: {$k->id})\n";
}

echo "\n✓ All tests completed\n";
