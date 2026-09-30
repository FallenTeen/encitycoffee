<?php

use App\Models\Cabang;
use App\Models\Shift;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('riwayat transaksi index menampilkan transaksi cabang user', function () {
    $cabangA = Cabang::factory()->create();
    $cabangB = Cabang::factory()->create();

    $manager = User::factory()->create([
        'role' => 'manager',
        'aktif' => true,
    ]);

    $manager->cabang()->attach([$cabangA->id]);

    $kasir = User::factory()->create([
        'role' => 'kasir',
        'aktif' => true,
    ]);

    $shiftA = Shift::create([
        'user_id' => $kasir->id,
        'cabang_id' => $cabangA->id,
        'saldo_awal' => 100000,
        'waktu_buka' => Carbon::parse('2025-01-01 08:00:00'),
        'status' => 'buka',
    ]);

    $shiftB = Shift::create([
        'user_id' => $kasir->id,
        'cabang_id' => $cabangB->id,
        'saldo_awal' => 200000,
        'waktu_buka' => Carbon::parse('2025-01-02 08:00:00'),
        'status' => 'buka',
    ]);

    $tCabangA = Transaksi::create([
        'shift_id' => $shiftA->id,
        'cabang_id' => $cabangA->id,
        'user_id' => $kasir->id,
        'nomor_invoice' => 'INV-A-1',
        'subtotal' => 50000,
        'diskon' => 0,
        'pajak' => 0,
        'total' => 50000,
        'status' => 'selesai',
        'waktu_selesai' => Carbon::parse('2025-01-01 10:00:00'),
    ]);

    $tCabangB = Transaksi::create([
        'shift_id' => $shiftB->id,
        'cabang_id' => $cabangB->id,
        'user_id' => $kasir->id,
        'nomor_invoice' => 'INV-B-1',
        'subtotal' => 75000,
        'diskon' => 0,
        'pajak' => 0,
        'total' => 75000,
        'status' => 'selesai',
        'waktu_selesai' => Carbon::parse('2025-01-02 12:00:00'),
    ]);

    $this->actingAs($manager)
        ->get(route('transaksi.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transaksi/Index')
            ->where('transaksis.data', function ($items) use ($tCabangA, $tCabangB) {
                $ids = collect($items)->pluck('id')->all();
                return in_array($tCabangA->id, $ids, true) && ! in_array($tCabangB->id, $ids, true);
            })
            ->where('filter_aktif.status', '')
        );
});

test('riwayat transaksi per shift menampilkan transaksi hanya untuk shift tersebut', function () {
    $cabang = Cabang::factory()->create();

    $manager = User::factory()->create([
        'role' => 'manager',
        'aktif' => true,
    ]);

    $manager->cabang()->attach([$cabang->id]);

    $kasir = User::factory()->create([
        'role' => 'kasir',
        'aktif' => true,
    ]);

    $shiftTarget = Shift::create([
        'user_id' => $kasir->id,
        'cabang_id' => $cabang->id,
        'saldo_awal' => 100000,
        'waktu_buka' => Carbon::parse('2025-03-01 08:00:00'),
        'status' => 'buka',
    ]);

    // Same cashier, same branch, so the second shift has to be closed: one user may
    // only hold one open shift per branch (shift_active_branch_unique).
    $shiftLain = Shift::create([
        'user_id' => $kasir->id,
        'cabang_id' => $cabang->id,
        'saldo_awal' => 150000,
        'waktu_buka' => Carbon::parse('2025-03-02 08:00:00'),
        'waktu_tutup' => Carbon::parse('2025-03-02 20:00:00'),
        'status' => 'tutup',
    ]);

    $tShiftTarget1 = Transaksi::create([
        'shift_id' => $shiftTarget->id,
        'cabang_id' => $cabang->id,
        'user_id' => $kasir->id,
        'nomor_invoice' => 'INV-SHIFT-1',
        'subtotal' => 40000,
        'diskon' => 0,
        'pajak' => 0,
        'total' => 40000,
        'status' => 'selesai',
        'waktu_selesai' => Carbon::parse('2025-03-01 10:00:00'),
    ]);

    $tShiftTarget2 = Transaksi::create([
        'shift_id' => $shiftTarget->id,
        'cabang_id' => $cabang->id,
        'user_id' => $kasir->id,
        'nomor_invoice' => 'INV-SHIFT-2',
        'subtotal' => 60000,
        'diskon' => 0,
        'pajak' => 0,
        'total' => 60000,
        'status' => 'pending',
        'waktu_selesai' => Carbon::parse('2025-03-01 11:00:00'),
    ]);

    $tShiftLain = Transaksi::create([
        'shift_id' => $shiftLain->id,
        'cabang_id' => $cabang->id,
        'user_id' => $kasir->id,
        'nomor_invoice' => 'INV-SHIFT-OTHER',
        'subtotal' => 80000,
        'diskon' => 0,
        'pajak' => 0,
        'total' => 80000,
        'status' => 'selesai',
        'waktu_selesai' => Carbon::parse('2025-03-02 12:00:00'),
    ]);

    $this->actingAs($manager)
        ->get(route('transaksi.by-shift', ['shift' => $shiftTarget->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transaksi/ByShift')
            ->where('shift.id', $shiftTarget->id)
            ->where('transaksis.data', function ($items) use ($tShiftTarget1, $tShiftTarget2, $tShiftLain) {
                $ids = collect($items)->pluck('id')->all();
                return in_array($tShiftTarget1->id, $ids, true)
                    && in_array($tShiftTarget2->id, $ids, true)
                    && ! in_array($tShiftLain->id, $ids, true);
            })
        );
});
