<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobileBundlingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            // --- Resolve cabang ID ---
            $cabangId = $request->input('cabang_id');

            if (!$cabangId) {
                return response()->json([
                    'success' => false,
                    'message' => 'cabang_id diperlukan',
                ], 400);
            }

            // Validate user has access to this cabang
            if ($user->role !== 'it_support') {
                $user->load('cabang:id');
                $allowedCabangIds = $user->cabang->pluck('id')->all();
                if (!in_array((int) $cabangId, $allowedCabangIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akses cabang tidak diizinkan',
                    ], 403);
                }
            }

            // --- Fetch active bundling products with their items ---
            $bundlings = Produk::with([
                    'bundleItems.produk:id,nama,harga_jual,tipe',
                ])
                ->where('tipe', 'bundling')
                ->where('aktif', true)
                ->orderBy('nama')
                ->get();

            $result = $bundlings->map(function ($bundling) {
                // Calculate real total from constituent products
                $totalHargaReal = $bundling->bundleItems->reduce(function ($carry, $item) {
                    return $carry + (($item->produk->harga_jual ?? 0) * $item->jumlah);
                }, 0);

                return [
                    'id'              => $bundling->id,
                    'sku'             => $bundling->sku,
                    'nama'            => $bundling->nama,
                    'deskripsi'       => $bundling->deskripsi,
                    'harga_jual'      => (float) $bundling->harga_jual,
                    'harga_real_total'=> (float) $totalHargaReal,
                    'potongan'        => (float) max(0, $totalHargaReal - $bundling->harga_jual),
                    'tipe'            => 'bundling',
                    'aktif'           => $bundling->aktif,
                    'image_path'      => $bundling->image_path,
                    'image_url'       => $bundling->image_path
                        ? url('storage/' . ltrim($bundling->image_path, '/'))
                        : null,
                    'bundle_items'    => $bundling->bundleItems->map(function ($item) {
                        return [
                            'id'           => $item->id,
                            'produk_id'    => $item->produk_id,
                            'nama'         => $item->produk->nama ?? '-',
                            'jumlah'       => $item->jumlah,
                            'harga_jual'   => (float) ($item->produk->harga_jual ?? 0),
                            'subtotal'     => (float) (($item->produk->harga_jual ?? 0) * $item->jumlah),
                        ];
                    })->values(),
                ];
            });

            return response()->json([
                'success'  => true,
                'bundling' => $result,
                'total'    => $result->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile Bundling API Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data bundling',
            ], 500);
        }
    }
}
