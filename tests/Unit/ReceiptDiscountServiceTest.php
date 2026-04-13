<?php

use App\Services\ReceiptDiscountService;

it('menghitung diskon nominal dan mengembalikan persentase yang sinkron', function () {
    $service = new ReceiptDiscountService();

    $out = $service->preview([
        'total_awal' => 100000,
        'diskon_nominal' => 20000,
    ]);

    expect($out['total_awal'])->toBe('100000.00');
    expect($out['diskon_nominal'])->toBe('20000.00');
    expect($out['diskon_persen'])->toBe('20.0000');
    expect($out['total_akhir'])->toBe('80000.00');
});

it('menghitung diskon persen dan mengembalikan nominal yang sinkron', function () {
    $service = new ReceiptDiscountService();

    $out = $service->preview([
        'total_awal' => 95000,
        'diskon_persen' => 25,
    ]);

    expect($out['diskon_nominal'])->toBe('23750.00');
    expect($out['diskon_persen'])->toBe('25.0000');
    expect($out['total_akhir'])->toBe('71250.00');
});

it('mendukung pembulatan total akhir dengan cara menyesuaikan nominal diskon', function () {
    $service = new ReceiptDiscountService();

    $out = $service->preview([
        'total_awal' => 99999,
        'diskon_persen' => 25,
        'pembulatan' => [
            'mode' => 'down',
            'unit' => 100,
        ],
    ]);

    expect($out['pembulatan']['applied'])->toBeTrue();
    expect($out['pembulatan']['unit'])->toBe(100);
    expect($out['total_akhir'])->toBe('74900.00');
});

