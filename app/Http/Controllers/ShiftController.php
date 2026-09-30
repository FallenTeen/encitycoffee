<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\OpenBill;
use App\Models\Pembayaran;
use App\Models\Shift;
use App\Policies\BranchAccessPolicy;
use App\Support\ApiErrorResponse;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

    public function openBills(Request $request, Shift $shift)
    {
        // SECURITY: Verify user has access to this shift's branch
        $policy = new \App\Policies\BranchAccessPolicy;
        $currentUser = $request->user() ?? auth()->user();
        if (! $currentUser || ! $policy->viewShift($currentUser, $shift)) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke shift ini.',
            ], 403);
        }

        $openBills = OpenBill::with(['items.produk'])
            ->where('shift_id', $shift->id)
            ->where('status', 'open')
            ->orderBy('created_at')
            ->get()
            ->map(function (OpenBill $bill) {
                return [
                    'id' => (int) $bill->id,
                    'nomor_open_bill' => (string) $bill->nomor_open_bill,
                    'nama_pelanggan' => $bill->nama_pelanggan,
                    'total' => (float) $bill->total,
                    'subtotal' => (float) $bill->subtotal,
                    'item_count' => $bill->items->count(),
                    'created_at' => $bill->created_at?->toDateTimeString(),
                ];
            });

        return response()->json([
            'shift_id' => (int) $shift->id,
            'has_open_bills' => $openBills->isNotEmpty(),
            'count' => $openBills->count(),
            'open_bills' => $openBills->values(),
        ]);
    }

    public function tampilkan(Shift $shift)
    {
        return $this->tampilkanShift(request(), $shift);
    }

    public function daftar(Request $request)
    {
        return $this->daftarShift($request);
    }

    /**
     * The active shift is identified by the authenticated user, optionally narrowed
     * to the branch the cashier selected.
     *
     * Identity is always `user_id`. Matching on `nama_kasir` (name/email LIKE) was
     * removed: it let one cashier pick up another cashier's shift whenever the
     * display name happened to overlap.
     */
    public function ambilShiftAktif(Request $request)
    {
        $user = $request->user();

        $cabangId = $request->integer('cabang_id');
        if ($cabangId > 0 && ! BranchAccessPolicy::canAccessCabang($user, $cabangId)) {
            Log::warning('Active shift requested for an inaccessible branch', [
                'user_id' => (int) $user->id,
                'cabang_id' => $cabangId,
            ]);

            return ApiErrorResponse::forbidden('Anda tidak memiliki akses ke cabang ini.');
        }

        $query = Shift::where('user_id', $user->id)->where('status', 'buka');

        if ($cabangId > 0) {
            $query->where('cabang_id', $cabangId);
        }

        // The id tiebreaker keeps this deterministic: two shifts opened inside the
        // same second would otherwise be ordered arbitrarily by waktu_buka.
        $shift = $query->orderByDesc('waktu_buka')->orderByDesc('id')->first();

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
                'input' => $request->all(),
            ]);

            return response()->json(['error' => $v->errors()], 422);
        }

        $user = $request->user();
        $cabang = Cabang::find($request->cabang_id);

        if (! $cabang) {
            Log::error('Cabang tidak ditemukan', [
                'user_id' => $user->id,
                'cabang_id' => $request->cabang_id,
            ]);

            return response()->json(['error' => 'Cabang tidak valid'], 400);
        }

        // SECURITY: Verify user has access to this cabang
        if (! BranchAccessPolicy::canAccessCabang($user, (int) $cabang->id)) {
            Log::warning('User mencoba buka shift di cabang yang tidak diizinkan', [
                'user_id' => $user->id,
                'cabang_id' => $cabang->id,
                'user_role' => $user->role,
            ]);

            return response()->json([
                'error' => 'Anda tidak diizinkan membuka shift di cabang ini.',
            ], 403);
        }

        // Friendly duplicate message for the ordinary sequential case. This is UX
        // only: it cannot close the check-then-act race on its own, so the actual
        // guarantee is the shift_active_branch_unique index added by migration
        // 2026_08_01_200003. The concurrent case is recovered in the catch below
        // and returns the exact same response, so the client sees one contract.
        $existing = Shift::where('user_id', $user->id)
            ->where('cabang_id', $cabang->id)
            ->where('status', 'buka')
            ->first();
        if ($existing) {
            Log::warning('Percobaan buka shift ganda', [
                'user_id' => $user->id,
                'cabang_id' => $cabang->id,
                'existing_shift_id' => $existing->id,
            ]);

            return response()->json(['error' => 'User sudah memiliki shift yang masih buka'], 400);
        }

        DB::beginTransaction();
        try {
            $saldoAwal = (float) $request->saldo_awal;
            if (! is_numeric($saldoAwal) || $saldoAwal < 0) {
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
                    ],
                ]],
            ]);

            Log::info('Shift berhasil dibuka', [
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'cabang_id' => $cabang->id,
                'saldo_awal' => $saldoAwal,
                'timestamp' => Carbon::now(),
            ]);

            DB::commit();
            $shift->load('cabang');

            return response()->json(['shift' => $shift]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Lost the race against a concurrent open for the same user+branch.
            if ($this->isActiveShiftConflict($e)) {
                Log::warning('Shift race conflict pada user+cabang yang sama', [
                    'user_id' => $user->id,
                    'cabang_id' => $cabang->id,
                ]);

                return response()->json(['error' => 'User sudah memiliki shift yang masih buka'], 400);
            }

            Log::error('Gagal membuka shift', [
                'user_id' => $user->id,
                'cabang_id' => $cabang->id,
                'saldo_awal' => $request->saldo_awal,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ApiErrorResponse::make(500, ApiErrorResponse::GENERIC_SERVER_ERROR);
        }
    }

    /**
     * True when the failure is the unique index on (user, branch) for open shifts.
     *
     * Scoped to that index on purpose: other integrity violations (a foreign key,
     * a bad enum value) are real server faults and must keep reporting 500 instead
     * of being disguised as "you already have an open shift".
     */
    private function isActiveShiftConflict(\Throwable $e): bool
    {
        if (! $e instanceof \Illuminate\Database\UniqueConstraintViolationException
            && ! ($e instanceof QueryException && $e->getCode() === '23000')) {
            return false;
        }

        return str_contains($e->getMessage(), 'shift_active_branch_unique');
    }

    public function tutupShift(Request $request, Shift $shift)
    {
        Log::info('🔒 SHIFT CLOSE START', [
            'shift_id' => $shift->id,
            'user_id' => auth()->id(),
            'saldo_akhir' => $request->saldo_akhir,
            'timestamp' => now(),
        ]);

        // SECURITY: Verify user has access to this shift's branch
        $policy = new \App\Policies\BranchAccessPolicy;
        $currentUser = $request->user() ?? auth()->user();
        if ($currentUser && ! $policy->viewShift($currentUser, $shift)) {
            Log::warning('User mencoba tutup shift di cabang yang tidak diizinkan', [
                'user_id' => $currentUser->id,
                'shift_id' => $shift->id,
                'shift_cabang_id' => $shift->cabang_id,
            ]);

            return response()->json([
                'error' => 'Anda tidak memiliki akses ke shift ini.',
            ], 403);
        }

        // ===================================================================
        // VALIDASI INPUT
        // ===================================================================
        $v = Validator::make($request->all(), [
            'saldo_akhir' => 'required|numeric|min:0',
            'total_tunai' => 'nullable|numeric|min:0',
            'total_qris' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
            'force_close_open_bills' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            Log::warning('❌ VALIDATION FAILED', ['errors' => $v->errors()]);

            return response()->json([
                'error' => 'Data tidak valid',
                'details' => $v->errors(),
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

        if (! $user) {
            Log::warning('❌ UNAUTHORIZED', ['shift_id' => $shift->id]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // ===================================================================
        // CHECK SHIFT STATUS - FLEXIBLE
        // ===================================================================
        // Jika sudah ditutup, return sukses dengan data yang ada
        // (Untuk handle double-click atau retry dari mobile)
        if ($shift->status === 'tutup') {
            Log::warning('❌ ALREADY CLOSED', ['shift' => $shift->toArray()]);

            return response()->json([
                'message' => 'Shift sudah ditutup sebelumnya',
                'shift' => $shift,
            ], 200);
        }

        if ($shift->status !== 'buka') {
            Log::warning('❌ INVALID STATUS', ['status' => $shift->status]);

            return response()->json([
                'error' => 'Shift tidak dapat ditutup (status: '.$shift->status.')',
            ], 400);
        }

        // ===================================================================
        // CHECK OPEN BILLS - BARU: Deteksi tagihan yang belum diclose
        // ===================================================================
        // Jika masih ada bill terbuka, minta konfirmasi dari mobile (409)
        // agar UI menampilkan snackbar dengan daftar bill.
        $forceClose = $request->boolean('force_close_open_bills');
        $openBills = OpenBill::where('shift_id', $shift->id)
            ->where('status', 'open')
            ->orderBy('created_at')
            ->get(['id', 'nomor_open_bill', 'nama_pelanggan', 'subtotal', 'total', 'created_at']);

        if ($openBills->isNotEmpty() && ! $forceClose) {
            Log::info('⚠️ SHIFT CLOSE BLOCKED: ada open bills', [
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'open_bill_count' => $openBills->count(),
            ]);

            return response()->json([
                'error' => 'Masih terdapat tagihan (bill) yang belum diclose pada shift ini.',
                'code' => 'OPEN_BILLS_EXIST',
                'requires_confirmation' => true,
                'confirmation_prompt' => 'apakah anda yakin ingin close semua bill dan melakukan pengakhiran shift?',
                'open_bills' => $openBills->map(function (OpenBill $bill) {
                    return [
                        'id' => (int) $bill->id,
                        'nomor_open_bill' => (string) $bill->nomor_open_bill,
                        'nama_pelanggan' => $bill->nama_pelanggan,
                        'total' => (float) $bill->total,
                    ];
                })->values(),
            ], 409);
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

        if (! $hasPermission) {
            Log::warning('❌ UNAUTHORIZED ACCESS', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'shift_id' => $shift->id,
                'shift_user_id' => $shift->user_id,
            ]);

            return response()->json([
                'error' => 'Anda tidak memiliki izin untuk menutup shift ini',
            ], 403);
        }

        // Cabang validation (kecuali IT Support)
        if (! $isItSupport && ! $isOwner) {
            $cabangIds = $user->cabang()->pluck('cabang.id')->all();

            if (! in_array($shift->cabang_id, $cabangIds, true)) {
                Log::warning('Cross-cabang shift close attempt', [
                    'user_id' => $user->id,
                    'user_role' => $userRole,
                    'shift_cabang_id' => $shift->cabang_id,
                    'user_cabang_ids' => $cabangIds,
                ]);

                return response()->json([
                    'error' => 'Anda tidak memiliki akses ke cabang ini',
                ], 403);
            }
        }

        // Log permission override
        if (! $isOwner) {
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
            Log::info('🔒 LOCKING SHIFT', ['shift_id' => $shift->id]);

            $shiftLocked = Shift::where('id', $shift->id)
                ->lockForUpdate()
                ->first();

            if (! $shiftLocked) {
                DB::rollBack();
                Log::error('❌ LOCK FAILED', ['shift_id' => $shift->id]);

                return response()->json([
                    'error' => 'Shift tidak ditemukan',
                ], 404);
            }

            Log::info('✅ LOCKED', ['shift' => $shiftLocked->toArray()]);

            // Double-check status setelah lock
            if ($shiftLocked->status === 'tutup') {
                DB::rollBack();
                Log::warning('❌ ALREADY CLOSED AFTER LOCK', ['shift_id' => $shiftLocked->id]);

                return response()->json([
                    'message' => 'Shift sudah ditutup sebelumnya',
                    'shift' => $shiftLocked,
                ], 200);
            }

            // ===================================================================
            // FORCE CLOSE OPEN BILLS (jika diminta oleh mobile)
            // ===================================================================
            $closedBills = [];
            if ($forceClose) {
                $stillOpen = OpenBill::where('shift_id', $shiftLocked->id)
                    ->where('status', 'open')
                    ->lockForUpdate()
                    ->get();

                if ($stillOpen->isNotEmpty()) {
                    Log::info('🔁 FORCE-CLOSING OPEN BILLS', [
                        'shift_id' => $shiftLocked->id,
                        'count' => $stillOpen->count(),
                        'user_id' => $user->id,
                    ]);

                    foreach ($stillOpen as $bill) {
                        $log = $bill->audit_log ?? [];
                        $log[] = [
                            'action' => 'force_closed_on_shift_close',
                            'user_id' => $user->id,
                            'timestamp' => now()->toDateTimeString(),
                            'data' => ['shift_id' => $shiftLocked->id],
                        ];
                        $bill->update([
                            'status' => 'closed',
                            'catatan' => trim(($bill->catatan ?? '').' [Force-closed saat tutup shift oleh user #'.$user->id.']'),
                            'audit_log' => $log,
                            'deleted_by' => $user->id,
                            'delete_reason' => 'force_closed_on_shift_close',
                        ]);
                        $closedBills[] = [
                            'id' => (int) $bill->id,
                            'nomor_open_bill' => (string) $bill->nomor_open_bill,
                            'nama_pelanggan' => $bill->nama_pelanggan,
                        ];
                    }
                }
            }

            // ===================================================================
            // HITUNG TOTAL DARI TRANSAKSI YANG TERSIMPAN
            // ===================================================================
            // The persisted payment rows are the source of truth. Any total_tunai /
            // total_qris sent by the client is ignored on purpose: it is a derived
            // value and a compromised or buggy client must not decide the cash
            // reconciliation. saldo_akhir stays client supplied, because the counted
            // cash really is a physical declaration by the cashier.
            $totals = Pembayaran::query()
                ->join('transaksi', 'transaksi.id', '=', 'pembayaran.transaksi_id')
                ->where('transaksi.shift_id', $shiftLocked->id)
                ->where('transaksi.status', 'selesai')
                ->whereNull('transaksi.deleted_at')
                ->whereIn('pembayaran.metode_pembayaran', ['tunai', 'qris'])
                ->groupBy('pembayaran.metode_pembayaran')
                ->selectRaw('pembayaran.metode_pembayaran as metode, SUM(pembayaran.jumlah) as total')
                ->pluck('total', 'metode');

            $total_tunai = (float) ($totals['tunai'] ?? 0);
            $total_qris = (float) ($totals['qris'] ?? 0);

            // Hitung saldo diharapkan dan selisih
            $saldo_diharapkan = $shiftLocked->saldo_awal + $total_tunai;
            $selisih = $request->saldo_akhir - $saldo_diharapkan;

            Log::info('📊 CALCULATED BALANCE', [
                'saldo_awal' => $shiftLocked->saldo_awal,
                'saldo_akhir' => $request->saldo_akhir,
                'saldo_diharapkan' => $saldo_diharapkan,
                'selisih' => $selisih,
            ]);

            // ===================================================================
            // UPDATE SHIFT
            // ===================================================================
            Log::info('🔄 UPDATING SHIFT', ['shift_id' => $shiftLocked->id]);

            $shiftLocked->update([
                'saldo_akhir' => $request->saldo_akhir,
                'saldo_diharapkan' => $saldo_diharapkan,
                'selisih' => $selisih,
                'total_tunai' => $total_tunai,
                'total_qris' => $total_qris,
                'waktu_tutup' => Carbon::now(),
                'status' => 'tutup',
                'catatan' => $request->catatan,
                'updated_at' => now(),
            ]);

            Log::info('✅ UPDATED', ['shift' => $shiftLocked->fresh()->toArray()]);

            DB::commit();
            Log::info('✅ COMMITTED', ['shift_id' => $shift->id]);

            // Reload shift untuk response
            $shiftLocked->refresh();
            $shiftLocked->load(['cabang', 'user']);

            return response()->json([
                'message' => 'Shift berhasil ditutup',
                'shift' => $shiftLocked,
                'closed_open_bills' => $closedBills,
                'closed_open_bills_count' => count($closedBills),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menutup shift', [
                'shift_id' => $shift->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // The exception message can carry SQL, so the client only gets the
            // generic message; the detail stays in the log above.
            return ApiErrorResponse::make(500, ApiErrorResponse::GENERIC_SERVER_ERROR);
        }
    }

    public function tampilkanShift(Request $request, Shift $shift)
    {
        // SECURITY: Verify user has access to this shift's branch
        $policy = new \App\Policies\BranchAccessPolicy;
        if (! $policy->viewShift($request->user(), $shift)) {
            return response()->json([
                'error' => 'Anda tidak memiliki akses ke shift ini.',
            ], 403);
        }

        $shift->load([
            'user',
            'cabang',
            'transaksi.item.produk',
            'transaksi.pembayaran',
            'kalibrasi.produk',
            'mutasiStok.stokEtalase.produk',
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
