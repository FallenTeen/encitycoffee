<?php

namespace App\Http\Controllers;

use App\Models\AntrianSinkronisasi;
use App\Models\Shift;
use App\Services\TransaksiService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SinkronisasiController extends Controller
{
    public function __construct(private TransaksiService $transaksiService) {}

    public function tambahKeAntrian(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_perangkat' => 'required|string|max:191',
            'tipe_entitas' => 'required|string|in:transaksi,mutasi_stok,shift',
            'id_entitas' => 'required',
            'payload' => 'required',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()], 422);
        }

        // The payload has to be a JSON object here, not just at processing time, so
        // a device that queued something the server will never accept finds out now.
        $payload = is_string($request->payload)
            ? json_decode($request->payload, true)
            : $request->payload;

        if (! is_array($payload) || $payload === []) {
            return response()->json(['error' => 'Payload harus berupa objek JSON yang valid.'], 422);
        }

        $antrian = AntrianSinkronisasi::create([
            'id_perangkat' => $request->id_perangkat,
            // Ownership is stamped server side. A client can only ever write into its
            // own queue, and the processor only ever reads its own queue.
            'user_id' => $request->user()->id,
            'tipe_entitas' => $request->tipe_entitas,
            'id_entitas' => $request->id_entitas,
            'payload' => json_encode($payload),
            'status' => 'pending',
            'jumlah_percobaan' => 0,
        ]);

        return response()->json(['antrian' => $antrian]);
    }

    /**
     * Apply queued offline work.
     *
     * This used to run Transaksi::create($payload), MutasiStok::create($payload) and
     * Shift::where('id', ...)->update($payload) straight from the client's JSON. That
     * let a kasir token write an arbitrary total, shift_id, cabang_id, user_id or
     * status into any of three financial tables, and flip any shift to "tutup" with a
     * chosen selisih, while bypassing every check in TransaksiService.
     *
     * A client payload is now only ever used as *input to the same validated path the
     * online endpoint uses*. Anything that cannot be replayed that way is refused and
     * parked as failed instead of being written.
     */
    public function prosesAntrian(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_perangkat' => 'required|string|max:191',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()], 422);
        }

        $user = $request->user();
        $idPerangkat = $request->get('id_perangkat');

        $items = AntrianSinkronisasi::pending()
            ->where('user_id', $user->id)
            ->where('id_perangkat', $idPerangkat)
            ->get();

        $sukses = 0;
        $gagal = 0;
        $ditolak = 0;

        foreach ($items as $it) {
            $payload = json_decode($it->payload, true);

            try {
                if (! is_array($payload) || $payload === []) {
                    throw ValidationException::withMessages([
                        'payload' => 'Payload bukan objek JSON yang valid.',
                    ]);
                }

                match ($it->tipe_entitas) {
                    'transaksi' => $this->sinkronkanTransaksi($user, $payload),
                    // Stock movement is deliberately not replayed: deducting stock is
                    // out of scope for this work, and applying it here would reintroduce
                    // the same unvalidated-write problem on a different table.
                    'mutasi_stok' => throw ValidationException::withMessages([
                        'tipe_entitas' => 'Sinkronisasi mutasi stok tidak didukung.',
                    ]),
                    // A client must never write shift columns. saldo_akhir and selisih
                    // are a cashier's physical count and are derived server side.
                    'shift' => throw ValidationException::withMessages([
                        'tipe_entitas' => 'Sinkronisasi perubahan shift tidak didukung.',
                    ]),
                    default => throw ValidationException::withMessages([
                        'tipe_entitas' => 'Tipe entitas tidak dikenal.',
                    ]),
                };

                $it->status = 'tersinkronisasi';
                $it->waktu_sinkronisasi = now();
                $it->save();

                $sukses++;
            } catch (ValidationException $e) {
                // Permanently invalid: retrying the same bytes cannot help, so fail fast
                // instead of burning three attempts and hiding the reason.
                $it->status = 'gagal';
                $it->save();

                $ditolak++;

                Log::warning('Item sinkronisasi ditolak karena tidak valid', [
                    'antrian_id' => (int) $it->id,
                    'tipe_entitas' => $it->tipe_entitas,
                    'user_id' => (int) $user->id,
                    'alasan' => $e->errors(),
                ]);
            } catch (QueryException $e) {
                // Infrastructure problem: worth another attempt later.
                $it->jumlah_percobaan += 1;
                $it->status = $it->jumlah_percobaan >= 3 ? 'gagal' : 'pending';
                $it->save();

                $gagal++;

                Log::error('Item sinkronisasi gagal karena masalah database', [
                    'antrian_id' => (int) $it->id,
                    'tipe_entitas' => $it->tipe_entitas,
                    'user_id' => (int) $user->id,
                    'percobaan' => $it->jumlah_percobaan,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
                // Rejected by a business rule (403/404/409/422). Deterministic, so the
                // item is failed rather than retried.
                $it->status = 'gagal';
                $it->save();

                $ditolak++;

                Log::warning('Item sinkronisasi ditolak oleh aturan bisnis', [
                    'antrian_id' => (int) $it->id,
                    'tipe_entitas' => $it->tipe_entitas,
                    'user_id' => (int) $user->id,
                    'status' => $e->getStatusCode(),
                    'alasan' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'jumlah_berhasil' => $sukses,
            'jumlah_gagal' => $gagal,
            'jumlah_ditolak' => $ditolak,
        ]);
    }

    /**
     * Replay one queued sale through the exact same service call the online endpoint
     * makes, so the offline path inherits the shift lock, ownership check, status
     * check, payment sufficiency check and idempotency key handling.
     */
    private function sinkronkanTransaksi($user, array $payload): void
    {
        $validated = validator($payload, [
            'shift_id' => 'required|integer|exists:shift,id',
            'client_transaction_id' => 'required|string|uuid|max:36',
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
            'pembayaran' => 'required|array|min:1',
            'pembayaran.*.metode' => 'required|in:tunai,qris,transfer',
            'pembayaran.*.jumlah' => 'required|numeric|min:0',
            'pembayaran.*.referensi' => 'nullable|string',
            'diskon' => 'nullable|numeric|min:0',
            'diskon_persen' => 'nullable|numeric|min:0|max:100',
            'pembulatan' => 'nullable|array',
            'pajak' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'nama_pelanggan' => 'nullable|string|max:255',
        ])->validate();

        $shift = Shift::findOrFail($validated['shift_id']);

        $this->transaksiService->buatTransaksi(
            $shift,
            $validated['items'],
            $validated['pembayaran'],
            array_key_exists('diskon', $validated) ? (float) $validated['diskon'] : null,
            array_key_exists('diskon_persen', $validated) ? (float) $validated['diskon_persen'] : null,
            $validated['pembulatan'] ?? [],
            (float) ($validated['pajak'] ?? 0),
            $validated['catatan'] ?? null,
            $validated['nama_pelanggan'] ?? null,
            $validated['client_transaction_id'],
            $user,
        );
    }

    public function statusSinkronisasi(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id_perangkat' => 'required|string|max:191',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()], 422);
        }

        $user = $request->user();
        $idPerangkat = $request->get('id_perangkat');

        // Scoped to the caller's own queue: id_perangkat is client supplied, so
        // filtering on it alone exposed any device's pending payloads.
        $antrian = AntrianSinkronisasi::where('user_id', $user->id)
            ->where('id_perangkat', $idPerangkat);

        $jumlah_pending = (clone $antrian)->where('status', 'pending')->count();
        $jumlah_tersinkronisasi = (clone $antrian)->where('status', 'tersinkronisasi')->count();
        $jumlah_gagal = (clone $antrian)->where('status', 'gagal')->count();

        $pending_items = (clone $antrian)->where('status', 'pending')
            ->orderByDesc('created_at')->take(10)->get();

        $last_sync = (clone $antrian)->whereNotNull('waktu_sinkronisasi')
            ->orderByDesc('waktu_sinkronisasi')->first();

        return response()->json([
            'id_perangkat' => $idPerangkat,
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
