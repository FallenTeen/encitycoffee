<?php

namespace App\Http\Controllers;

use App\Models\Kalibrasi;
use App\Models\MutasiStok;
use App\Models\Produk;
use App\Models\Shift;
use App\Models\StokEtalase;
use App\Support\ApiErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KalibrasiController extends Controller
{
    public function simpan(Request $request)
    {
        return $this->simpanKalibrasi($request);
    }

    public function pilih(Kalibrasi $kalibrasi)
    {
        return $this->pilihKalibrasi($kalibrasi);
    }

    public function dapatkanBerdasarkanShift(Shift $shift)
    {
        return $this->ambilKalibrasiPerShift($shift);
    }

    public function simpanKalibrasi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'shift_id' => 'required|integer|exists:shift,id',
            'produk_id' => 'required|integer|exists:produk,id',
            'nomor_percobaan' => 'required|integer|min:1',
            'berat_beans_gram' => 'required|numeric|min:0',
            'terpilih' => 'nullable|boolean',
            'catatan' => 'nullable|string',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()], 422);
        }

        $user = $request->user() ?? auth()->user();
        $shift = Shift::findOrFail($request->shift_id);
        $produk = Produk::findOrFail($request->produk_id);

        if (! $user || $shift->user_id != $user->id) {
            return response()->json(['error' => 'Tidak memiliki akses'], 403);
        }

        DB::beginTransaction();
        try {
            $kalibrasi = Kalibrasi::create([
                'shift_id' => $shift->id,
                'produk_id' => $produk->id,
                'nomor_percobaan' => $request->nomor_percobaan,
                'berat_beans_gram' => $request->berat_beans_gram,
                'terpilih' => $request->filled('terpilih') ? (bool) $request->terpilih : false,
                'catatan' => $request->catatan,
            ]);

            $jumlah_kg = $request->berat_beans_gram / 1000.0;

            $stok = StokEtalase::where('cabang_id', $shift->cabang_id)
                ->where('produk_id', $produk->id)
                ->where('tipe_stok', 'produksi_minuman')
                ->first();

            if (! $stok || $stok->jumlah < $jumlah_kg) {
                throw new \Exception('Stok tidak mencukupi untuk kalibrasi');
            }

            $jumlah_sebelum = $stok->jumlah;
            $stok->jumlah = $stok->jumlah - $jumlah_kg;
            $stok->save();

            MutasiStok::create([
                'stok_etalase_id' => $stok->id,
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'tipe' => 'kalibrasi',
                'jumlah_sebelum' => $jumlah_sebelum,
                'jumlah_sesudah' => $stok->jumlah,
                'jumlah_perubahan' => -$jumlah_kg,
                'catatan' => "Kalibrasi percobaan #{$request->nomor_percobaan} - {$request->berat_beans_gram}g",
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan kalibrasi', [
                'user_id' => $request->user()?->id,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return ApiErrorResponse::make(500, ApiErrorResponse::GENERIC_SERVER_ERROR);
        }

        $kalibrasi->load('produk');

        return response()->json(['kalibrasi' => $kalibrasi]);
    }

    public function pilihKalibrasi(Kalibrasi $kalibrasi)
    {
        DB::beginTransaction();
        try {
            Kalibrasi::where('shift_id', $kalibrasi->shift_id)
                ->where('produk_id', $kalibrasi->produk_id)
                ->update(['terpilih' => false]);

            $kalibrasi->terpilih = true;
            $kalibrasi->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['kalibrasi' => $kalibrasi]);
    }

    public function ambilKalibrasiPerShift(Shift $shift)
    {
        $data = Kalibrasi::where('shift_id', $shift->id)
            ->with('produk')
            ->orderBy('nomor_percobaan')
            ->get();

        return response()->json(['kalibrasi' => $data]);
    }
}
