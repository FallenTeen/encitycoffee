<?php
namespace App\Http\Controllers;

use App\Models\AntrianSinkronisasi;
use App\Models\Transaksi;
use App\Models\MutasiStok;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SinkronisasiController extends Controller
{
    public function tambahKeAntrian(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_perangkat' => 'required|string',
            'tipe_entitas' => 'required|string',
            'id_entitas' => 'required',
            'payload' => 'required',
        ]);

        if ($v->fails()) return response()->json(['error' => $v->errors()], 422);

        $antrian = AntrianSinkronisasi::create([
            'id_perangkat' => $request->id_perangkat,
            'tipe_entitas' => $request->tipe_entitas,
            'id_entitas' => $request->id_entitas,
            'payload' => $request->payload,
            'status' => 'pending',
            'jumlah_percobaan' => 0,
        ]);

        return response()->json(['antrian' => $antrian]);
    }

    public function prosesAntrian(Request $request)
    {
        $id_perangkat = $request->get('id_perangkat');
        if (! $id_perangkat) return response()->json(['error' => 'id_perangkat required'], 422);

        $items = AntrianSinkronisasi::pending()->where('id_perangkat', $id_perangkat)->get();

        $sukses = 0; $gagal = 0;

        foreach ($items as $it) {
            DB::beginTransaction();
            try {
                $payload = json_decode($it->payload, true);
                if (! $payload) throw new \Exception('Invalid payload JSON');

                if ($it->tipe_entitas === 'transaksi') {
                    // naive create: assume payload matches Transaksi::create structure
                    Transaksi::create($payload);
                } elseif ($it->tipe_entitas === 'mutasi_stok') {
                    MutasiStok::create($payload);
                } elseif ($it->tipe_entitas === 'shift') {
                    Shift::where('id', $it->id_entitas)->update($payload);
                }

                $it->status = 'tersinkronisasi';
                $it->waktu_sinkronisasi = now();
                $it->save();

                DB::commit();
                $sukses++;
            } catch (\Exception $e) {
                DB::rollBack();
                $it->jumlah_percobaan += 1;
                if ($it->jumlah_percobaan >= 3) $it->status = 'gagal';
                $it->save();
                // ideally log $e->getMessage()
                $gagal++;
            }
        }

        return response()->json(['jumlah_berhasil' => $sukses, 'jumlah_gagal' => $gagal]);
    }

    public function statusSinkronisasi(Request $request)
    {
        $id_perangkat = $request->get('id_perangkat');
        if (! $id_perangkat) return response()->json(['error' => 'id_perangkat required'], 422);

        $jumlah_pending = AntrianSinkronisasi::where('id_perangkat', $id_perangkat)->where('status', 'pending')->count();
        $jumlah_tersinkronisasi = AntrianSinkronisasi::where('id_perangkat', $id_perangkat)->where('status', 'tersinkronisasi')->count();
        $jumlah_gagal = AntrianSinkronisasi::where('id_perangkat', $id_perangkat)->where('status', 'gagal')->count();

        $pending_items = AntrianSinkronisasi::where('id_perangkat', $id_perangkat)->where('status', 'pending')->orderByDesc('created_at')->take(10)->get();
        $last_sync = AntrianSinkronisasi::where('id_perangkat', $id_perangkat)->whereNotNull('waktu_sinkronisasi')->orderByDesc('waktu_sinkronisasi')->first();

        return response()->json([
            'id_perangkat' => $id_perangkat,
            'statistik' => [
                'pending' => $jumlah_pending,
                'tersinkronisasi' => $jumlah_tersinkronisasi,
                'gagal' => $jumlah_gagal,
            ],
            'item_pending' => $pending_items,
            'terakhir_sinkronisasi' => $last_sync ? $last_sync->waktu_sinkronisasi : null,
        ]);
    }
}
