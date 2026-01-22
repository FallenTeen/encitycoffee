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

        $nama = trim((string) ($user->name ?? ''));
        $email = trim((string) ($user->email ?? ''));

        $shift = Shift::where('status', 'buka')
            ->where(function ($query) use ($user, $nama, $email) {
                $query->where('user_id', $user->id);

                if ($nama !== '' || $email !== '') {
                    $query->orWhere(function ($q) use ($nama, $email) {
                        $q->whereNotNull('nama_kasir');
                        $q->where(function ($qq) use ($nama, $email) {
                            if ($nama !== '') {
                                $qq->orWhere('nama_kasir', 'like', '%' . $nama . '%');
                            }
                            if ($email !== '') {
                                $qq->orWhere('nama_kasir', 'like', '%' . $email . '%');
                            }
                        });
                    });
                }
            })
            ->orderByDesc('waktu_buka')
            ->first();

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
        // ===================================================================
        // VALIDASI INPUT
        // ===================================================================
        $v = Validator::make($request->all(), [
            'saldo_akhir' => 'required|numeric|min:0',
            'total_tunai' => 'nullable|numeric|min:0',
            'total_qris' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($v->fails()) {
            return response()->json([
                'error' => 'Data tidak valid',
                'details' => $v->errors()
            ], 422);
        }

        // ===================================================================
        // GET USER & LOG REQUEST
        // ===================================================================
        $user = $request->user() ?? auth()->user();

        Log::info('tutupShift request', [
            'shift_id' => $shift->id,
            'shift_user_id' => $shift->user_id,
            'shift_status' => $shift->status,
            'auth_user_id' => $user?->id,
            'saldo_akhir' => $request->saldo_akhir,
        ]);

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ===================================================================
        // CHECK SHIFT STATUS - FLEXIBLE
        // ===================================================================
        // Jika sudah ditutup, return sukses dengan data yang ada
        // (Untuk handle double-click atau retry dari mobile)
        if ($shift->status === 'tutup') {
            Log::info('Shift already closed, returning existing data', [
                'shift_id' => $shift->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Shift sudah ditutup sebelumnya',
                'shift' => $shift
            ], 200);
        }

        if ($shift->status !== 'buka') {
            return response()->json([
                'error' => 'Shift tidak dapat ditutup (status: ' . $shift->status . ')'
            ], 400);
        }

        // ===================================================================
        // PERMISSION CHECK - FLEXIBLE TAPI AMAN
        // ===================================================================
        $isOwner = ((int) $shift->user_id === (int) $user->id);
        $userRole = strtolower((string) ($user->role ?? ''));

        // Role-based permissions
        $isSupervisor = $userRole === 'supervisor';
        $isManager = $userRole === 'manager';
        $isItSupport = $userRole === 'it_support';

        // Base permission check
        $hasPermission = $isOwner || $isSupervisor || $isManager || $isItSupport;

        if (!$hasPermission) {
            Log::warning('Unauthorized shift close attempt', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'shift_id' => $shift->id,
                'shift_user_id' => $shift->user_id,
            ]);

            return response()->json([
                'error' => 'Anda tidak memiliki izin untuk menutup shift ini'
            ], 403);
        }

        // Cabang validation (kecuali IT Support)
        if (!$isItSupport && !$isOwner) {
            $cabangIds = $user->cabang()->pluck('cabang.id')->all();

            if (!in_array($shift->cabang_id, $cabangIds, true)) {
                Log::warning('Cross-cabang shift close attempt', [
                    'user_id' => $user->id,
                    'user_role' => $userRole,
                    'shift_cabang_id' => $shift->cabang_id,
                    'user_cabang_ids' => $cabangIds,
                ]);

                return response()->json([
                    'error' => 'Anda tidak memiliki akses ke cabang ini'
                ], 403);
            }
        }

        // Log permission override
        if (!$isOwner) {
            Log::info('Shift closed by supervisor/manager', [
                'shift_id' => $shift->id,
                'shift_owner_id' => $shift->user_id,
                'closed_by_user_id' => $user->id,
                'closed_by_role' => $userRole,
            ]);
        }

        // ===================================================================
        // TUTUP SHIFT - DENGAN TRANSACTION & LOCK
        // ===================================================================
        DB::beginTransaction();
        try {
            // Pessimistic lock untuk prevent race condition
            $shiftLocked = Shift::where('id', $shift->id)
                ->lockForUpdate()
                ->first();

            if (!$shiftLocked) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Shift tidak ditemukan'
                ], 404);
            }

            // Double-check status setelah lock
            if ($shiftLocked->status === 'tutup') {
                DB::rollBack();

                Log::info('Shift already closed after lock', [
                    'shift_id' => $shiftLocked->id,
                ]);

                return response()->json([
                    'message' => 'Shift sudah ditutup sebelumnya',
                    'shift' => $shiftLocked
                ], 200);
            }

            // ===================================================================
            // HITUNG TOTAL DARI TRANSAKSI
            // ===================================================================
            $transaksiSelesai = $shiftLocked->transaksi()
                ->where('status', 'selesai')
                ->get();

            // Jika user kirim total_tunai & total_qris, pakai itu
            // Jika tidak, hitung dari transaksi
            if ($request->filled('total_tunai') && $request->filled('total_qris')) {
                $total_tunai = (float) $request->total_tunai;
                $total_qris = (float) $request->total_qris;

                Log::info('Using manual totals from request', [
                    'total_tunai' => $total_tunai,
                    'total_qris' => $total_qris,
                ]);
            } else {
                $total_tunai = (float) $transaksiSelesai->sum('total_tunai');
                $total_qris = (float) $transaksiSelesai->sum('total_qris');

                Log::info('Calculated totals from transactions', [
                    'total_tunai' => $total_tunai,
                    'total_qris' => $total_qris,
                    'transaction_count' => $transaksiSelesai->count(),
                ]);
            }

            // Hitung saldo diharapkan dan selisih
            $saldo_diharapkan = $shiftLocked->saldo_awal + $total_tunai;
            $selisih = $request->saldo_akhir - $saldo_diharapkan;

            // ===================================================================
            // UPDATE SHIFT
            // ===================================================================
            $shiftLocked->update([
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

            Log::info('Shift closed successfully', [
                'shift_id' => $shiftLocked->id,
                'closed_by_user_id' => $user->id,
                'saldo_awal' => $shiftLocked->saldo_awal,
                'saldo_akhir' => $request->saldo_akhir,
                'saldo_diharapkan' => $saldo_diharapkan,
                'selisih' => $selisih,
                'total_tunai' => $total_tunai,
                'total_qris' => $total_qris,
            ]);

            // Reload shift untuk response
            $shiftLocked->refresh();
            $shiftLocked->load(['cabang', 'user']);

            return response()->json([
                'message' => 'Shift berhasil ditutup',
                'shift' => $shiftLocked
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to close shift', [
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Gagal menutup shift. Silakan coba lagi.',
                'details' => $e->getMessage()
            ], 500);
        }
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
        $query = Shift::with(['user', 'cabang']);

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
