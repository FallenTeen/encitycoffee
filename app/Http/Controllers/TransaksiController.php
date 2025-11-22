<?php
namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Models\Pembayaran;
use App\Models\Shift;
use App\Models\Produk;
use App\Models\StokEtalase;
use App\Models\Kalibrasi;
use App\Models\MutasiStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function buatTransaksi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'shift_id' => 'required|integer|exists:shift,id',
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|integer|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'pembayaran' => 'required|array|min:1',
            'pembayaran.*.metode' => 'required|in:tunai,qris',
            'pembayaran.*.jumlah' => 'required|numeric|min:0',
            'diskon' => 'nullable|numeric|min:0',
            'pajak' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $user = $request->user() ?? auth()->user();
        $shift = Shift::findOrFail($request->shift_id);

        if ($shift->status !== 'buka') return response()->json(['error' => 'Shift tidak terbuka'], 400);
        if (! $user || $shift->user_id != $user->id) return response()->json(['error' => 'Tidak memiliki akses'], 403);

        DB::beginTransaction();
        try {
            // generate nomor invoice (unique)
            $nomor = uniqid('INV-');

            $subtotal = 0;
            $item_transaksi_data = [];

            foreach ($request->items as $it) {
                $produk = Produk::findOrFail($it['produk_id']);
                $subtotal_item = $produk->harga_jual * $it['jumlah'];
                $subtotal += $subtotal_item;

                $item_transaksi_data[] = [
                    'produk' => $produk,
                    'jumlah' => $it['jumlah'],
                    'harga_satuan' => $produk->harga_jual,
                    'subtotal' => $subtotal_item,
                    'catatan' => $it['catatan'] ?? null,
                ];
            }

            $diskon = $request->get('diskon', 0);
            $pajak = $request->get('pajak', 0);
            $total = $subtotal - $diskon + $pajak;

            $total_pembayaran = collect($request->pembayaran)->sum('jumlah');
            if ($total_pembayaran < $total) throw new \Exception('Jumlah pembayaran kurang');

            $transaksi = Transaksi::create([
                'shift_id' => $shift->id,
                'cabang_id' => $shift->cabang_id,
                'user_id' => $user->id,
                'nomor_invoice' => $nomor,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'pajak' => $pajak,
                'total' => $total,
                'status' => 'selesai',
                'waktu_selesai' => now(),
                'catatan' => $request->catatan ?? null,
            ]);

            foreach ($item_transaksi_data as $it) {
                $item = ItemTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $it['produk']->id,
                    'jumlah' => $it['jumlah'],
                    'harga_satuan' => $it['harga_satuan'],
                    'subtotal' => $it['subtotal'],
                    'catatan' => $it['catatan'],
                ]);

                $produk = $it['produk'];

                if ($produk->tipe === 'minuman') {
                    $kalibrasi = Kalibrasi::where('shift_id', $shift->id)
                        ->where('produk_id', $produk->id)
                        ->where('terpilih', true)
                        ->first();

                    if (! $kalibrasi) throw new \Exception('Belum ada kalibrasi terpilih');

                    $total_beans_gram = $kalibrasi->berat_beans_gram * $it['jumlah'];
                    $total_beans_kg = $total_beans_gram / 1000.0;

                    $stok = StokEtalase::where('cabang_id', $shift->cabang_id)
                        ->where('produk_id', $produk->id)
                        ->where('tipe_stok', 'produksi_minuman')
                        ->first();

                    if (! $stok || $stok->jumlah < $total_beans_kg) throw new \Exception('Stok beans tidak mencukupi');

                    $jumlah_sebelum = $stok->jumlah;
                    $stok->jumlah -= $total_beans_kg;
                    $stok->save();

                    MutasiStok::create([
                        'stok_etalase_id' => $stok->id,
                        'user_id' => $user->id,
                        'shift_id' => $shift->id,
                        'tipe' => 'penjualan',
                        'jumlah_sebelum' => $jumlah_sebelum,
                        'jumlah_sesudah' => $stok->jumlah,
                        'jumlah_perubahan' => -$total_beans_kg,
                        'catatan' => "Penjualan minuman (transaksi {$transaksi->id})",
                    ]);
                }

                if ($produk->tipe === 'beans') {
                    $stok = StokEtalase::where('cabang_id', $shift->cabang_id)
                        ->where('produk_id', $produk->id)
                        ->where('tipe_stok', 'penjualan_retail')
                        ->first();

                    if ($stok) {
                        $jumlah_sebelum = $stok->jumlah;
                        $stok->jumlah -= $it['jumlah'];
                        $stok->save();
                        MutasiStok::create([
                            'stok_etalase_id' => $stok->id,
                            'user_id' => $user->id,
                            'shift_id' => $shift->id,
                            'tipe' => 'penjualan',
                            'jumlah_sebelum' => $jumlah_sebelum,
                            'jumlah_sesudah' => $stok->jumlah,
                            'jumlah_perubahan' => -$it['jumlah'],
                            'catatan' => "Penjualan beans (transaksi {$transaksi->id})",
                        ]);
                    }
                }

                if ($produk->tipe === 'snack') {
                    $stok = StokEtalase::where('cabang_id', $shift->cabang_id)
                        ->where('produk_id', $produk->id)
                        ->where('tipe_stok', 'produksi_minuman')
                        ->first();

                    if ($stok) {
                        $jumlah_sebelum = $stok->jumlah;
                        $stok->jumlah -= $it['jumlah'];
                        $stok->save();
                        MutasiStok::create([
                            'stok_etalase_id' => $stok->id,
                            'user_id' => $user->id,
                            'shift_id' => $shift->id,
                            'tipe' => 'penjualan',
                            'jumlah_sebelum' => $jumlah_sebelum,
                            'jumlah_sesudah' => $stok->jumlah,
                            'jumlah_perubahan' => -$it['jumlah'],
                            'catatan' => "Penjualan snack (transaksi {$transaksi->id})",
                        ]);
                    }
                }
            }

            foreach ($request->pembayaran as $p) {
                Pembayaran::create([
                    'transaksi_id' => $transaksi->id,
                    'metode_pembayaran' => $p['metode'],
                    'jumlah' => $p['jumlah'],
                    'nomor_referensi' => $p['referensi'] ?? null,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $transaksi->load(['item.produk','pembayaran']);
        return response()->json(['transaksi' => $transaksi]);
    }

    public function tampilkanTransaksi(Transaksi $transaksi)
    {
        $transaksi->load(['item.produk','pembayaran','shift.user','cabang']);
        return response()->json(['transaksi' => $transaksi]);
    }

    public function batalkanTransaksi(Request $request, Transaksi $transaksi)
    {
        $user = $request->user();

        if ($transaksi->status === 'batal') throw new \Exception('Transaksi sudah dibatalkan');

        DB::beginTransaction();
        try {
            $transaksi->status = 'batal';
            $transaksi->catatan = ($transaksi->catatan ?? '') . " | Dibatalkan oleh {$user->name} pada " . now();
            $transaksi->save();

            foreach ($transaksi->item as $item) {
                $produk = $item->produk;
                if ($produk->tipe === 'minuman') continue; // skip

                $tipe_stok = $produk->tipe === 'beans' ? 'penjualan_retail' : 'produksi_minuman';

                $stok = StokEtalase::where('cabang_id', $transaksi->cabang_id)
                    ->where('produk_id', $produk->id)
                    ->where('tipe_stok', $tipe_stok)
                    ->first();

                if ($stok) {
                    $jumlah_sebelum = $stok->jumlah;
                    $stok->jumlah += $item->jumlah;
                    $stok->save();

                    MutasiStok::create([
                        'stok_etalase_id' => $stok->id,
                        'user_id' => $user->id,
                        'shift_id' => $transaksi->shift_id,
                        'tipe' => 'return',
                        'jumlah_sebelum' => $jumlah_sebelum,
                        'jumlah_sesudah' => $stok->jumlah,
                        'jumlah_perubahan' => $item->jumlah,
                        'catatan' => "Return dari pembatalan transaksi {$transaksi->id}",
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['transaksi' => $transaksi]);
    }

    public function transaksiPerShift(Shift $shift)
    {
        $data = Transaksi::with(['item.produk','pembayaran'])
            ->where('shift_id', $shift->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['transaksi' => $data]);
    }
}
