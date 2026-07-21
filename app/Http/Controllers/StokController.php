<?php
namespace App\Http\Controllers;

use App\Models\StokEtalase;
use App\Models\BatchStok;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\MutasiStok;
use App\Services\ProductCacheService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class StokController extends Controller
{
    protected ProductCacheService $productCacheService;

    public function __construct(ProductCacheService $productCacheService)
    {
        $this->productCacheService = $productCacheService;
    }

    public function index(Request $request)
    {
        Gate::authorize('view-stok');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $validated = $request->validate([
            'cabang_id' => 'nullable|integer|exists:cabang,id',
            'tipe_stok' => 'nullable|in:produksi_minuman,penjualan_retail',
            'kategori_id' => 'nullable|integer|exists:kategori_produk,id',
            'status' => 'nullable|in:semua,rendah,normal',
        ]);

        $query = StokEtalase::whereIn('cabang_id', $cabangIds)
            ->with(['produk.kategori', 'cabang', 'batch']);

        if (!empty($validated['cabang_id'])) {
            if (!in_array((int) $validated['cabang_id'], $cabangIds)) {
                abort(403, 'Tidak berhak mengakses cabang ini');
            }
            $query->where('cabang_id', (int) $validated['cabang_id']);
        }

        if (!empty($validated['tipe_stok'])) {
            $query->where('tipe_stok', $validated['tipe_stok']);
        }

        if (!empty($validated['kategori_id'])) {
            $query->whereHas('produk', function ($q) use ($validated) {
                $q->where('kategori_id', (int) $validated['kategori_id']);
            });
        }

        if (!empty($validated['status'])) {
            if ($validated['status'] === 'rendah') {
                $query->whereColumn('jumlah', '<=', 'stok_minimum');
            } elseif ($validated['status'] === 'normal') {
                $query->whereColumn('jumlah', '>', 'stok_minimum');
            }
        }

        $stoks = $query->get();

        $stoksTransformed = $stoks->map(function ($s) {
            $nilaiTotal = $s->batch->sum(function ($b) {
                return (float) $b->jumlah * (float) $b->harga_beli;
            });
            $batchKadaluarsaCount = $s->batch->filter(function ($b) {
                return $b->tanggal_kadaluarsa && $b->tanggal_kadaluarsa->lte(Carbon::now()->addDays(30));
            })->count();
            return [
                'id' => $s->id,
                'cabang_id' => $s->cabang_id,
                'tipe_stok' => $s->tipe_stok,
                'jumlah' => (float) $s->jumlah,
                'stok_minimum' => (float) $s->stok_minimum,
                'produk' => $s->produk,
                'cabang' => $s->cabang,
                'batch' => $s->batch,
                'nilai_total' => (float) $nilaiTotal,
                'is_stok_rendah' => (bool) ($s->jumlah <= $s->stok_minimum),
                'batch_kadaluarsa_count' => $batchKadaluarsaCount,
            ];
        });


        $totalItem = $stoksTransformed->count();
        $itemStokRendah = $stoksTransformed->filter(fn($s) => $s['is_stok_rendah'])->count();
        $totalNilaiInventori = $stoksTransformed->sum(fn($s) => (float) $s['nilai_total']);
        $itemMendekatiKadaluarsa = $stoksTransformed->sum('batch_kadaluarsa_count');

        $statistik = [
            'total_item' => $totalItem,
            'item_stok_rendah' => $itemStokRendah,
            'total_nilai_inventori' => (float) $totalNilaiInventori,
            'item_mendekati_kadaluarsa' => (int) $itemMendekatiKadaluarsa,
        ];

        $cabangList = $user->cabang->map(function ($c) {
            return ['id' => $c->id, 'nama' => $c->nama];
        });

        return Inertia::render('stok/Index', [
            'stoks' => $stoksTransformed,
            'statistik' => $statistik,
            'filter_aktif' => $validated,
            'cabang_list' => $cabangList,
        ]);
    }

    public function byCabang(Cabang $cabang, Request $request)
    {
        Gate::authorize('view-stok');

        $user = $request->user();
        $authorizedCabangIds = $user->cabang->pluck('id')->all();
        if (!in_array($cabang->id, $authorizedCabangIds)) {
            return response()->json(['error' => 'Tidak berhak mengakses cabang ini'], 403);
        }

        $stok = StokEtalase::where('cabang_id', $cabang->id)
            ->with(['produk.kategori', 'batch'])
            ->get();

        $groups = [
            'produksi_minuman' => [],
            'penjualan_retail' => [],
        ];

        $summary = [
            'produksi_minuman' => ['total_item' => 0, 'stok_rendah' => 0, 'nilai_inventori' => 0.0],
            'penjualan_retail' => ['total_item' => 0, 'stok_rendah' => 0, 'nilai_inventori' => 0.0],
        ];

        foreach ($stok as $s) {
            $nilaiTotal = $s->batch->sum(fn($b) => (float) $b->jumlah * (float) $b->harga_beli);
            $data = [
                'id' => $s->id,
                'produk' => $s->produk,
                'jumlah' => (float) $s->jumlah,
                'stok_minimum' => (float) $s->stok_minimum,
                'batch' => $s->batch,
                'nilai_total' => (float) $nilaiTotal,
                'is_stok_rendah' => (bool) ($s->jumlah <= $s->stok_minimum),
            ];
            $groups[$s->tipe_stok][] = $data;

            $summary[$s->tipe_stok]['total_item'] += 1;
            $summary[$s->tipe_stok]['nilai_inventori'] += (float) $nilaiTotal;
            if ($data['is_stok_rendah']) {
                $summary[$s->tipe_stok]['stok_rendah'] += 1;
            }
        }

        return response()->json([
            'cabang' => ['id' => $cabang->id, 'nama' => $cabang->nama],
            'stok_produksi_minuman' => $groups['produksi_minuman'],
            'stok_penjualan_retail' => $groups['penjualan_retail'],
            'statistik' => $summary,
        ]);
    }



    public function kadaluarsa(Request $request, ?Cabang $cabang = null)
    {
        Gate::authorize('view-stok');

        $validated = $request->validate([
            'hari' => 'nullable|integer|min:1|max:365',
        ]);
        $hari = (int) ($validated['hari'] ?? 30);

        $user = $request->user();
        $cabangIds = $user->cabang->pluck('id')->all();
        if ($cabang) {
            if (!in_array($cabang->id, $cabangIds)) {
                return response()->json(['error' => 'Tidak berhak mengakses cabang ini'], 403);
            }
            $cabangIds = [$cabang->id];
        }

        $batch = BatchStok::whereHas('stokEtalase', function ($q) use ($cabangIds) {
                $q->whereIn('cabang_id', $cabangIds);
            })
            ->mendekatiKadaluarsa($hari)
            ->with(['stokEtalase.produk', 'stokEtalase.cabang'])
            ->orderBy('tanggal_kadaluarsa')
            ->get();

        return response()->json(['data' => $batch]);
    }



    public function rendah(Request $request, ?Cabang $cabang = null)
    {
        Gate::authorize('view-stok');

        $user = $request->user();
        $cabangIds = $user->cabang->pluck('id')->all();
        if ($cabang) {
            if (!in_array($cabang->id, $cabangIds)) {
                return response()->json(['error' => 'Tidak berhak mengakses cabang ini'], 403);
            }
            $cabangIds = [$cabang->id];
        }

        $stoks = StokEtalase::whereIn('cabang_id', $cabangIds)
            ->whereColumn('jumlah', '<=', 'stok_minimum')
            ->with(['produk.kategori', 'cabang'])
            ->get();

        $items = [];
        $stats = [
            'kritis' => 0,
            'tinggi' => 0,
            'sedang' => 0,
        ];
        $rekomendasiTotal = 0;

        foreach ($stoks as $s) {
            $persentase = $s->stok_minimum > 0
                ? ((float) $s->jumlah / (float) $s->stok_minimum) * 100
                : 100;

            if ($persentase <= 50) {
                $tingkat = 'kritis';
            } elseif ($persentase <= 80) {
                $tingkat = 'tinggi';
            } else {
                $tingkat = 'sedang';
            }

            $rekomendasiPembelian = max(0.0, ((float) $s->stok_minimum * 2) - (float) $s->jumlah);
            $rekomendasiTotal += $rekomendasiPembelian;
            $stats[$tingkat] += 1;

            $items[] = [
                'id' => $s->id,
                'produk' => $s->produk,
                'cabang' => $s->cabang,
                'jumlah' => (float) $s->jumlah,
                'stok_minimum' => (float) $s->stok_minimum,
                'persentase' => (float) $persentase,
                'tingkat_bahaya' => $tingkat,
                'rekomendasi_pembelian' => (float) $rekomendasiPembelian,
            ];
        }

        $kelompok = [
            'kritis' => array_values(array_filter($items, fn($i) => $i['tingkat_bahaya'] === 'kritis')),
            'tinggi' => array_values(array_filter($items, fn($i) => $i['tingkat_bahaya'] === 'tinggi')),
            'sedang' => array_values(array_filter($items, fn($i) => $i['tingkat_bahaya'] === 'sedang')),
        ];

        return response()->json([
            'stok_rendah' => $items,
            'kelompok' => $kelompok,
            'statistik' => $stats,
            'rekomendasi_pembelian_total' => (float) $rekomendasiTotal,
        ]);
    }



    public function mutasi(StokEtalase $stokEtalase, Request $request)
    {
        Gate::authorize('view-stok');

        $user = $request->user();
        $authorizedCabangIds = $user->cabang->pluck('id')->all();
        if (!in_array($stokEtalase->cabang_id, $authorizedCabangIds)) {
            return response()->json(['error' => 'Tidak berhak mengakses stok ini'], 403);
        }

        $validated = $request->validate([
            'tipe' => 'nullable|array',
            'tipe.*' => 'in:masuk,keluar,penyesuaian,kalibrasi,tidak_teralokasi',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'user_id' => 'nullable|integer|exists:users,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = MutasiStok::where('stok_etalase_id', $stokEtalase->id)->with('user')->latest();

        if (!empty($validated['tipe'])) {
            $query->whereIn('tipe', $validated['tipe']);
        }
        if (!empty($validated['tanggal_mulai'])) {
            $query->whereDate('created_at', '>=', $validated['tanggal_mulai']);
        }
        if (!empty($validated['tanggal_selesai'])) {
            $query->whereDate('created_at', '<=', $validated['tanggal_selesai']);
        }
        if (!empty($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $mutasi = $query->paginate($perPage);

        if ($request->expectsJson()) {
            return response()->json($mutasi);
        }

        return Inertia::render('stok/Mutasi', [
            'stokEtalaseId' => (int) $stokEtalase->id,
            'mutasi' => $mutasi,
        ]);
    }


    public function create()
    {
        Gate::authorize('create-stok');
        return Inertia::render('stok/Create');
    }

    public function store(Request $request, StokService $stokService)
    {
        Gate::authorize('create-stok');

        $validated = $request->validate([
            'cabang_id' => 'required|integer|exists:cabang,id',
            'produk_id' => 'required|integer|exists:produk,id',
            'tipe_stok' => 'required|in:produksi_minuman,penjualan_retail',
            'jumlah' => 'required|numeric|min:0.0001',
            'nomor_batch' => 'required|string|max:50|unique:batch_stok,nomor_batch',
            'tanggal_kadaluarsa' => 'nullable|date|after:today',
            'harga_beli' => 'required|numeric|min:0',
            'stok_minimum' => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();
        $authorizedCabangIds = $user->cabang->pluck('id')->all();
        if (!in_array((int) $validated['cabang_id'], $authorizedCabangIds)) {
            return back()->withErrors(['cabang_id' => 'Tidak berhak menambah stok untuk cabang ini'])->withInput();
        }

        $cabang = Cabang::findOrFail((int) $validated['cabang_id']);
        $produk = Produk::findOrFail((int) $validated['produk_id']);

        DB::beginTransaction();
        try {
            $stokEtalase = $stokService->tambahStok(
                $cabang,
                $produk,
                $validated['tipe_stok'],
                (float) $validated['jumlah'],
                $validated['nomor_batch'],
                isset($validated['tanggal_kadaluarsa']) ? Carbon::parse($validated['tanggal_kadaluarsa']) : null,
                (float) $validated['harga_beli'],
                $user,
            );

            if (isset($validated['stok_minimum'])) {
                $stokEtalase->stok_minimum = (float) $validated['stok_minimum'];
                $stokEtalase->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        // CRITICAL: Invalidate product cache for this cabang so kasir sees new produk.
        // Without this, the cached product list (scoped to cabang) becomes stale and
        // produk that just got their first stok_etalase won't appear in mobile home.
        $this->productCacheService->clearCache((int) $validated['cabang_id']);
        $this->productCacheService->incrementCacheVersion();

        return redirect()->route('stok.index')->with('success', 'Stok berhasil ditambahkan');
    }

    public function adjust(Request $request, StokService $stokService)
    {
        Gate::authorize('create-stok');

        $validated = $request->validate([
            'stok_etalase_id' => 'required|integer|exists:stok_etalase,id',
            'jumlah_baru' => 'required|numeric|min:0',
            'alasan' => 'required|in:stock_opname,koreksi_kesalahan,kerusakan,kehilangan,lainnya',
            'catatan' => 'required|string|min:10',
        ]);

        $user = $request->user();
        $stokEtalase = StokEtalase::findOrFail((int) $validated['stok_etalase_id']);

        $authorizedCabangIds = $user->cabang->pluck('id')->all();
        if (!in_array($stokEtalase->cabang_id, $authorizedCabangIds)) {
            return back()->withErrors(['stok_etalase_id' => 'Tidak berhak menyusun stok untuk cabang ini'])->withInput();
        }

        $catatanGabungan = 'Penyesuaian (' . $validated['alasan'] . '): ' . $validated['catatan'];

        try {
            $stokService->sesuaikanStok(
                $stokEtalase,
                (float) $validated['jumlah_baru'],
                $user,
                $catatanGabungan
            );
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        // CRITICAL: Invalidate product cache so mobile home reflects new stok immediately.
        $this->productCacheService->clearCache((int) $stokEtalase->cabang_id);

        return back()->with('success', 'Stok berhasil disesuaikan');
    }
}
