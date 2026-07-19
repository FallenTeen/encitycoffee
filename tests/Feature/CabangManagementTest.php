<?php

use App\Models\Cabang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->backofficeBaseUrl = 'http://web.encitycompany.test';
});

test('it_support dapat update profil cabang', function () {
    $actor = User::factory()->create(['role' => 'it_support']);
    $cabang = Cabang::factory()->create([
        'kode' => 'AJB',
        'nama' => 'Ajibarang',
        'alamat' => 'Alamat Lama',
        'telepon' => '081234',
        'aktif' => true,
    ]);

    $this->actingAs($actor);

    $res = $this->put("{$this->backofficeBaseUrl}/admin/cabang/{$cabang->id}", [
        'kode' => 'AJB-NEW',
        'nama' => 'Ajibarang Baru',
        'alamat' => 'Alamat Baru',
        'telepon' => '081999',
        'aktif' => false,
    ]);

    $res->assertStatus(302);
    $res->assertSessionHas('success');

    $this->assertDatabaseHas('cabang', [
        'id' => $cabang->id,
        'kode' => 'AJB-NEW',
        'nama' => 'Ajibarang Baru',
        'alamat' => 'Alamat Baru',
        'telepon' => '081999',
        'aktif' => 0,
    ]);
});

test('it_support dapat menambah dan menghapus user supervisor dari cabang', function () {
    $actor = User::factory()->create(['role' => 'it_support']);
    $supervisor = User::factory()->create(['role' => 'supervisor']);
    $cabang = Cabang::factory()->create();

    $this->actingAs($actor);

    $attach = $this->post("{$this->backofficeBaseUrl}/admin/cabang/{$cabang->id}/users", [
        'user_id' => $supervisor->id,
    ]);
    $attach->assertStatus(302);
    $attach->assertSessionHas('success');

    $this->assertDatabaseHas('user_cabang', [
        'user_id' => $supervisor->id,
        'cabang_id' => $cabang->id,
    ]);

    $detach = $this->delete("{$this->backofficeBaseUrl}/admin/cabang/{$cabang->id}/users/{$supervisor->id}");
    $detach->assertStatus(302);
    $detach->assertSessionHas('success');

    $this->assertDatabaseMissing('user_cabang', [
        'user_id' => $supervisor->id,
        'cabang_id' => $cabang->id,
    ]);
});

test('attach manager ke cabang mengisi cabang_hierarchy dan detach mengosongkannya', function () {
    $actor = User::factory()->create(['role' => 'it_support']);
    $manager = User::factory()->create(['role' => 'manager']);
    $cabang = Cabang::factory()->create();

    $this->actingAs($actor);

    $attach = $this->post("{$this->backofficeBaseUrl}/admin/cabang/{$cabang->id}/users", [
        'user_id' => $manager->id,
    ]);
    $attach->assertStatus(302);
    $attach->assertSessionHas('success');

    $this->assertDatabaseHas('user_cabang', [
        'user_id' => $manager->id,
        'cabang_id' => $cabang->id,
    ]);
    $this->assertDatabaseHas('cabang_hierarchy', [
        'cabang_id' => $cabang->id,
        'manager_user_id' => $manager->id,
    ]);

    $detach = $this->delete("{$this->backofficeBaseUrl}/admin/cabang/{$cabang->id}/users/{$manager->id}");
    $detach->assertStatus(302);
    $detach->assertSessionHas('success');

    $this->assertDatabaseMissing('user_cabang', [
        'user_id' => $manager->id,
        'cabang_id' => $cabang->id,
    ]);
    $this->assertDatabaseHas('cabang_hierarchy', [
        'cabang_id' => $cabang->id,
        'manager_user_id' => null,
    ]);
});

test('non it_support tidak bisa akses modul cabang admin', function () {
    $actor = User::factory()->create(['role' => 'manager']);
    $cabang = Cabang::factory()->create();

    $this->actingAs($actor);
    $this->get("{$this->backofficeBaseUrl}/admin/cabang/{$cabang->id}/edit")->assertStatus(403);
});
