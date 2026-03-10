<?php
namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\OpenBill;
use App\Models\Shift;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    private TransaksiService $transaksiService;

    public function __construct(TransaksiService $transaksiService)
    {
        $this->transaksiService = $transaksiService;
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status'          => ['nullable', 'in:pending,selesai,batal'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'tanggal_mulai'   => ['nullable', 'date', 'before_or_equal:today'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai', 'before_or_equal:today'],
        ]);

        $user  = $request->user();
        $query = Transaksi::query()
            ->with(['cabang:id,kode,nama', 'user:id,name,email', 'shift:id,status'])
            ->latest();

        if (method_exists($user, 'isItSupport') && !($user->isItSupport() || $user->isManager())) {
            $cabangIds = $user->cabang()->pluck('cabang.id')->all();
            $query->whereIn('cabang_id', $cabangIds);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $tanggalMulai   = $validated['tanggal_mulai']   ?? null;
        $tanggalSelesai = $validated['tanggal_selesai'] ?? null;

        if ($tanggalMulai && $tanggalSelesai) {
            $akhirHari = now()->parse($tanggalSelesai)->endOfDay()->toDateTimeString();
            $query->whereBetween('created_at', [$tanggalMulai, $akhirHari]);
        } elseif ($tanggalMulai) {
            $query->whereDate('created_at', $tanggalMulai);
        }

        $perPage    = (int) ($validated['per_page'] ?? 15);
        $transaksis = $query->paginate($perPage)->withQueryString();

        return Inertia::render('transaksi/Index', [
            'transaksis'   => $transaksis,
            'filter_aktif' => [
                'status'          => $validated['status'] ?? '',
                'per_page'        => $perPage,
                'tanggal_mulai'   => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // EXPORT PDF
    // Menghasilkan halaman HTML yang langsung membuka dialog Print.
    // User tinggal pilih "Save as PDF" / "Print to PDF" di browser.
    // Tidak membutuhkan package tambahan.
    // ─────────────────────────────────────────────────────────────

    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'status'          => ['nullable', 'in:pending,selesai,batal'],
            'tanggal_mulai'   => ['required', 'date', 'before_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai', 'before_or_equal:today'],
        ]);

        $akhirHari = now()->parse($validated['tanggal_selesai'])->endOfDay()->toDateTimeString();

        $rows = Transaksi::with(['cabang', 'user'])
            ->when(!empty($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->whereBetween('created_at', [$validated['tanggal_mulai'], $akhirHari])
            ->orderByDesc('created_at')
            ->get();

        $storeName  = config('app.name', 'Toko');
        $periode    = $validated['tanggal_mulai'] . ' s/d ' . $validated['tanggal_selesai'];
        $generated  = now()->format('d/m/Y H:i:s');
        $statusLabel = $validated['status'] ? ucfirst($validated['status']) : 'Semua';

        $totalNilai = $rows->sum(fn ($t) => (float) ($t->total ?? 0));
        $countSelesai = $rows->where('status', 'selesai')->count();
        $countPending = $rows->where('status', 'pending')->count();
        $countBatal   = $rows->where('status', 'batal')->count();

        // Build baris tabel
        $tbody = '';
        if ($rows->isEmpty()) {
            $tbody = '<tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;font-style:italic">Tidak ada transaksi pada periode ini</td></tr>';
        } else {
            foreach ($rows as $i => $t) {
                $status = $t->status ?? '';
                $badgeStyle = match ($status) {
                    'selesai' => 'background:#dcfce7;color:#166534',
                    'pending' => 'background:#fef9c3;color:#854d0e',
                    'batal'   => 'background:#fee2e2;color:#991b1b',
                    default   => 'background:#f1f5f9;color:#475569',
                };
                $tbody .= '<tr style="' . ($i % 2 === 1 ? 'background:#f8fafc' : '') . '">'
                    . '<td>' . ($i + 1) . '</td>'
                    . '<td style="font-family:monospace;font-size:11px">' . htmlspecialchars($t->nomor_invoice ?? ('#' . $t->id)) . '</td>'
                    . '<td>' . htmlspecialchars(optional($t->cabang)->nama ?? optional($t->cabang)->kode ?? '-') . '</td>'
                    . '<td>' . htmlspecialchars(optional($t->user)->name ?? '-') . '</td>'
                    . '<td style="text-align:right">' . number_format((float) ($t->total ?? 0), 0, ',', '.') . '</td>'
                    . '<td><span style="' . $badgeStyle . ';padding:2px 8px;border-radius:12px;font-size:10px;font-weight:600">' . htmlspecialchars(ucfirst($status ?: '-')) . '</span></td>'
                    . '<td style="font-size:11px">' . htmlspecialchars(optional($t->created_at)?->format('d/m/Y H:i') ?? '-') . '</td>'
                    . '</tr>';
            }
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Transaksi – {$periode}</title>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { margin: 0; }
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #1a1a1a; padding: 24px; }

    /* Print button */
    .print-bar { display: flex; align-items: center; justify-content: space-between; background: #1e3a5f; color: #fff; padding: 10px 16px; border-radius: 8px; margin-bottom: 20px; }
    .print-bar p { font-size: 13px; }
    .print-bar button { background: #fff; color: #1e3a5f; border: none; padding: 7px 18px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13px; }

    /* Header */
    .header { border-bottom: 2px solid #1e3a5f; padding-bottom: 12px; margin-bottom: 12px; display: flex; justify-content: space-between; }
    .store-name { font-size: 20px; font-weight: 700; color: #1e3a5f; }
    .meta { text-align: right; font-size: 11px; color: #64748b; line-height: 1.7; }
    .meta strong { color: #334155; }

    /* Summary */
    .summary { display: flex; gap: 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 14px; }
    .summary-item { flex: 1; padding: 10px 14px; border-right: 1px solid #e2e8f0; }
    .summary-item:last-child { border-right: none; }
    .summary-item .val { font-size: 15px; font-weight: 700; color: #1e3a5f; }
    .summary-item .lbl { font-size: 10px; color: #94a3b8; margin-top: 2px; }

    /* Table */
    table { width: 100%; border-collapse: collapse; font-size: 11px; }
    thead { background: #1e3a5f; color: #fff; }
    th { padding: 8px 10px; text-align: left; font-weight: 600; }
    th:nth-child(5) { text-align: right; }
    td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
    td:nth-child(5) { text-align: right; }

    /* Footer */
    .footer { margin-top: 16px; font-size: 10px; color: #94a3b8; display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px; }
  </style>
</head>
<body>

<div class="print-bar no-print">
  <p>🖨️ Tekan tombol di kanan, lalu pilih <strong>Save as PDF</strong> (atau Ctrl+P)</p>
  <button onclick="window.print()">Print / Save PDF</button>
</div>

<div class="header">
  <div>
    <div class="store-name">{$storeName}</div>
    <div style="font-size:13px;color:#475569;margin-top:2px">Laporan Transaksi</div>
  </div>
  <div class="meta">
    <div><strong>Periode</strong>&nbsp; {$periode}</div>
    <div><strong>Status</strong>&nbsp;&nbsp; {$statusLabel}</div>
    <div><strong>Dicetak</strong>&nbsp; {$generated}</div>
  </div>
</div>

<div class="summary">
  <div class="summary-item"><div class="val">{$rows->count()}</div><div class="lbl">Total Transaksi</div></div>
  <div class="summary-item"><div class="val">Rp {$this->fmt($totalNilai)}</div><div class="lbl">Total Nilai</div></div>
  <div class="summary-item"><div class="val">{$countSelesai}</div><div class="lbl">Selesai</div></div>
  <div class="summary-item"><div class="val">{$countPending}</div><div class="lbl">Pending</div></div>
  <div class="summary-item"><div class="val">{$countBatal}</div><div class="lbl">Batal</div></div>
</div>

<table>
  <thead>
    <tr>
      <th style="width:4%">#</th>
      <th style="width:18%">Invoice</th>
      <th style="width:17%">Cabang</th>
      <th style="width:15%">Kasir</th>
      <th style="width:14%">Total (Rp)</th>
      <th style="width:10%">Status</th>
      <th style="width:14%">Waktu</th>
    </tr>
  </thead>
  <tbody>{$tbody}</tbody>
</table>

<div class="footer">
  <span>{$storeName} — Laporan Transaksi {$periode}</span>
  <span>Dicetak: {$generated}</span>
</div>

<script>
  // Auto-buka dialog print saat tab dibuka (bisa dinonaktifkan jika tidak diinginkan)
  // window.onload = () => setTimeout(() => window.print(), 500);
</script>
</body>
</html>
HTML;

        // Kirim sebagai HTML yang dibuka di tab baru (inline), bukan attachment
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // EXPORT EXCEL
    // Menghasilkan file CSV berformat UTF-8 dengan BOM agar Excel
    // bisa langsung membukanya tanpa encoding error.
    // Extension .csv — Excel, LibreOffice Calc, Google Sheets semua bisa buka.
    // Tidak membutuhkan package tambahan.
    // ─────────────────────────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        $validated = $request->validate([
            'status'          => ['nullable', 'in:pending,selesai,batal'],
            'tanggal_mulai'   => ['required', 'date', 'before_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai', 'before_or_equal:today'],
        ]);

        $akhirHari = now()->parse($validated['tanggal_selesai'])->endOfDay()->toDateTimeString();

        $rows = Transaksi::with(['cabang', 'user'])
            ->when(!empty($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->whereBetween('created_at', [$validated['tanggal_mulai'], $akhirHari])
            ->orderByDesc('created_at')
            ->get();

        $handle = fopen('php://temp', 'r+');

        // UTF-8 BOM — wajib agar Excel tidak salah baca karakter Indonesia
        fwrite($handle, "\xEF\xBB\xBF");

        // Header info
        fputcsv($handle, ['Laporan Transaksi']);
        fputcsv($handle, ['Periode', $validated['tanggal_mulai'] . ' s/d ' . $validated['tanggal_selesai']]);
        fputcsv($handle, ['Status',  $validated['status'] ? ucfirst($validated['status']) : 'Semua']);
        fputcsv($handle, ['Diekspor', now()->format('d/m/Y H:i:s')]);
        fputcsv($handle, []); // baris kosong pemisah

        // Kolom header
        fputcsv($handle, ['No', 'Invoice', 'Cabang', 'Kasir', 'Total (Rp)', 'Status', 'Waktu']);

        // Data
        foreach ($rows as $i => $t) {
            fputcsv($handle, [
                $i + 1,
                $t->nomor_invoice ?? ('#' . $t->id),
                optional($t->cabang)->nama ?? optional($t->cabang)->kode ?? '-',
                optional($t->user)->name ?? '-',
                (float) ($t->total ?? 0),
                $t->status ?? '-',
                optional($t->created_at)?->format('d/m/Y H:i:s') ?? '-',
            ]);
        }

        // Baris summary di bawah
        fputcsv($handle, []);
        fputcsv($handle, ['Total Transaksi', $rows->count()]);
        fputcsv($handle, ['Total Nilai (Rp)', $rows->sum(fn ($t) => (float) ($t->total ?? 0))]);

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        $filename = 'laporan-transaksi-' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            // text/csv dengan ekstensi .csv — Excel membukanya langsung dan benar
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // SHOW / PRINT STRUK
    // ─────────────────────────────────────────────────────────────

    public function show(Transaksi $transaksi)
    {
        $transaksi->load(['item.produk', 'pembayaran', 'shift', 'cabang', 'user']);
        return Inertia::render('transaksi/Show', compact('transaksi'));
    }

    public function printStruk(Transaksi $transaksi)
    {
        $transaksi->load(['item.produk', 'pembayaran', 'shift']);
        return Inertia::render('transaksi/Print', compact('transaksi'));
    }

    // ─────────────────────────────────────────────────────────────
    // SOFT DELETE / RESTORE / DELETED LIST
    // ─────────────────────────────────────────────────────────────

    public function softDeleteTransaksi(Request $request, Transaksi $transaksi)
    {
        $user = $request->user();
        if (!$user->isItSupport()) {
            return response()->json(['error' => 'Hanya IT Support yang bisa menghapus transaksi'], 403);
        }
        if ($transaksi->trashed()) {
            return response()->json(['error' => 'Transaksi sudah dihapus'], 400);
        }

        $request->validate(['reason' => 'required|string|min:5']);

        try {
            $transaksi->delete_reason = $request->input('reason');
            $transaksi->deleted_by    = $user->id;
            $transaksi->delete();
            $transaksi->item()->delete();

            return response()->json(['message' => 'Transaksi berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function restoreTransaksi(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isItSupport()) {
            return response()->json(['error' => 'Hanya IT Support yang bisa mengembalikan transaksi'], 403);
        }

        $transaksi = Transaksi::withTrashed()->findOrFail($id);

        if (!$transaksi->trashed()) {
            return response()->json(['error' => 'Transaksi tidak dalam status terhapus'], 400);
        }

        try {
            $transaksi->restore();
            $transaksi->item()->withTrashed()->restore();

            return response()->json(['message' => 'Transaksi berhasil dikembalikan']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deletedTransaksi(Request $request)
    {
        $user = $request->user();
        if (!$user->isItSupport()) {
            return response()->json(['error' => 'Hanya IT Support yang bisa melihat transaksi terhapus'], 403);
        }

        $perPage    = (int) $request->get('per_page', 15);
        $transaksis = Transaksi::onlyTrashed()
            ->with(['cabang:id,kode,nama', 'user:id,name', 'shift:id,status'])
            ->latest('deleted_at')
            ->paginate($perPage);

        return response()->json($transaksis);
    }

    // ─────────────────────────────────────────────────────────────
    // TRANSAKSI PER SHIFT
    // ─────────────────────────────────────────────────────────────

    public function transaksiPerShift(Shift $shift, Request $request)
    {
        $status  = $request->string('status')->toString();
        $perPage = (int) $request->get('per_page', 15);

        if ($status === 'open') {
            $data = OpenBill::query()
                ->where('shift_id', $shift->id)
                ->where('status', 'open')
                ->with(['cabang:id,kode,nama', 'shift:id,status', 'user:id,name', 'items.produk'])
                ->latest()
                ->paginate($perPage);

            return response()->json($data);
        }

        $query = Transaksi::query()
            ->where('shift_id', $shift->id)
            ->with(['cabang:id,kode,nama', 'user:id,name,email', 'shift:id,status', 'item.produk', 'pembayaran'])
            ->latest();

        if (!empty($status)) {
            $statusMap = [
                'selesai'   => 'selesai', 'paid' => 'selesai', 'completed' => 'selesai',
                'lunas'     => 'selesai', 'success' => 'selesai',
                'pending'   => 'pending',
                'batal'     => 'batal',
            ];
            $query->where('status', $statusMap[$status] ?? $status);
        }

        return response()->json($query->paginate($perPage));
    }

    public function byShift(Shift $shift, Request $request)
    {
        $query = Transaksi::where('shift_id', $shift->id)->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return Inertia::render('transaksi/ByShift', [
            'shift'      => $shift,
            'transaksis' => $query->paginate(15),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // BUAT / TAMPILKAN TRANSAKSI
    // ─────────────────────────────────────────────────────────────

    public function buatTransaksi(Request $request)
    {
        $validated = $request->validate([
            'shift_id'               => 'required|exists:shift,id',
            'nama_pelanggan'         => 'nullable|string|max:255',
            'items'                  => 'required|array|min:1',
            'items.*.produk_id'      => 'required|exists:produk,id',
            'items.*.jumlah'         => 'required|integer|min:1',
            'items.*.catatan'        => 'nullable|string',
            'pembayaran'             => 'required|array|min:1',
            'pembayaran.*.metode'    => 'required|in:tunai,qris',
            'pembayaran.*.jumlah'    => 'required|numeric|min:0',
            'pembayaran.*.referensi' => 'nullable|string',
            'diskon'                 => 'nullable|numeric|min:0',
            'pajak'                  => 'nullable|numeric|min:0',
            'catatan'                => 'nullable|string',
        ]);

        $shift = Shift::findOrFail($validated['shift_id']);
        if ($shift->status !== 'buka') {
            return response()->json(['error' => 'Shift tidak terbuka'], 400);
        }

        try {
            $transaksi = $this->transaksiService->buatTransaksi(
                $shift,
                $validated['items'],
                $validated['pembayaran'],
                (float) ($validated['diskon'] ?? 0),
                (float) ($validated['pajak']  ?? 0),
                $validated['catatan']          ?? null,
                $validated['nama_pelanggan']   ?? null,
            );
            return response()->json($transaksi);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function tampilkanTransaksi(Transaksi $transaksi)
    {
        $transaksi->load(['item.produk', 'pembayaran', 'shift', 'cabang']);
        
        // Append formatted datetime fields for receipt printing
        $transaksi->append([
            'waktu_selesai_formatted',
            'created_at_formatted', 
            'updated_at_formatted'
        ]);
        
        return response()->json($transaksi);
    }

    // ─────────────────────────────────────────────────────────────
    // BATAL / VOID
    // ─────────────────────────────────────────────────────────────

    public function batalkanTransaksi(Transaksi $transaksi, Request $request)
    {
        $request->validate(['alasan' => 'required|string|min:10']);

        try {
            $updated = $this->transaksiService->batalkanTransaksi(
                $transaksi, $request->user(), $request->string('alasan')->toString()
            );
            return response()->json([
                'message'   => 'Transaksi berhasil dibatalkan',
                'transaksi' => $updated->load(['item.produk', 'pembayaran']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function voidTransaksi(Request $request, Transaksi $transaksi)
    {
        $user = $request->user();
        if (!($user->isManager() || $user->isItSupport())) {
            return response()->json(['error' => 'Hanya manager dan IT Support yang bisa membatalkan transaksi'], 403);
        }
        if ($transaksi->status === 'batal') {
            return response()->json(['error' => 'Transaksi sudah dibatalkan'], 400);
        }
        if ($transaksi->shift && $transaksi->shift->status === 'tutup') {
            return response()->json(['error' => 'Tidak bisa membatalkan transaksi dari shift yang sudah ditutup'], 400);
        }

        $request->validate(['alasan' => 'required|string|min:10']);

        try {
            $this->transaksiService->batalkanTransaksi($transaksi, $user, $request->input('alasan'));
            return response()->json(['message' => 'Transaksi berhasil dibatalkan']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function void(Transaksi $transaksi, Request $request)
    {
        if (!($request->user()->isManager() || $request->user()->isItSupport())) {
            return redirect()->back()->with('error', 'Hanya manager dan IT Support yang bisa membatalkan transaksi');
        }
        if ($transaksi->status === 'batal') {
            return redirect()->back()->with('error', 'Transaksi sudah dibatalkan');
        }
        if ($transaksi->shift && $transaksi->shift->status === 'tutup') {
            return redirect()->back()->with('error', 'Tidak bisa membatalkan transaksi dari shift yang sudah ditutup');
        }

        $request->validate(['alasan' => 'required|string|min:10']);

        try {
            $this->transaksiService->batalkanTransaksi(
                $transaksi, $request->user(), $request->string('alasan')->toString()
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('transaksi.show', $transaksi->id)->with('success', 'Transaksi berhasil dibatalkan');
    }

    // ─────────────────────────────────────────────────────────────
    // OPEN BILL
    // ─────────────────────────────────────────────────────────────

    public function buatOpenBill(Request $request)
    {
        $validated = $request->validate([
            'shift_id'          => 'required|exists:shift,id',
            'nama_pelanggan'    => 'nullable|string|max:255',
            'items'             => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.jumlah'    => 'required|integer|min:1',
            'items.*.catatan'   => 'nullable|string',
            'diskon'            => 'nullable|numeric|min:0',
            'pajak'             => 'nullable|numeric|min:0',
            'catatan'           => 'nullable|string',
        ]);

        $shift = Shift::findOrFail($validated['shift_id']);
        if ($shift->status !== 'buka') {
            return response()->json(['error' => 'Shift tidak terbuka'], 400);
        }

        try {
            $openBill = $this->transaksiService->buatOpenBill(
                $shift, $validated['items'],
                (float) ($validated['diskon']       ?? 0),
                (float) ($validated['pajak']        ?? 0),
                $validated['catatan']               ?? null,
                $validated['nama_pelanggan']        ?? null,
            );
            return response()->json($openBill);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function daftarOpenBill(Request $request)
    {
        $status = $request->string('status')->toString();

        $query = OpenBill::query()
            ->with(['cabang:id,kode,nama', 'shift:id,status', 'user:id,name'])
            ->latest();

        if ($request->expectsJson()) {
            $query->where('status', 'open');
        } else {
            $allowed = ['open', 'closed', 'batal'];
            $status  = in_array($status, $allowed) ? $status : 'open';
            $query->where('status', $status);
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->integer('shift_id'));
        }

        $perPage   = (int) $request->get('per_page', 15);
        $openBills = $query->paginate($perPage)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($openBills);
        }

        return Inertia::render('transaksi/OpenBillIndex', [
            'open_bills' => $openBills,
            'per_page'   => $perPage,
            'status'     => $status,
        ]);
    }

    public function updateOpenBill(OpenBill $openBill, Request $request)
    {
        $validated = $request->validate([
            'nama_pelanggan'    => 'nullable|string|max:255',
            'items'             => 'required|array|min:1',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.jumlah'    => 'required|integer|min:1',
            'items.*.catatan'   => 'nullable|string',
            'diskon'            => 'nullable|numeric|min:0',
            'pajak'             => 'nullable|numeric|min:0',
            'catatan'           => 'nullable|string',
        ]);

        $shift = $openBill->shift;
        if ($openBill->status !== 'open') return response()->json(['error' => 'Open bill sudah tidak aktif'], 400);
        if (!$shift)                       return response()->json(['error' => 'Shift tidak ditemukan'], 400);
        if ($shift->status !== 'buka')     return response()->json(['error' => 'Shift tidak terbuka'], 400);

        try {
            $updated = $this->transaksiService->updateOpenBill(
                $openBill, $validated['items'],
                (float) ($validated['diskon']     ?? 0),
                (float) ($validated['pajak']      ?? 0),
                $validated['catatan']             ?? null,
                $validated['nama_pelanggan']      ?? null,
            );
            return response()->json($updated);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function tampilkanOpenBill(OpenBill $openBill, Request $request)
    {
        $openBill->load(['items.produk', 'shift', 'cabang', 'user']);
        if ($request->expectsJson()) return response()->json($openBill);
        return Inertia::render('transaksi/OpenBillShow', ['open_bill' => $openBill]);
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
        $openBill->addAuditLog('status_update', ['from' => 'open', 'to' => 'batal']);
        return response()->json(['message' => 'Open bill berhasil dibatalkan']);
    }

    public function bayarOpenBill(OpenBill $openBill, Request $request)
    {
        $validated = $request->validate([
            'pembayaran'             => 'required|array|min:1',
            'pembayaran.*.metode'    => 'required|in:tunai,qris',
            'pembayaran.*.jumlah'    => 'required|numeric|min:0',
            'pembayaran.*.referensi' => 'nullable|string',
            'diskon'                 => 'nullable|numeric|min:0',
            'pajak'                  => 'nullable|numeric|min:0',
            'catatan'                => 'nullable|string',
        ]);

        $shift = $openBill->shift;
        if (!$shift)                      return response()->json(['error' => 'Shift tidak ditemukan'], 400);
        if ($shift->status !== 'buka')    return response()->json(['error' => 'Shift tidak terbuka'], 400);
        if ($openBill->status !== 'open') return response()->json(['error' => 'Open bill sudah tidak aktif'], 400);

        try {
            $transaksi = $this->transaksiService->convertOpenBillToTransaksi(
                $openBill, $validated['pembayaran'],
                (float) ($validated['diskon']  ?? 0),
                (float) ($validated['pajak']   ?? 0),
                $validated['catatan']          ?? null,
            );
            return response()->json([
                'message'   => 'Open bill berhasil dibayar',
                'transaksi' => $transaksi->load(['item.produk', 'pembayaran', 'shift', 'cabang', 'user']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // SOFT DELETE OPEN BILL
    // ─────────────────────────────────────────────────────────────

    public function softDeleteOpenBill(Request $request, OpenBill $openBill)
    {
        $user = $request->user();
        if (!$user->isItSupport()) {
            return response()->json(['error' => 'Hanya IT Support yang bisa menghapus bill'], 403);
        }
        if ($openBill->trashed()) {
            return response()->json(['error' => 'Bill sudah dihapus'], 400);
        }

        $request->validate(['reason' => 'required|string|min:5']);

        try {
            $openBill->delete_reason = $request->input('reason');
            $openBill->deleted_by    = $user->id;
            $openBill->delete();
            $openBill->items()->delete();
            return response()->json(['message' => 'Bill berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function restoreOpenBill(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isItSupport()) {
            return response()->json(['error' => 'Hanya IT Support yang bisa mengembalikan bill'], 403);
        }

        $openBill = OpenBill::withTrashed()->findOrFail($id);
        if (!$openBill->trashed()) {
            return response()->json(['error' => 'Bill tidak dalam status terhapus'], 400);
        }

        try {
            $openBill->restore();
            $openBill->items()->withTrashed()->restore();
            return response()->json(['message' => 'Bill berhasil dikembalikan']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deletedOpenBills(Request $request)
    {
        $user = $request->user();
        if (!$user->isItSupport()) {
            return response()->json(['error' => 'Hanya IT Support yang bisa melihat bill terhapus'], 403);
        }

        $perPage   = (int) $request->get('per_page', 15);
        $openBills = OpenBill::onlyTrashed()
            ->with(['cabang:id,kode,nama', 'user:id,name', 'shift:id,status'])
            ->latest('deleted_at')
            ->paginate($perPage);

        return response()->json($openBills);
    }

    // ─────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────

    private function fmt(float $n): string
    {
        return number_format($n, 0, ',', '.');
    }
}