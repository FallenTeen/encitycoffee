<?php

namespace App\Http\Controllers;

use App\Models\OpenBill;
use App\Services\ReceiptDiscountService;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function __construct(private readonly ReceiptDiscountService $discountService)
    {
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'total_awal' => ['required', 'numeric', 'min:0'],
            'pajak' => ['nullable', 'numeric', 'min:0'],
            'diskon_nominal' => ['nullable', 'numeric', 'min:0'],
            'diskon_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pembulatan' => ['nullable', 'array'],
            'pembulatan.mode' => ['nullable', 'in:none,nearest,up,down'],
            'pembulatan.unit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        if (! array_key_exists('diskon_nominal', $validated) && ! array_key_exists('diskon_persen', $validated)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'diskon_nominal atau diskon_persen wajib diisi',
                ],
            ], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'data' => $this->discountService->preview($validated),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }

    public function applyToOpenBill(OpenBill $openBill, Request $request)
    {
        $validated = $request->validate([
            'diskon_nominal' => ['nullable', 'numeric', 'min:0'],
            'diskon_persen' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pembulatan' => ['nullable', 'array'],
            'pembulatan.mode' => ['nullable', 'in:none,nearest,up,down'],
            'pembulatan.unit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        if ($openBill->status !== 'open') {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Open bill sudah tidak aktif'],
            ], 400);
        }
        $shift = $openBill->shift;
        if (! $shift) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Shift tidak ditemukan'],
            ], 400);
        }
        if ($shift->status !== 'buka') {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Shift tidak terbuka'],
            ], 400);
        }

        if (! array_key_exists('diskon_nominal', $validated) && ! array_key_exists('diskon_persen', $validated)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'diskon_nominal atau diskon_persen wajib diisi',
                ],
            ], 422);
        }

        try {
            $calc = $this->discountService->preview(array_merge($validated, [
                'total_awal' => (string) $openBill->subtotal,
                'pajak' => (string) $openBill->pajak,
            ]));

            $openBill->update([
                'diskon' => $calc['diskon_nominal'],
                'diskon_persen' => $calc['diskon_persen'],
                'diskon_rounding_mode' => $calc['pembulatan']['mode'],
                'diskon_rounding_unit' => $calc['pembulatan']['unit'],
                'diskon_rounding_delta' => $calc['pembulatan']['diskon_delta'],
                'total' => $calc['total_akhir'],
            ]);

            $openBill->addAuditLog('discount_update', [
                'total_awal' => $calc['total_awal'],
                'diskon_nominal' => $calc['diskon_nominal'],
                'diskon_persen' => $calc['diskon_persen'],
                'total_akhir' => $calc['total_akhir'],
                'pembulatan' => $calc['pembulatan'],
            ]);

            $openBill->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'kalkulasi' => $calc,
                    'open_bill' => $openBill->load(['items.produk', 'shift', 'cabang', 'user']),
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }
    }
}

