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
        // Removed permission checks - all users can access all shifts and branches

        $status = $request->string('status')->toString();
        $perPage = (int) $request->get('per_page', 15);

        Log::info('transaksiPerShift called', [
            'shift_id' => $shift->id,
            'status' => $status,
            'per_page' => $perPage,
        ]);

        // PERBAIKAN: Pisahkan query berdasarkan status
        // Jika status = 'open' → ambil dari tabel open_bill
        // Jika status lainnya → ambil dari tabel transaksi
        
        if ($status === 'open') {
            // Query open bills untuk shift ini
            $query = OpenBill::query()
                ->where('shift_id', $shift->id)
                ->where('status', 'open')
                ->with(['cabang:id,kode,nama', 'shift:id,status', 'user:id,name', 'items.produk'])
                ->latest();

            $data = $query->paginate($perPage);
            
            Log::info('Open bills found', [
                'count' => $data->total(),
                'current_page' => $data->currentPage(),
            ]);

            return response()->json($data);
        } else {
            // PERBAIKAN: Query langsung ke model Transaksi
            // Ini lebih reliable daripada menggunakan service
            $query = Transaksi::query()
                ->where('shift_id', $shift->id)
                ->with([
                    'cabang:id,kode,nama',
                    'user:id,name,email',
                    'shift:id,status',
                    'item.produk',
                    'pembayaran'
                ])
                ->latest();

            // Filter berdasarkan status jika diberikan
            if (!empty($status)) {
                // Map berbagai variasi status ke status yang benar di database
                $statusMap = [
                    'selesai' => 'selesai',
                    'paid' => 'selesai',
                    'completed' => 'selesai',
                    'lunas' => 'selesai',
                    'success' => 'selesai',
                    'pending' => 'pending',
                    'batal' => 'batal',
                ];
                
                $mappedStatus = $statusMap[$status] ?? $status;
                $query->where('status', $mappedStatus);
                
                Log::info('Filtering transaksi by status', [
                    'requested_status' => $status,
                    'mapped_status' => $mappedStatus,
                ]);
            }

            $data = $query->paginate($perPage);
            
            Log::info('Transaksi found', [
                'status' => $status,
                'count' => $data->total(),
                'current_page' => $data->currentPage(),
            ]);

            return response()->json($data);
        }
    }


    public function buatTransaksi(Request $request)
    {
        // Removed permission checks - all users can create transactions
        
        // Debug: Log all received data
        Log::info('TransaksiController.buatTransaksi: Received data', [
            'all_data' => $request->all(),
            'shift_id' => $request->input('shift_id'),
            'user_id' => $request->user()->id,
            'user_role' => $request->user()->role,
        ]);
        
        $validated = $request->validate([
            'shift_id' => 'required|exists:shift,id',
            'nama_pelanggan' => 'nullable|string|max:255',
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

        // Removed branch validation - all users can access any branch

        try {
            $transaksi = $this->transaksiService->buatTransaksi(
                $shift,
                $validated['items'],
                $validated['pembayaran'],
                (float) ($validated['diskon'] ?? 0),
                (float) ($validated['pajak'] ?? 0),
                $validated['catatan'] ?? null,
                $validated['nama_pelanggan'] ?? null
            );
            return response()->json($transaksi);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function tampilkanTransaksi(Transaksi $transaksi)
    {
        // Removed permission checks - all users can view transactions

        $transaksi->load(['item.produk', 'pembayaran', 'shift', 'cabang']);
        return response()->json($transaksi);
    }


    public function batalkanTransaksi(Transaksi $transaksi, Request $request)
    {
        // Removed permission checks - all users can cancel transactions
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
        // Removed permission checks - all users can create open bills
        $validated = $request->validate([
            'shift_id' => 'required|exists:shift,id',
            'nama_pelanggan' => 'nullable|string|max:255',
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

        // Removed branch validation - all users can access any branch

        try {
            $openBill = $this->transaksiService->buatOpenBill(
                $shift,
                $validated['items'],
                (float) ($validated['diskon'] ?? 0),
                (float) ($validated['pajak'] ?? 0),
                $validated['catatan'] ?? null,
                $validated['nama_pelanggan'] ?? null
            );
            return response()->json($openBill);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function daftarOpenBill(Request $request)
    {
        // Removed permission checks - all users can view open bills

        $user = $request->user();
        $status = $request->string('status')->toString();

        $query = OpenBill::query()
            ->with(['cabang:id,kode,nama', 'shift:id,status', 'user:id,name'])
            ->latest();

        if ($request->expectsJson()) {
            $query->where('status', 'open');
        } else {
            if (in_array($status, ['open', 'closed', 'batal'], true)) {
                $query->where('status', $status);
            } elseif ($status === 'all' || $status === '') {
                $status = $status === '' ? 'open' : $status;
                if ($status === 'open') {
                    $query->where('status', 'open');
                }
            } else {
                $status = 'open';
                $query->where('status', 'open');
            }
        }

        // PERBAIKAN: Filter by shift_id if provided (CRITICAL for filtering by active shift)
        if ($request->filled('shift_id')) {
            $shiftId = $request->integer('shift_id');
            $query->where('shift_id', $shiftId);
            Log::info('Filtering open bills by shift_id', ['shift_id' => $shiftId]);
        }

        // Removed branch filtering - all users can see all branches

        $perPage = (int) $request->get('per_page', 15);
        $openBills = $query->paginate($perPage)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($openBills);
        }

        return Inertia::render('transaksi/OpenBillIndex', [
            'open_bills' => $openBills,
            'per_page' => $perPage,
            'status' => $status,
        ]);
    }

    public function updateOpenBill(OpenBill $openBill, Request $request)
    {
        // Removed permission checks - all users can update open bills

        $validated = $request->validate([
            'nama_pelanggan' => 'nullable|string|max:255',
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

        if ($openBill->status !== 'open') {
            return response()->json(['error' => 'Open bill sudah tidak aktif'], 400);
        }

        if (! $shift) {
            return response()->json(['error' => 'Shift untuk open bill tidak ditemukan'], 400);
        }

        if ($shift->status !== 'buka') {
            return response()->json(['error' => 'Shift tidak terbuka'], 400);
        }

        // Removed branch validation - all users can access any branch

        try {
            $updated = $this->transaksiService->updateOpenBill(
                $openBill,
                $validated['items'],
                (float) ($validated['diskon'] ?? 0),
                (float) ($validated['pajak'] ?? 0),
                $validated['catatan'] ?? null,
                $validated['nama_pelanggan'] ?? null
            );

            return response()->json($updated);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function tampilkanOpenBill(OpenBill $openBill, Request $request)
    {
        // Removed permission checks - all users can view open bills

        $user = $request->user();
        
        // Removed branch validation - all users can access any branch

        $openBill->load(['items.produk', 'shift', 'cabang', 'user']);

        if ($request->expectsJson()) {
            return response()->json($openBill);
        }

        return Inertia::render('transaksi/OpenBillShow', [
            'open_bill' => $openBill,
        ]);
    }

    public function hapusOpenBill(OpenBill $openBill, Request $request)
    {
        $shift = $openBill->shift;

        if ($shift && $shift->status === 'tutup') {
            return response()->json(['error' => 'Tidak bisa menghapus open bill dari shift yang sudah ditutup'], 400);
        }

        if ($openBill->status !== 'open') {
            return response()->json(['error' => 'Open bill sudah tidak aktif'], 400);
        }

        $openBill->update(['status' => 'batal']);
        $openBill->addAuditLog('status_update', [
            'from' => 'open',
            'to' => 'batal',
        ]);

        return response()->json(['message' => 'Open bill berhasil dibatalkan']);
    }


    public function index(Request $request)
    {
        // Removed permission checks - all users can view transactions

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
        // Removed permission checks - all users can view transactions
        $transaksi->load(['item.produk', 'pembayaran', 'shift', 'cabang', 'user']);

        // Removed branch validation - all users can access any branch
        return Inertia::render('transaksi/Show', compact('transaksi'));
    }

    public function byShift(Shift $shift, Request $request)
    {
        // Removed permission checks - all users can view transactions by shift

        $user = $request->user();
        // Removed branch validation - all users can access any branch

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
        // Removed permission checks - all users can print receipts
        $transaksi->load(['item.produk', 'pembayaran', 'shift']);
        return Inertia::render('transaksi/Print', compact('transaksi'));
    }

    public function bayarOpenBill(OpenBill $openBill, Request $request)
    {
        // Removed permission checks - all users can pay open bills

        $validated = $request->validate([
            'pembayaran' => 'required|array|min:1',
            'pembayaran.*.metode' => 'required|in:tunai,qris',
            'pembayaran.*.jumlah' => 'required|numeric|min:0',
            'pembayaran.*.referensi' => 'nullable|string',
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

        if ($openBill->status !== 'open') {
            return response()->json(['error' => 'Open bill sudah tidak aktif'], 400);
        }

        // Removed branch validation - all users can access any branch

        try {
            // Convert Open Bill to Transaction
            $transaksi = $this->transaksiService->convertOpenBillToTransaksi(
                $openBill,
                $validated['pembayaran'],
                (float) ($validated['diskon'] ?? 0),
                (float) ($validated['pajak'] ?? 0),
                $validated['catatan'] ?? null
            );

            return response()->json([
                'message' => 'Open bill berhasil dibayar',
                'transaksi' => $transaksi->load(['item.produk', 'pembayaran', 'shift', 'cabang', 'user'])
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}