<?php
namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Cabang;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShiftController extends Controller
{
    public function dapatkanAktif(Request $request)
    {
        return $this->ambilShiftAktif($request);
    }

    public function buka(Request $request)
    {
        return $this->bukaShift($request);
    }

    public function tutup(Request $request, Shift $shift)
    {
        return $this->tutupShift($request, $shift);
    }

    public function tampilkan(Shift $shift)
    {
        return $this->tampilkanShift($shift);
    }

    public function daftar(Request $request)
    {
        return $this->daftarShift($request);
    }

    public function ambilShiftAktif(Request $request)
    {
        $user = $request->user();

        $shift = Shift::where('user_id', $user->id)->where('status', 'buka')->first();

        if (! $shift) {
            return response()->json(['error' => 'Tidak ada shift aktif'], 404);
        }

        $shift->load(['cabang', 'user', 'kalibrasi.produk']);

        return response()->json(['shift' => $shift]);
    }

    public function bukaShift(Request $request)
    {
        $v = Validator::make($request->all(), [
            'cabang_id' => 'required|integer',
            'saldo_awal' => 'required|numeric|min:0|max:999999999.99',
            'nama_kasir' => 'nullable|string|max:255',
        ]);

        if ($v->fails()) {
            Log::error('Validasi buka shift gagal', [
                'user_id' => $request->user()->id,
                'errors' => $v->errors(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $v->errors()], 422);
        }

        $user = $request->user();
        $cabang = Cabang::find($request->cabang_id);

        if (!$cabang) {
            Log::error('Cabang tidak ditemukan', [
                'user_id' => $user->id,
                'cabang_id' => $request->cabang_id
            ]);
            return response()->json(['error' => 'Cabang tidak valid'], 400);
        }

        $existing = Shift::where('user_id', $user->id)->where('status', 'buka')->first();
        if ($existing) {
            Log::warning('Percobaan buka shift ganda', [
                'user_id' => $user->id,
                'existing_shift_id' => $existing->id
            ]);
            return response()->json(['error' => 'User sudah memiliki shift yang masih buka'], 400);
        }

        DB::beginTransaction();
        try {
            $saldoAwal = (float) $request->saldo_awal;
            if (!is_numeric($saldoAwal) || $saldoAwal < 0) {
                throw new \InvalidArgumentException('Saldo awal harus berupa angka positif');
            }

            $namaKasir = $request->input('nama_kasir');

            $shift = Shift::create([
                'user_id' => $user->id,
                'cabang_id' => $cabang->id,
                'saldo_awal' => number_format($saldoAwal, 2, '.', ''),
                'waktu_buka' => Carbon::now(),
                'status' => 'buka',
                'nama_kasir' => $namaKasir !== null && $namaKasir !== '' ? $namaKasir : $user->name,
                'audit_log' => [[
                    'action' => 'buka_shift',
                    'user_id' => $user->id,
                    'timestamp' => Carbon::now()->toDateTimeString(),
                    'data' => [
                        'saldo_awal' => $saldoAwal,
                        'cabang_id' => $cabang->id,
                        'nama_kasir' => $namaKasir ?? $user->name,
                    ]
                ]]
            ]);

            Log::info('Shift berhasil dibuka', [
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'cabang_id' => $cabang->id,
                'saldo_awal' => $saldoAwal,
                'timestamp' => Carbon::now()
            ]);

            DB::commit();
            $shift->load('cabang');
            return response()->json(['shift' => $shift]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membuka shift', [
                'user_id' => $user->id,
                'cabang_id' => $cabang->id,
                'saldo_awal' => $request->saldo_awal,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Gagal membuka shift: ' . $e->getMessage()], 500);
        }
    }

    public function tutupShift(Request $request, Shift $shift)
    {
        $v = Validator::make($request->all(), [
            'saldo_akhir' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $v->errors()], 422);
        }

        $user = $request->user() ?? auth()->user();

        Log::info('tutupShift debug', [
            'shift_user_id' => $shift->user_id,
            'auth_user_id' => $user?->id,
            'auth_id_helper' => auth()->id(),
            'request_payload' => $request->all(),
            'running_tests' => app()->runningUnitTests(),
        ]);


        try {
            Log::info('tutupShift raw', [
                'shift_class' => get_class($shift),
                'shift_id' => $shift->id,
                'attributes' => $shift->getAttributes(),
            ]);
        } catch (\Throwable $e) {
            Log::error('tutupShift raw logging failed', ['error' => $e->getMessage()]);
        }

        if (! $user) {
            return response()->json(['error' => 'Tidak memiliki akses'], 403);
        }


        $isOwner = ((int) $shift->user_id === (int) $user->id);
        $isSupervisor = method_exists($user, 'isSupervisor') && $user->isSupervisor();
        if (! $isOwner && ! $isSupervisor) {
            return response()->json(['error' => 'Tidak memiliki akses'], 403);
        }

        DB::beginTransaction();
        try {

            $total_tunai = $shift->transaksi()->where('status','selesai')->get()->sum('total_tunai');
            $total_qris = $shift->transaksi()->where('status','selesai')->get()->sum('total_qris');

            $saldo_diharapkan = $shift->saldo_awal + $total_tunai;
            $selisih = $request->saldo_akhir - $saldo_diharapkan;

            $shift->update([
                'saldo_akhir' => $request->saldo_akhir,
                'saldo_diharapkan' => $saldo_diharapkan,
                'selisih' => $selisih,
                'total_tunai' => $total_tunai,
                'total_qris' => $total_qris,
                'waktu_tutup' => Carbon::now(),
                'status' => 'tutup',
                'catatan' => $request->catatan,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $shift->load(['transaksi', 'kalibrasi']);
        return response()->json(['shift' => $shift]);
    }

    public function tampilkanShift(Shift $shift)
    {
        $shift->load([
            'user',
            'cabang',
            'transaksi.item.produk',
            'transaksi.pembayaran',
            'kalibrasi.produk',
            'mutasiStok.stokEtalase.produk'
        ]);

        return response()->json(['shift' => $shift]);
    }

    public function daftarShift(Request $request)
    {
        $user = $request->user();
        $query = Shift::with(['user','cabang']);

        if ($user->isKasir()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isSupervisor()) {
            $cabangIds = $user->cabang()->pluck('cabang.id');
            $query->whereIn('cabang_id', $cabangIds);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('waktu_buka', $request->tanggal);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->get('per_page', 20);
        $data = $query->orderByDesc('waktu_buka')->paginate($perPage);

        return response()->json($data);
    }
}
