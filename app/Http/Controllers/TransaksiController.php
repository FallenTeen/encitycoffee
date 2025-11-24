<?php
namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Produk;
use App\Models\Shift;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

        $shift = Shift::findOrFail($validated['shift_id']);

        if ($shift->status !== 'buka') {
            return response()->json(['error' => 'Shift tidak terbuka'], 400);
        }

        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Tidak memiliki akses'], 403);
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
            return response()->json($transaksi, 201);
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


    public function index(Request $request)
    {
        Gate::authorize('view-transaksi');
        $query = Transaksi::query()->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $transaksis = $query->paginate(15);
        return Inertia::render('transaksi/Index', compact('transaksis'));
    }

    public function show(Transaksi $transaksi)
    {
        Gate::authorize('view-transaksi');
        $transaksi->load(['item.produk', 'pembayaran', 'shift']);
        return Inertia::render('transaksi/Show', compact('transaksi'));
    }

    public function byShift(Shift $shift, Request $request)
    {
        Gate::authorize('view-transaksi');
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
