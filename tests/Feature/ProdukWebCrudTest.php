<?php

use App\Models\User;
use App\Models\KategoriProduk;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('manager can see produk index page', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $kategori = KategoriProduk::create([
        'nama' => 'Minuman',
        'slug' => 'minuman',
        'deskripsi' => '',
    ]);

    Produk::create([
        'kategori_id' => $kategori->id,
        'sku' => 'PRD-001',
        'nama' => 'Produk Test',
        'deskripsi' => 'Deskripsi',
        'tipe' => 'minuman',
        'satuan_dasar' => 'cup',
        'harga_modal' => 5000,
        'harga_jual' => 10000,
        'aktif' => true,
        'perlu_kalibrasi' => false,
    ]);

    $this->actingAs($user)
        ->get(route('produk.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('produk/Index')
            ->has('produks')
            ->has('kategori_list')
            ->has('filter_aktif')
        );
});

test('manager can create produk via web form', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $kategori = KategoriProduk::create([
        'nama' => 'Minuman',
        'slug' => 'minuman',
        'deskripsi' => '',
    ]);

    $payload = [
        'kategori_id' => $kategori->id,
        'sku' => 'PRD-NEW',
        'nama' => 'Produk Baru',
        'deskripsi' => 'Deskripsi produk baru',
        'tipe' => 'minuman',
        'satuan_dasar' => 'cup',
        'harga_modal' => 5000,
        'harga_jual' => 15000,
        'perlu_kalibrasi' => false,
        'aktif' => true,
    ];

    $response = $this->actingAs($user)->post(route('produk.store'), $payload);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('produk.index'));

    $this->assertDatabaseHas('produk', [
        'sku' => 'PRD-NEW',
        'nama' => 'Produk Baru',
        'kategori_id' => $kategori->id,
    ]);
});

test('manager can update produk via web form', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $kategori = KategoriProduk::create([
        'nama' => 'Minuman',
        'slug' => 'minuman',
        'deskripsi' => '',
    ]);

    $produk = Produk::create([
        'kategori_id' => $kategori->id,
        'sku' => 'PRD-EDIT',
        'nama' => 'Produk Lama',
        'deskripsi' => 'Deskripsi lama',
        'tipe' => 'minuman',
        'satuan_dasar' => 'cup',
        'harga_modal' => 5000,
        'harga_jual' => 10000,
        'aktif' => true,
        'perlu_kalibrasi' => false,
    ]);

    $payload = [
        'kategori_id' => $kategori->id,
        'sku' => 'PRD-EDIT',
        'nama' => 'Produk Updated',
        'deskripsi' => 'Deskripsi baru',
        'tipe' => 'minuman',
        'satuan_dasar' => 'cup',
        'harga_modal' => 6000,
        'harga_jual' => 16000,
        'perlu_kalibrasi' => false,
        'aktif' => true,
    ];

    $response = $this->actingAs($user)->put(route('produk.update', $produk), $payload);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('produk.index'));

    $this->assertDatabaseHas('produk', [
        'id' => $produk->id,
        'nama' => 'Produk Updated',
        'deskripsi' => 'Deskripsi baru',
        'harga_modal' => 6000,
        'harga_jual' => 16000,
    ]);
});

test('manager can delete produk without related stok or transaksi', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $kategori = KategoriProduk::create([
        'nama' => 'Snack',
        'slug' => 'snack',
        'deskripsi' => '',
    ]);

    $produk = Produk::create([
        'kategori_id' => $kategori->id,
        'sku' => 'PRD-DEL',
        'nama' => 'Produk Hapus',
        'deskripsi' => 'Akan dihapus',
        'tipe' => 'snack',
        'satuan_dasar' => 'pcs',
        'harga_modal' => 2000,
        'harga_jual' => 5000,
        'aktif' => true,
        'perlu_kalibrasi' => false,
    ]);

    $response = $this->actingAs($user)->delete(route('produk.destroy', $produk));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('produk.index'));

    $this->assertDatabaseMissing('produk', [
        'id' => $produk->id,
    ]);
});

