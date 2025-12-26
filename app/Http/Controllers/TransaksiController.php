<?php
namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\OpenBill;
use App\Models\Produk;
use App\Models\Shift;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    private TransaksiService $transaksiService;

    public function __construct(TransaksiService $transaksiService)
    {

        $this->transaksiService = $transaksiService;
    }

    public function transaksiPerShift(Shift $shift, Request $request)
    {
        if (! (Gate::allows('view-kasir-dashboard') || Gate::allows('view-supervisor-dashboard'))) {
            return response()->json(['error' => 'Tidak memiliki akses'], 403);
        }

        // Kasir hanya boleh melihat shift miliknya sendiri
        if ($request->user()->isKasir() && $shift->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Tidak memiliki akses ke shift ini'], 403);
        }

        $filter = [];
        if ($request->filled('status')) {
            $filter['status'] = $request->string('status')->toString();
        }

        $perPage = (int) $request->get('per_page', 15);
        $data = $this->transaksiService->transaksiPerShift($shift, $filter, $perPage);
        return response()->json($data);
    }


    public function buatTransaksi(Request $request)
    {
        Gate::authorize('create-transaksi');
        
        // Debug: Log all received data
        Log::info('TransaksiController.buatTransaksi: Received data', [
            'all_data' => $request->all(),
            'shift_id' => $request->input('shift_id'),
            'user_id' => $request->user()->id,
            'user_role' => $request->user()->role,
        ]);
        
        $validated = $request->validate([
            'shift_id' => 'required|exists:shift,id',
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
            'pembayaran' => 'required|array|min:1',
            'pembayaran.*.metode' => 'required|in:tunai,qris',
            'pembayaran.*.jumlah' => 'required|numeric|min:0',
            'pembayaran.*.referensi' => 'nullable|string',
            'diskon' => 'nullable|numeric|min:0',
            'pajak' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        // Debug: Log validated data
        Log::info('TransaksiController.buatTransaksi: Validated data', [
            'shift_id' => $validated['shift_id'],
            'items_count' => count($validated['items']),
            'pembayaran_count' => count($validated['pembayaran']),
        ]);

        $shift = Shift::findOrFail($validated['shift_id']);

        // Debug: Log shift details
        Log::info('TransaksiController.buatTransaksi: Shift details', [
            'shift_id' => $shift->id,
            'shift_status' => $shift->status,
            'shift_user_id' => $shift->user_id,
            'shift_cabang_id' => $shift->cabang_id,
            'current_user_id' => $request->user()->id,
            'current_user_role' => $request->user()->role,
        ]);

        if ($shift->status !== 'buka') {
            return response()->json(['error' => 'Shift tidak terbuka'], 400);
        }

        $user = $request->user();
        // Validasi cabang untuk supervisor/manager
        if (!$user->isKasir() && !$user->isItSupport()) {
            $userCabangIds = $user->cabang()->pluck('cabang.id')->all();
            if (!in_array((int) $shift->cabang_id, $userCabangIds, true)) {
                return response()->json(['error' => 'Tidak memiliki akses ke cabang ini'], 403);
            }
        }

        try {
            $transaksi = $this->transaksiService->buatTransaksi(
                $shift,
                $validated['items'],
                $validated['pembayaran'],
                (float) ($validated['diskon'] ?? 0),
                (float) ($validated['pajak'] ?? 0),
                $validated['catatan'] ?? null
            );
            return response()->json($transaksi);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function tampilkanTransaksi(Transaksi $transaksi)
    {
        if (! (Gate::allows('view-kasir-dashboard') || Gate::allows('view-supervisor-dashboard'))) {
            return response()->json(['error' => 'Tidak memiliki akses'], 403);
        }

        $transaksi->load(['item.produk', 'pembayaran', 'shift', 'cabang']);
        return response()->json($transaksi);
    }


    public function batalkanTransaksi(Transaksi $transaksi, Request $request)
    {
        Gate::authorize('delete-transaksi');
        $request->validate([
            'alasan' => 'required|string|min:10',
        ]);

        try {
            $updated = $this->transaksiService->batalkanTransaksi($transaksi, $request->user(), $request->string('alasan')->toString());
            return response()->json(['message' => 'Transaksi berhasil dibatalkan', 'transaksi' => $updated->load(['item.produk', 'pembayaran'])]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function buatOpenBill(Request $request)
    {
        Gate::authorize('create-transaksi');
        $validated = $request->validate([
            'shift_id' => 'required|exists:shift,id',
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
            'diskon' => 'nullable|numeric|min:0',
            'pajak' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        $shift = Shift::findOrFail($validated['shift_id']);

        if ($shift->status !== 'buka') {
            return response()->json(['error' => 'Shift tidak terbuka'], 400);
        }

        $user = $request->user();
        // Validasi cabang untuk supervisor/manager
        if (!$user->isKasir() && !$user->isItSupport()) {
            $userCabangIds = $user->cabang()->pluck('cabang.id')->all();
            if (!in_array((int) $shift->cabang_id, $userCabangIds, true)) {
                return response()->json(['error' => 'Tidak memiliki akses ke cabang ini'], 403);
            }
        }

        try {
            $openBill = $this->transaksiService->buatOpenBill(
                $shift,
                $validated['items'],
                (float) ($validated['diskon'] ?? 0),
                (float) ($validated['pajak'] ?? 0),
                $validated['catatan'] ?? null
            );
            return response()->json($openBill);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function daftarOpenBill(Request $request)
    {
        Gate::authorize('view-transaksi');

        $user = $request->user();
        $query = OpenBill::query()
            ->with(['cabang:id,kode,nama', 'shift:id,status', 'user:id,name'])
            ->where('status', 'open')
            ->latest();

        if (method_exists($user, 'isItSupport') && ! $user->isItSupport()) {
            $cabangIds = $user->cabang()->pluck('cabang.id')->all();
            $query->whereIn('cabang_id', $cabangIds);
        }

        $perPage = (int) $request->get('per_page', 15);
        $openBills = $query->paginate($perPage)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($openBills);
        }

        return Inertia::render('transaksi/OpenBillIndex', [
            'open_bills' => $openBills,
            'per_page' => $perPage,
        ]);
    }

    public function updateOpenBill(OpenBill $openBill, Request $request)
    {
        Gate::authorize('create-transaksi');

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.catatan' => 'nullable|string',
            'diskon' => 'nullable|numeric|min:0',
            'pajak' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        $user = $request->user();
        $shift = $openBill->shift;

        if (! $shift) {
            return response()->json(['error' => 'Shift untuk open bill tidak ditemukan'], 400);
        }

        if ($shift->status !== 'buka') {
            return response()->json(['error' => 'Shift tidak terbuka'], 400);
        }

        if (! $user->isKasir() && ! $user->isItSupport()) {
            $userCabangIds = $user->cabang()->pluck('cabang.id')->all();
            if (! in_array((int) $shift->cabang_id, $userCabangIds, true)) {
                return response()->json(['error' => 'Tidak memiliki akses ke cabang ini'], 403);
            }
        }

        try {
            $updated = $this->transaksiService->updateOpenBill(
                $openBill,
                $validated['items'],
                (float) ($validated['diskon'] ?? 0),
                (float) ($validated['pajak'] ?? 0),
                $validated['catatan'] ?? null
            );

            return response()->json($updated);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function tampilkanOpenBill(OpenBill $openBill, Request $request)
    {
        Gate::authorize('view-transaksi');

        $openBill->load(['items.produk', 'shift', 'cabang', 'user']);

        if ($request->expectsJson()) {
            return response()->json($openBill);
        }

        return Inertia::render('transaksi/OpenBillShow', [
            'open_bill' => $openBill,
        ]);
    }


    public function index(Request $request)
    {
        Gate::authorize('view-transaksi');

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,selesai,batal'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $query = Transaksi::query()
            ->with([
                'cabang:id,kode,nama',
                'user:id,name,email',
                'shift:id,status',
            ])
            ->latest();

        if (method_exists($user, 'isItSupport') && ! $user->isItSupport()) {
            $cabangIds = $user->cabang()->pluck('cabang.id')->all();
            $query->whereIn('cabang_id', $cabangIds);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $transaksis = $query->paginate($perPage)->withQueryString();
        return Inertia::render('transaksi/Index', [
            'transaksis' => $transaksis,
            'filter_aktif' => [
                'status' => $validated['status'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Transaksi $transaksi)
    {
        Gate::authorize('view-transaksi');
        $transaksi->load(['item.produk', 'pembayaran', 'shift', 'cabang', 'user']);

        $user = request()->user();
        if (method_exists($user, 'isItSupport') && ! $user->isItSupport()) {
            $cabangIds = $user->cabang()->pluck('cabang.id')->all();
            if (! in_array((int) $transaksi->cabang_id, $cabangIds, true)) {
                abort(403, 'Tidak memiliki akses');
            }
        }
        return Inertia::render('transaksi/Show', compact('transaksi'));
    }

    public function byShift(Shift $shift, Request $request)
    {
        Gate::authorize('view-transaksi');

        $user = $request->user();
        if (method_exists($user, 'isItSupport') && ! $user->isItSupport()) {
            $cabangIds = $user->cabang()->pluck('cabang.id')->all();
            if (! in_array((int) $shift->cabang_id, $cabangIds, true)) {
                abort(403, 'Tidak memiliki akses');
            }
        }

        $query = Transaksi::where('shift_id', $shift->id)->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $transaksis = $query->paginate(15);
        return Inertia::render('transaksi/ByShift', [
            'shift' => $shift,
            'transaksis' => $transaksis,
        ]);
    }

    public function void(Transaksi $transaksi, Request $request)
    {

        if (! ($request->user()->isManager() || $request->user()->isItSupport())) {
            return redirect()->back()->with('error', 'Hanya manager yang bisa membatalkan transaksi');
        }

        if ($transaksi->status === 'batal') {
            return redirect()->back()->with('error', 'Transaksi sudah dibatalkan');
        }

        if ($transaksi->shift && $transaksi->shift->status === 'tutup') {
            return redirect()->back()->with('error', 'Tidak bisa membatalkan transaksi dari shift yang sudah ditutup');
        }

        $request->validate([
            'alasan' => 'required|string|min:10',
        ]);

        try {
            $this->transaksiService->batalkanTransaksi($transaksi, $request->user(), $request->string('alasan')->toString());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('transaksi.show', $transaksi->id)->with('success', 'Transaksi berhasil dibatalkan');
    }

    public function printStruk(Transaksi $transaksi)
    {
        Gate::authorize('view-supervisor-dashboard');
        $transaksi->load(['item.produk', 'pembayaran', 'shift']);
        return Inertia::render('transaksi/Print', compact('transaksi'));
    }
}
