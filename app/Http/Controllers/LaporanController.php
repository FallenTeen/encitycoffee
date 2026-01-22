<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\ItemTransaksi;
use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\StokEtalase;
use App\Models\BatchStok;
use App\Models\Cabang;
use App\Models\Shift;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Inertia\Inertia;

class LaporanController extends Controller
{
    public function shift(Request $request)
    {
        Gate::authorize('view-laporan');

        $user = Auth::user();
        $cabangIds = $this->tentukanCabangIds($user);

        $validated = $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'cabang_id' => ['nullable', 'integer', Rule::in($cabangIds)],
            'user_id' => 'nullable|integer',
            'status' => 'nullable|string',
        ]);

        $query = Shift::whereIn('cabang_id', $cabangIds)
            ->with(['user', 'cabang', 'transaksi'])
            ->orderByDesc('waktu_buka');
        $tanggalMulai = $validated['tanggal_mulai'] ?? null;
        $tanggalSelesai = $validated['tanggal_selesai'] ?? null;
        if (!$tanggalMulai && !$tanggalSelesai) {
            $tanggalMulai = Carbon::now()->subDays(30)->toDateString();
            $tanggalSelesai = Carbon::now()->toDateString();
        }

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('waktu_buka', [$tanggalMulai, $tanggalSelesai]);
        } elseif ($tanggalMulai) {
            $query->whereDate('waktu_buka', $tanggalMulai);
        }

        if (!empty($validated['cabang_id'])) {
            $query->where('cabang_id', (int) $validated['cabang_id']);
        }
        if (!empty($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }
        if (!empty($validated['status']) && $validated['status'] !== 'all') {
            $status = $validated['status'];
            if ($status === 'open') {
                $status = 'buka';
            }
            if ($status === 'closed') {
                $status = 'tutup';
            }
            $query->where('status', $status);
        }

        $shift = $query->paginate(20)->through(function (Shift $s) {
            $totalTransaksi = $s->transaksi->count();
            $totalPenjualan = (float) $s->transaksi->where('status', 'selesai')->sum('total');
            $durasiShift = null;
            if ($s->waktu_tutup) {
                $durasiShift = Carbon::parse($s->waktu_buka)->diffInMinutes(Carbon::parse($s->waktu_tutup));
            }
            return [
                'id' => $s->id,
                'user' => $s->user,
                'cabang' => $s->cabang,
                'waktu_buka' => $s->waktu_buka,
                'waktu_tutup' => $s->waktu_tutup,
                'status' => $s->status,
                'nama_kasir' => $s->nama_kasir,
                'nama_kasir_list' => collect(preg_split('/,/', (string) $s->nama_kasir))
                    ->map(fn($n) => trim((string) $n))
                    ->filter(fn($n) => $n !== '')
                    ->values()
                    ->all(),
                'total_transaksi' => $totalTransaksi,
                'total_penjualan' => $totalPenjualan,
                'akurasi_kas' => (float) ($s->selisih ?? 0.0),
                'durasi_shift_menit' => $durasiShift,
            ];
        });

        $collection = $shift->getCollection();
        $statistikRingkasan = [
            'total_shift' => $shift->total(),
            'total_penjualan' => (float) $collection->sum('total_penjualan'),
            'rata_rata_per_shift' => ($shift->total() ?? 0) > 0
                ? round(((float) $collection->sum('total_penjualan')) / $shift->total(), 2)
                : 0,
            'shift_dengan_selisih' => (int) $collection->filter(fn($r) => ((float) ($r['akurasi_kas'] ?? 0.0)) !== 0.0)->count(),
            'total_selisih' => (float) $collection->sum('akurasi_kas'),
        ];

        $filterAktif = array_merge($validated, [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ]);

        return Inertia::render('laporan/Shift', [
            'shift' => $shift,
            'statistik_ringkasan' => $statistikRingkasan,
            'filter_aktif' => $filterAktif,
        ]);
    }

    public function transaksi(Request $request)
    {
        Gate::authorize('view-laporan');

        $user = Auth::user();
        $cabangIds = $this->tentukanCabangIds($user);

        $validated = $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'cabang_id' => ['nullable', 'integer', Rule::in($cabangIds)],
            'shift_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
        ]);

        $tanggalMulai = $validated['tanggal_mulai'] ?? null;
        $tanggalSelesai = $validated['tanggal_selesai'] ?? null;
        if (!$tanggalMulai && !$tanggalSelesai) {
            $tanggalMulai = Carbon::now()->subDays(30)->toDateString();
            $tanggalSelesai = Carbon::now()->toDateString();
        }

        $query = Transaksi::with(['cabang', 'shift', 'user'])
            ->whereIn('cabang_id', $cabangIds)
            ->orderByDesc('waktu_selesai');

        if ($tanggalMulai && $tanggalSelesai) {
            $akhirHari = Carbon::parse($tanggalSelesai)->endOfDay()->toDateTimeString();
            $query->whereBetween('waktu_selesai', [$tanggalMulai, $akhirHari]);
        } elseif ($tanggalMulai) {
            $query->whereDate('waktu_selesai', $tanggalMulai);
        }

        if (!empty($validated['cabang_id'])) {
            $query->where('cabang_id', (int) $validated['cabang_id']);
        }
        if (!empty($validated['shift_id'])) {
            $query->where('shift_id', (int) $validated['shift_id']);
        }
        if (!empty($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $statRow = (clone $query)
            ->selectRaw('COUNT(*) as total_transaksi, COALESCE(SUM(total), 0) as total_nilai')
            ->first();

        $transaksi = $query->paginate(20)->through(function (Transaksi $t) {
            return [
                'id' => $t->id,
                'nomor_invoice' => $t->nomor_invoice,
                'waktu_selesai' => $t->waktu_selesai,
                'status' => $t->status,
                'total' => (float) ($t->total ?? 0),
                'cabang' => $t->cabang ? [
                    'id' => $t->cabang->id,
                    'nama' => $t->cabang->nama,
                    'kode' => $t->cabang->kode ?? null,
                ] : null,
                'shift' => $t->shift ? [
                    'id' => $t->shift->id,
                    'status' => $t->shift->status,
                    'nama_kasir' => $t->shift->nama_kasir,
                ] : null,
                'kasir' => $t->user ? [
                    'id' => $t->user->id,
                    'name' => $t->user->name,
                    'email' => $t->user->email,
                ] : null,
            ];
        });

        $statistik = [
            'total_transaksi' => (int) ($statRow->total_transaksi ?? 0),
            'total_nilai' => (float) ($statRow->total_nilai ?? 0),
        ];

        $filterAktif = array_merge($validated, [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ]);

        $cabangOptions = Cabang::whereIn('id', $cabangIds)
            ->orderBy('nama')
            ->get(['id', 'nama', 'kode']);

        $kasirOptions = User::role('kasir')
            ->aktif()
            ->when(!empty($cabangIds), fn($q) => $q->whereHas('cabang', fn($qq) => $qq->whereIn('cabang.id', $cabangIds)))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $shiftOptions = Shift::whereIn('cabang_id', $cabangIds)
            ->orderByDesc('waktu_buka')
            ->limit(100)
            ->get(['id', 'cabang_id', 'waktu_buka', 'waktu_tutup', 'status', 'nama_kasir']);

        if ($request->expectsJson()) {
            return response()->json([
                'transaksi' => $transaksi,
                'statistik' => $statistik,
                'filter_aktif' => $filterAktif,
                'cabang_options' => $cabangOptions,
                'kasir_options' => $kasirOptions,
                'shift_options' => $shiftOptions,
            ]);
        }

        return Inertia::render('laporan/Transaksi', [
            'transaksi' => $transaksi,
            'statistik' => $statistik,
            'filter_aktif' => $filterAktif,
            'cabang_options' => $cabangOptions,
            'kasir_options' => $kasirOptions,
            'shift_options' => $shiftOptions,
        ]);
    }
    public function detailShift(Shift $shift)
    {
        Gate::authorize('view-laporan');

        $user = Auth::user();
        $cabangIds = $this->tentukanCabangIds($user);
        if (!in_array($shift->cabang_id, $cabangIds, true)) {
            abort(403, 'Tidak memiliki akses ke cabang ini');
        }

        $shift->load([
            'user',
            'cabang',
            'transaksi.item.produk',
            'transaksi.pembayaran',
            'kalibrasi.produk',
            'mutasiStok.stokEtalase.produk',
        ]);

        $totalTransaksi = $shift->transaksi->count();
        $totalPenjualan = (float) $shift->transaksi->where('status', 'selesai')->sum('total');
        $durasiMenit = $shift->waktu_tutup
            ? Carbon::parse($shift->waktu_buka)->diffInMinutes(Carbon::parse($shift->waktu_tutup))
            : null;

        $totalPendapatanTunai = DB::table('pembayaran')
            ->join('transaksi', 'pembayaran.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.shift_id', $shift->id)
            ->where('transaksi.status', 'selesai')
            ->where('pembayaran.metode_pembayaran', 'tunai')
            ->sum('pembayaran.jumlah');

        $totalPendapatanQris = DB::table('pembayaran')
            ->join('transaksi', 'pembayaran.transaksi_id', '=', 'transaksi.id')
            ->where('transaksi.shift_id', $shift->id)
            ->where('transaksi.status', 'selesai')
            ->where('pembayaran.metode_pembayaran', 'qris')
            ->sum('pembayaran.jumlah');

        $statistik = [
            'total_transaksi' => $totalTransaksi,
            'total_penjualan' => $totalPenjualan,
            'total_tunai' => (float) $totalPendapatanTunai,
            'total_qris' => (float) $totalPendapatanQris,
            'saldo_awal' => (float) ($shift->saldo_awal ?? 0),
            'saldo_akhir' => (float) ($shift->saldo_akhir ?? 0),
            'selisih' => (float) ($shift->selisih ?? 0),
            'durasi_menit' => $durasiMenit,
        ];
        $items = $shift->transaksi->flatMap(fn($t) => $t->item);
        $produkTerjual = $items
            ->groupBy('produk_id')
            ->map(function ($group) {
                $jumlah = (float) $group->sum('jumlah');
                $subtotal = (float) $group->sum('subtotal');
                $produk = optional($group->first()->produk);
                return [
                    'produk_id' => $produk?->id,
                    'produk' => $produk?->nama,
                    'jumlah' => $jumlah,
                    'subtotal' => $subtotal,
                ];
            })
            ->sortByDesc('jumlah')
            ->values();

        $kalibrasiDetail = $shift->kalibrasi
            ->groupBy('produk_id')
            ->map(function ($group) {
                return [
                    'produk_id' => $group->first()->produk_id,
                    'produk' => optional($group->first()->produk)->nama,
                    'percobaan' => $group->count(),
                    'berat_total_gram' => (float) $group->sum('berat_beans_gram'),
                ];
            })
            ->values();

        $timeline = collect([])
            ->merge(
                $shift->transaksi->map(function ($t) {
                    return [
                        'waktu' => $t->created_at,
                        'tipe' => 'transaksi',
                        'nomor_invoice' => $t->nomor_invoice,
                        'total' => (float) $t->total,
                    ];
                })
            )
            ->merge(
                $shift->kalibrasi->map(function ($k) {
                    return [
                        'waktu' => $k->created_at,
                        'tipe' => 'kalibrasi',
                        'produk' => optional($k->produk)->nama,
                        'berat_beans_gram' => (float) $k->berat_beans_gram,
                    ];
                })
            )
            ->merge(
                $shift->mutasiStok->map(function ($m) {
                    return [
                        'waktu' => $m->created_at,
                        'tipe' => 'mutasi',
                        'produk' => optional(optional($m->stokEtalase)->produk)->nama,
                        'tipe_mutasi' => $m->tipe,
                        'jumlah_perubahan' => (float) $m->jumlah_perubahan,
                    ];
                })
            )
            ->sortBy('waktu')
            ->values();
        $shiftSebelumnya = Shift::where('user_id', $shift->user_id)
            ->where('waktu_buka', '<', $shift->waktu_buka)
            ->orderByDesc('waktu_buka')
            ->first();
        $perbandinganSebelumnya = null;
        if ($shiftSebelumnya) {
            $penjualanSebelumnya = Transaksi::where('shift_id', $shiftSebelumnya->id)
                ->where('status', 'selesai')
                ->sum('total');
            $perbandinganSebelumnya = [
                'shift_id' => $shiftSebelumnya->id,
                'total_penjualan' => (float) $penjualanSebelumnya,
                'selisih_penjualan' => (float) ($totalPenjualan - $penjualanSebelumnya),
            ];
        }
        $tanggalMulaiAvg = Carbon::now()->subDays(30);
        $avgKasir = Shift::where('user_id', $shift->user_id)
            ->whereBetween('waktu_buka', [$tanggalMulaiAvg, Carbon::now()])
            ->get()
            ->map(function ($s) {
                return Transaksi::where('shift_id', $s->id)->where('status', 'selesai')->sum('total');
            })
            ->avg();
        $jamTersibuk = Transaksi::where('shift_id', $shift->id)
            ->where('status', 'selesai')
            ->select(DB::raw('HOUR(waktu_selesai) as jam'), DB::raw('COUNT(*) as jumlah_transaksi'), DB::raw('SUM(total) as total'))
            ->groupBy(DB::raw('HOUR(waktu_selesai)'))
            ->orderByDesc('jumlah_transaksi')
            ->first();
        $rekomendasi = [];
        if (((float) ($shift->selisih ?? 0)) !== 0.0) {
            $rekomendasi[] = 'Periksa akurasi kas dan pencatatan pembayaran.';
        }
        if ($jamTersibuk && (int) ($jamTersibuk->jumlah_transaksi ?? 0) < max(3, $totalTransaksi / 4)) {
            $rekomendasi[] = 'Pertimbangkan promosi pada jam sepi untuk menaikkan transaksi.';
        }
        if (($avgKasir ?? 0) > 0 && $totalPenjualan < $avgKasir) {
            $rekomendasi[] = 'Penjualan di bawah rata-rata kasir; evaluasi proses atau stok.';
        }

        return Inertia::render('laporan/ShiftDetail', [
            'shift' => [
                'id' => $shift->id,
                'user' => $shift->user,
                'cabang' => $shift->cabang,
                'waktu_buka' => $shift->waktu_buka,
                'waktu_tutup' => $shift->waktu_tutup,
                'status' => $shift->status,
                'nama_kasir' => $shift->nama_kasir,
                'nama_kasir_list' => collect(preg_split('/,/', (string) $shift->nama_kasir))
                    ->map(fn($n) => trim((string) $n))
                    ->filter(fn($n) => $n !== '')
                    ->values()
                    ->all(),
            ],
            'statistik' => $statistik,
            'produk_terjual' => $produkTerjual,
            'kalibrasi_detail' => $kalibrasiDetail,
            'timeline' => $timeline,
            'perbandingan' => [
                'sebelumnya' => $perbandinganSebelumnya,
                'rata_rata_kasir' => round((float) ($avgKasir ?? 0), 2),
            ],
            'jam_tersibuk' => $jamTersibuk,
            'rekomendasi' => $rekomendasi,
        ]);
    }

    public function harian(Request $request)
    {
        Gate::authorize('view-laporan');

        $user = Auth::user();
        $cabangIds = $this->tentukanCabangIds($user);

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'cabang_id' => ['nullable', 'integer', Rule::in($cabangIds)],
        ]);

        $tanggal = $validated['tanggal'];
        if (!empty($validated['cabang_id'])) {
            $cabangIds = [(int) $validated['cabang_id']];
        }

        $shiftHariIni = Shift::whereIn('cabang_id', $cabangIds)
            ->whereDate('waktu_buka', $tanggal)
            ->with(['user', 'cabang', 'transaksi.item.produk', 'transaksi.pembayaran'])
            ->get();

        $totalShift = $shiftHariIni->count();
        $shiftBuka = $shiftHariIni->where('status', 'buka')->count();
        $shiftTutup = $shiftHariIni->where('status', 'tutup')->count();
        $totalKasir = $shiftHariIni->pluck('user_id')->unique()->count();

        $totalTransaksi = $shiftHariIni->sum(fn($s) => $s->transaksi->count());
        $totalPenjualan = $shiftHariIni->sum(fn($s) => (float) $s->transaksi->where('status', 'selesai')->sum('total'));
        $totalTunai = DB::table('pembayaran')
            ->join('transaksi', 'pembayaran.transaksi_id', '=', 'transaksi.id')
            ->join('shift', 'transaksi.shift_id', '=', 'shift.id')
            ->whereIn('shift.cabang_id', $cabangIds)
            ->whereDate('transaksi.waktu_selesai', $tanggal)
            ->where('transaksi.status', 'selesai')
            ->where('pembayaran.metode_pembayaran', 'tunai')
            ->sum('pembayaran.jumlah');

        $totalQris = DB::table('pembayaran')
            ->join('transaksi', 'pembayaran.transaksi_id', '=', 'transaksi.id')
            ->join('shift', 'transaksi.shift_id', '=', 'shift.id')
            ->whereIn('shift.cabang_id', $cabangIds)
            ->whereDate('transaksi.waktu_selesai', $tanggal)
            ->where('transaksi.status', 'selesai')
            ->where('pembayaran.metode_pembayaran', 'qris')
            ->sum('pembayaran.jumlah');

        $statistik = [
            'total_shift' => $totalShift,
            'shift_buka' => $shiftBuka,
            'shift_tutup' => $shiftTutup,
            'total_kasir' => $totalKasir,
            'total_transaksi' => (int) $totalTransaksi,
            'total_penjualan' => (float) $totalPenjualan,
            'total_tunai' => (float) ($totalTunai ?? 0),
            'total_qris' => (float) ($totalQris ?? 0),
            'rata_rata_per_shift' => $totalShift > 0 ? round($totalPenjualan / $totalShift, 2) : 0,
            'rata_rata_per_transaksi' => $totalTransaksi > 0 ? round($totalPenjualan / $totalTransaksi, 2) : 0,
        ];
        $grafikPerJam = Transaksi::whereIn('cabang_id', $cabangIds)
            ->whereDate('waktu_selesai', $tanggal)
            ->where('status', 'selesai')
            ->select(DB::raw('HOUR(waktu_selesai) as jam'), DB::raw('SUM(total) as total_penjualan'), DB::raw('COUNT(*) as jumlah_transaksi'))
            ->groupBy(DB::raw('HOUR(waktu_selesai)'))
            ->orderBy('jam')
            ->get();
        $produkTerlaris = ItemTransaksi::join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
            ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->whereIn('transaksi.cabang_id', $cabangIds)
            ->whereDate('transaksi.waktu_selesai', $tanggal)
            ->where('transaksi.status', 'selesai')
            ->select(
                'item_transaksi.produk_id',
                'produk.nama',
                DB::raw('SUM(item_transaksi.jumlah) as total_terjual'),
                DB::raw('SUM(item_transaksi.subtotal) as pendapatan')
            )
            ->groupBy('item_transaksi.produk_id', 'produk.nama')
            ->orderByDesc('total_terjual')
            ->limit(10)
            ->get();
        $performaPerCabang = Transaksi::join('shift', 'transaksi.shift_id', '=', 'shift.id')
            ->whereIn('shift.cabang_id', $cabangIds)
            ->whereDate('transaksi.waktu_selesai', $tanggal)
            ->where('transaksi.status', 'selesai')
            ->select(
                'shift.cabang_id',
                DB::raw('COUNT(DISTINCT shift.id) as total_shift'),
                DB::raw('COUNT(transaksi.id) as total_transaksi'),
                DB::raw('SUM(transaksi.total) as total_penjualan')
            )
            ->groupBy('shift.cabang_id')
            ->orderByDesc('total_penjualan')
            ->get();
        $performaPerKasir = Transaksi::join('shift', 'transaksi.shift_id', '=', 'shift.id')
            ->whereIn('shift.cabang_id', $cabangIds)
            ->whereDate('transaksi.waktu_selesai', $tanggal)
            ->where('transaksi.status', 'selesai')
            ->select(
                'shift.user_id',
                DB::raw('COUNT(DISTINCT shift.id) as total_shift'),
                DB::raw('COUNT(transaksi.id) as total_transaksi'),
                DB::raw('SUM(transaksi.total) as total_penjualan')
            )
            ->groupBy('shift.user_id')
            ->orderByDesc('total_penjualan')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'tanggal' => $tanggal,
                'shift_hari_ini' => $shiftHariIni,
                'statistik' => $statistik,
                'grafik_per_jam' => $grafikPerJam,
                'produk_terlaris' => $produkTerlaris,
                'performa_per_cabang' => $performaPerCabang,
                'performa_per_kasir' => $performaPerKasir,
            ]);
        }

        return Inertia::render('laporan/Harian', [
            'tanggal' => $tanggal,
            'shift_hari_ini' => $shiftHariIni,
            'statistik' => $statistik,
            'grafik_per_jam' => $grafikPerJam,
            'produk_terlaris' => $produkTerlaris,
            'performa_per_cabang' => $performaPerCabang,
            'performa_per_kasir' => $performaPerKasir,
        ]);
    }
    public function penjualanProduk(Request $request)
    {
        Gate::authorize('view-laporan');

        $user = Auth::user();
        $cabangIds = $this->tentukanCabangIds($user);

        $validated = $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'cabang_id' => ['nullable', 'integer', Rule::in($cabangIds)],
            'kategori_id' => 'nullable|integer',
            'tipe' => 'nullable|string',
        ]);

        $cabangId = $request->integer('cabang_id');
        $tanggalMulai = $validated['tanggal_mulai'];
        $tanggalAkhir = $validated['tanggal_selesai'];
        $kategoriId = $request->integer('kategori_id');
        $tipe = $request->get('tipe');

        $base = ItemTransaksi::query()
            ->join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
            ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->where('transaksi.status', 'selesai');

        if ($cabangId) {
            $base->where('transaksi.cabang_id', $cabangId);
        }
        $base->whereBetween('transaksi.waktu_selesai', [$tanggalMulai, $tanggalAkhir]);
        if ($kategoriId) {
            $base->where('produk.kategori_id', $kategoriId);
        }
        if ($tipe) {
            $base->where('produk.tipe', $tipe);
        }
        $totalPendapatan = (clone $base)
            ->select(DB::raw('SUM(item_transaksi.subtotal) as pendapatan'))
            ->value('pendapatan') ?? 0;
        $topProduk = (clone $base)
            ->select(
                'item_transaksi.produk_id',
                'produk.nama',
                DB::raw('SUM(item_transaksi.jumlah) as total_terjual'),
                DB::raw('SUM(item_transaksi.subtotal) as pendapatan'),
                DB::raw('COUNT(DISTINCT item_transaksi.transaksi_id) as jumlah_transaksi')
            )
            ->groupBy('item_transaksi.produk_id', 'produk.nama')
            ->orderByDesc('pendapatan')
            ->limit(10)
            ->get()
            ->map(function ($row) use ($totalPendapatan) {
                $row->rata_rata_per_transaksi = ($row->jumlah_transaksi ?? 0) > 0
                    ? round($row->pendapatan / $row->jumlah_transaksi, 2)
                    : 0;
                $row->kontribusi_persen = $totalPendapatan > 0
                    ? round(($row->pendapatan / $totalPendapatan) * 100, 2)
                    : 0;
                return $row;
            });

        $ringkasanKategori = (clone $base)
            ->join('kategori_produk', 'produk.kategori_id', '=', 'kategori_produk.id')
            ->select(
                'kategori_produk.id as kategori_id',
                'kategori_produk.nama as kategori',
                DB::raw('SUM(item_transaksi.jumlah) as total_terjual'),
                DB::raw('SUM(item_transaksi.subtotal) as pendapatan'),
                DB::raw('COUNT(DISTINCT item_transaksi.transaksi_id) as jumlah_transaksi')
            )
            ->groupBy('kategori_produk.id', 'kategori_produk.nama')
            ->orderByDesc('pendapatan')
            ->get();
        $ringkasanTipe = (clone $base)
            ->select(
                'produk.tipe',
                DB::raw('SUM(item_transaksi.jumlah) as total_terjual'),
                DB::raw('SUM(item_transaksi.subtotal) as pendapatan'),
                DB::raw('COUNT(DISTINCT item_transaksi.transaksi_id) as jumlah_transaksi')
            )
            ->groupBy('produk.tipe')
            ->orderByDesc('pendapatan')
            ->get();
        $trendHarian = (clone $base)
            ->select(
                DB::raw('DATE(transaksi.waktu_selesai) as tanggal'),
                DB::raw('SUM(item_transaksi.jumlah) as total_terjual'),
                DB::raw('SUM(item_transaksi.subtotal) as pendapatan'),
                DB::raw('COUNT(DISTINCT item_transaksi.transaksi_id) as jumlah_transaksi')
            )
            ->groupBy(DB::raw('DATE(transaksi.waktu_selesai)'))
            ->orderBy('tanggal')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'filters' => [
                    'cabang_id' => $cabangId,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalAkhir,
                    'kategori_id' => $kategoriId,
                    'tipe' => $tipe,
                ],
                'total_pendapatan' => $totalPendapatan,
                'top_produk' => $topProduk,
                'ringkasan_kategori' => $ringkasanKategori,
                'ringkasan_tipe' => $ringkasanTipe,
                'trend_harian' => $trendHarian,
            ]);
        }

        return Inertia::render('laporan/PenjualanProduk', [
            'data' => [
                'total_pendapatan' => $totalPendapatan,
                'top_produk' => $topProduk,
                'ringkasan_kategori' => $ringkasanKategori,
                'ringkasan_tipe' => $ringkasanTipe,
                'trend_harian' => $trendHarian,
            ],
        ]);
    }

    public function pendapatanKategori(Request $request)
    {
        Gate::authorize('view-laporan');

        $user = Auth::user();
        $cabangIds = $this->tentukanCabangIds($user);

        $validated = $request->validate([
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'kategori_id' => 'nullable|integer|exists:kategori_produk,id',
            'harga_min' => 'nullable|numeric|min:0',
            'harga_max' => 'nullable|numeric|min:0|gte:harga_min',
        ]);

        $tanggalMulai = $validated['tanggal_mulai'] ?? Carbon::now()->startOfMonth()->toDateString();
        $tanggalSelesai = $validated['tanggal_selesai'] ?? Carbon::now()->toDateString();
        $kategoriId = $request->integer('kategori_id') ?: null;
        $hargaMin = $validated['harga_min'] ?? null;
        $hargaMax = $validated['harga_max'] ?? null;

        $akhirHari = Carbon::parse($tanggalSelesai)->endOfDay()->toDateTimeString();

        $base = ItemTransaksi::query()
            ->join('transaksi', 'item_transaksi.transaksi_id', '=', 'transaksi.id')
            ->join('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->join('kategori_produk', 'produk.kategori_id', '=', 'kategori_produk.id')
            ->where('transaksi.status', 'selesai')
            ->when(!empty($cabangIds), fn($q) => $q->whereIn('transaksi.cabang_id', $cabangIds))
            ->whereBetween('transaksi.waktu_selesai', [$tanggalMulai, $akhirHari]);

        if ($kategoriId) {
            $base->where('produk.kategori_id', $kategoriId);
        }
        if ($hargaMin !== null) {
            $base->where('produk.harga_jual', '>=', $hargaMin);
        }
        if ($hargaMax !== null) {
            $base->where('produk.harga_jual', '<=', $hargaMax);
        }

        $perKategori = (clone $base)
            ->select(
                'kategori_produk.id as kategori_id',
                'kategori_produk.nama as kategori',
                DB::raw('SUM(item_transaksi.jumlah * produk.harga_jual) as pendapatan_kotor'),
                DB::raw('SUM(item_transaksi.jumlah * produk.harga_modal) as total_modal'),
                DB::raw('SUM(item_transaksi.jumlah * (produk.harga_jual - produk.harga_modal)) as margin')
            )
            ->groupBy('kategori_produk.id', 'kategori_produk.nama')
            ->orderByDesc('pendapatan_kotor')
            ->get()
            ->map(function ($row) {
                $pendapatanKotor = (float) ($row->pendapatan_kotor ?? 0.0);
                $totalModal = (float) ($row->total_modal ?? 0.0);
                $margin = (float) ($row->margin ?? 0.0);
                $row->pendapatan_kotor = $pendapatanKotor;
                $row->total_modal = $totalModal;
                $row->margin = $margin;
                $row->margin_persen = $pendapatanKotor > 0.0
                    ? round(($margin / $pendapatanKotor) * 100, 2)
                    : 0.0;
                return $row;
            });

        $totalPendapatanKotor = (float) $perKategori->sum('pendapatan_kotor');
        $totalModal = (float) $perKategori->sum('total_modal');
        $totalMargin = (float) $perKategori->sum('margin');
        $rataRataMarginPerKategori = $perKategori->count() > 0
            ? round($perKategori->avg('margin'), 2)
            : 0.0;

        $kategoriTertinggi = $perKategori->sortByDesc('margin')->values()->first();
        $kategoriTerendah = $perKategori->sortBy('margin')->values()->first();

        $kategoriOptions = KategoriProduk::orderBy('nama')
            ->get(['id', 'nama']);

        $ringkasan = [
            'total_pendapatan_kotor' => $totalPendapatanKotor,
            'total_modal' => $totalModal,
            'total_margin' => $totalMargin,
            'rata_rata_margin_per_kategori' => $rataRataMarginPerKategori,
            'kategori_margin_tertinggi' => $kategoriTertinggi,
            'kategori_margin_terendah' => $kategoriTerendah,
        ];

        $filterAktif = [
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'kategori_id' => $kategoriId,
            'harga_min' => $hargaMin,
            'harga_max' => $hargaMax,
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'kategori' => $perKategori,
                'ringkasan' => $ringkasan,
                'filter_aktif' => $filterAktif,
                'kategori_options' => $kategoriOptions,
            ]);
        }

        return Inertia::render('laporan/PendapatanKategori', [
            'kategori' => $perKategori,
            'ringkasan' => $ringkasan,
            'filter_aktif' => $filterAktif,
            'kategori_options' => $kategoriOptions,
        ]);
    }

    public function kinerjaKasir(Request $request)
    {
        Gate::authorize('view-laporan');

        $cabangId = $request->integer('cabang_id');
        $userId = $request->integer('user_id');
        $tanggalMulai = $request->get('tanggal_mulai', date('Y-m-01'));
        $tanggalAkhir = $request->get('tanggal_akhir', date('Y-m-t'));

        $kinerja = User::join('shift', 'users.id', '=', 'shift.user_id')
            ->leftJoin('transaksi', function ($join) {
                $join->on('shift.id', '=', 'transaksi.shift_id')
                    ->where('transaksi.status', 'selesai');
            })
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(DISTINCT shift.id) as total_shift'),
                DB::raw('SUM(CASE WHEN transaksi.id IS NOT NULL THEN 1 ELSE 0 END) as total_transaksi'),
                DB::raw('SUM(transaksi.total) as total_penjualan'),
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, shift.waktu_buka, shift.waktu_tutup)) as menit_kerja'),
                DB::raw('SUM(ABS(shift.selisih)) as selisih_total')
            )
            ->whereBetween('shift.waktu_buka', [$tanggalMulai, $tanggalAkhir])
            ->when($cabangId, fn($q) => $q->where('shift.cabang_id', $cabangId))
            ->when($userId, fn($q) => $q->where('users.id', $userId))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_penjualan')
            ->get()
            ->map(function ($row) {
                $jamKerja = ($row->menit_kerja ?? 0) / 60.0;
                $row->jam_kerja = round($jamKerja, 2);
                $row->rata_rata_per_shift = ($row->total_shift ?? 0) > 0
                    ? round(($row->total_penjualan ?? 0) / $row->total_shift, 2)
                    : 0;
                $row->penjualan_per_jam = $jamKerja > 0
                    ? round(($row->total_penjualan ?? 0) / $jamKerja, 2)
                    : 0;
                return $row;
            });

        $topKasir = $kinerja->sortByDesc('total_penjualan')->values()->take(5);

        $avgPenjualanPerShift = $kinerja->avg(fn($r) => ($r->total_shift ?? 0) > 0 ? $r->total_penjualan / $r->total_shift : 0);
        $avgPenjualanPerJam = $kinerja->avg('penjualan_per_jam');


        $trendBulanan = Transaksi::where('status', 'selesai')
            ->when($cabangId, fn($q) => $q->where('cabang_id', $cabangId))
            ->whereBetween('waktu_selesai', [$tanggalMulai, $tanggalAkhir])
            ->select(DB::raw('DATE_FORMAT(waktu_selesai, "%Y-%m") as bulan'), DB::raw('SUM(total) as total'))
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'filters' => [
                    'cabang_id' => $cabangId,
                    'user_id' => $userId,
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_akhir' => $tanggalAkhir,
                ],
                'data' => $kinerja,
                'top_5' => $topKasir,
                'rata_rata' => [
                    'per_shift' => round($avgPenjualanPerShift ?? 0, 2),
                    'per_jam' => round($avgPenjualanPerJam ?? 0, 2),
                ],
                'trend_bulanan' => $trendBulanan,
            ]);
        }

        return Inertia::render('laporan/KinerjaKasir', [
            'data' => [
                'kinerja' => $kinerja,
                'top_5' => $topKasir,
                'rata_rata' => [
                    'per_shift' => round($avgPenjualanPerShift ?? 0, 2),
                    'per_jam' => round($avgPenjualanPerJam ?? 0, 2),
                ],
                'trend_bulanan' => $trendBulanan,
            ],
        ]);
    }
    //============
    //POS Mobile
    //============

    public function ringkasanShift(Shift $shift, Request $request)
    {

        try {
            $keuangan = DB::table('transaksi')
                ->leftJoin('pembayaran', 'transaksi.id', '=', 'pembayaran.transaksi_id')
                ->where('transaksi.shift_id', $shift->id)
                ->where('transaksi.status', 'selesai')
                ->select(
                    DB::raw('COUNT(DISTINCT transaksi.id) as total_transaksi'),
                    DB::raw('COALESCE(SUM(CASE WHEN pembayaran.metode_pembayaran = "tunai" THEN pembayaran.jumlah ELSE 0 END), 0) as total_pendapatan_tunai'),
                    DB::raw('COALESCE(SUM(CASE WHEN pembayaran.metode_pembayaran = "qris" THEN pembayaran.jumlah ELSE 0 END), 0) as total_pendapatan_qris')
                )
                ->first();
        } catch (\Exception $e) {
            \Log::error('ringkasanShift query error', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage()
            ]);

            $keuangan = (object)[
                'total_transaksi' => 0,
                'total_pendapatan_tunai' => 0,
                'total_pendapatan_qris' => 0,
            ];
        }

        $totalTransaksi = max(1, (int) ($keuangan->total_transaksi ?? 0));

        return response()->json([
            'keuangan' => [
                'shift_id' => (int) $shift->id,
                'total_transaksi' => $totalTransaksi,
                'total_pendapatan_tunai' => (float) ($keuangan->total_pendapatan_tunai ?? 0),
                'total_pendapatan_qris' => (float) ($keuangan->total_pendapatan_qris ?? 0),
                'saldo_awal' => (float) $shift->saldo_awal,
                'saldo_akhir' => $shift->saldo_akhir !== null ? (float) $shift->saldo_akhir : null,
            ],
            'shift_id' => (int) $shift->id,
            'total_transaksi' => $totalTransaksi,
            'total_pendapatan_tunai' => (float) ($keuangan->total_pendapatan_tunai ?? 0),
            'total_pendapatan_qris' => (float) ($keuangan->total_pendapatan_qris ?? 0),
        ]);
    }

    public function stok(Request $request, $cabang = null)
    {
        Gate::authorize('view-laporan');

        $cabangId = $cabang ? (int) $cabang : $request->integer('cabang_id');
        $kategoriId = $request->integer('kategori_id');
        $status = $request->get('status');

        $base = StokEtalase::query()
            ->join('produk', 'stok_etalase.produk_id', '=', 'produk.id')
            ->select(
                'stok_etalase.id',
                'stok_etalase.cabang_id',
                'stok_etalase.produk_id',
                'stok_etalase.tipe_stok',
                'stok_etalase.jumlah',
                'stok_etalase.stok_minimum',
                'produk.nama',
                'produk.kategori_id',
                'produk.tipe',
                'produk.harga_modal',
                DB::raw('(stok_etalase.jumlah * produk.harga_modal) as nilai')
            );

        if ($cabangId) {
            $base->where('stok_etalase.cabang_id', $cabangId);
        }
        if ($kategoriId) {
            $base->where('produk.kategori_id', $kategoriId);
        }
        if ($status === 'rendah') {
            $base->whereColumn('stok_etalase.jumlah', '<=', 'stok_etalase.stok_minimum');
        }

        $all = (clone $base)->get();

        $nilaiInventori = (clone $base)
            ->select(DB::raw('SUM(stok_etalase.jumlah * produk.harga_modal) as total'))
            ->value('total') ?? 0;
        $lowStock = (clone $base)
            ->whereColumn('stok_etalase.jumlah', '<', 'stok_etalase.stok_minimum')
            ->select(
                'stok_etalase.id',
                'produk.nama',
                'stok_etalase.jumlah',
                'stok_etalase.stok_minimum',
                'stok_etalase.cabang_id',
                DB::raw('(stok_etalase.stok_minimum - stok_etalase.jumlah) as rekomendasi'),
                DB::raw('(stok_etalase.jumlah * produk.harga_modal) as nilai')
            )
            ->orderByDesc('rekomendasi')
            ->get();

        $expired = BatchStok::join('stok_etalase', 'batch_stok.stok_etalase_id', '=', 'stok_etalase.id')
            ->join('produk', 'stok_etalase.produk_id', '=', 'produk.id')
            ->when($cabangId, fn($q) => $q->where('stok_etalase.cabang_id', $cabangId))
            ->when($kategoriId, fn($q) => $q->where('produk.kategori_id', $kategoriId))
            ->whereDate('batch_stok.tanggal_kadaluarsa', '<=', now())
            ->select(
                'batch_stok.id',
                'stok_etalase.id as stok_id',
                'stok_etalase.cabang_id',
                'produk.nama',
                'batch_stok.jumlah',
                'batch_stok.tanggal_kadaluarsa',
                DB::raw('(batch_stok.jumlah * produk.harga_modal) as nilai')
            )
            ->orderBy('batch_stok.tanggal_kadaluarsa')
            ->get();

        $potensiKerugian = $expired->sum('nilai');
        $oldestBatch = BatchStok::join('stok_etalase', 'batch_stok.stok_etalase_id', '=', 'stok_etalase.id')
            ->join('produk', 'stok_etalase.produk_id', '=', 'produk.id')
            ->when($cabangId, fn($q) => $q->where('stok_etalase.cabang_id', $cabangId))
            ->when($kategoriId, fn($q) => $q->where('produk.kategori_id', $kategoriId))
            ->select('stok_etalase.produk_id', 'produk.nama', DB::raw('MIN(batch_stok.tanggal_kadaluarsa) as tanggal_tertua'))
            ->groupBy('stok_etalase.produk_id', 'produk.nama')
            ->get();
        $perCabang = StokEtalase::join('produk', 'stok_etalase.produk_id', '=', 'produk.id')
            ->join('cabang', 'stok_etalase.cabang_id', '=', 'cabang.id')
            ->when($kategoriId, fn($q) => $q->where('produk.kategori_id', $kategoriId))
            ->select(
                'cabang.id as cabang_id',
                'cabang.nama as cabang',
                DB::raw('SUM(stok_etalase.jumlah * produk.harga_modal) as nilai_inventori'),
                DB::raw('SUM(CASE WHEN stok_etalase.jumlah < stok_etalase.stok_minimum THEN 1 ELSE 0 END) as stok_rendah_count')
            )
            ->groupBy('cabang.id', 'cabang.nama')
            ->orderByDesc('nilai_inventori')
            ->get();
        $perKategori = StokEtalase::join('produk', 'stok_etalase.produk_id', '=', 'produk.id')
            ->join('kategori_produk', 'produk.kategori_id', '=', 'kategori_produk.id')
            ->when($cabangId, fn($q) => $q->where('stok_etalase.cabang_id', $cabangId))
            ->select(
                'kategori_produk.id as kategori_id',
                'kategori_produk.nama as kategori',
                DB::raw('SUM(stok_etalase.jumlah * produk.harga_modal) as nilai_inventori'),
                DB::raw('SUM(CASE WHEN stok_etalase.jumlah < stok_etalase.stok_minimum THEN 1 ELSE 0 END) as stok_rendah_count')
            )
            ->groupBy('kategori_produk.id', 'kategori_produk.nama')
            ->orderByDesc('nilai_inventori')
            ->get();
        $lowIds = $lowStock->pluck('id')->all();
        $normal = $all->filter(fn($s) => !in_array($s->id, $lowIds));

        $stokSummary = [
            'nilai_inventori' => $nilaiInventori,
            'stok_rendah' => [
                'count' => $lowStock->count(),
                'items' => $lowStock,
            ],
            'kadaluarsa' => [
                'count' => $expired->count(),
                'potensi_kerugian' => $potensiKerugian,
                'batches' => $expired,
                'oldest_batch' => $oldestBatch,
            ],
            'normal' => [
                'count' => $normal->count(),
            ],
            'per_cabang' => $perCabang,
            'per_kategori' => $perKategori,
            'rekomendasi_pembelian' => $lowStock->map(fn($s) => [
                'stok_etalase_id' => $s->id,
                'produk' => $s->nama,
                'cabang_id' => $s->cabang_id,
                'order_qty' => max(0, (float) $s->rekomendasi),
            ]),
        ];

        if ($request->expectsJson()) {
            return response()->json(['data' => $stokSummary]);
        }

        return Inertia::render('laporan/Stok', [
            'data' => $stokSummary,
        ]);
    }

    public function kinerjaUser(User $user, Request $request)
    {
        $tanggalMulai = $request->get('tanggal_mulai', date('Y-m-01'));
        $tanggalAkhir = $request->get('tanggal_akhir', date('Y-m-t'));

        $kinerja = Transaksi::join('shift', 'transaksi.shift_id', '=', 'shift.id')
            ->where('shift.user_id', $user->id)
            ->whereBetween('transaksi.waktu_selesai', [$tanggalMulai, $tanggalAkhir])
            ->where('transaksi.status', 'selesai')
            ->select(DB::raw('COUNT(transaksi.id) as total_transaksi'), DB::raw('SUM(transaksi.total) as total_penjualan'))
            ->first();

        return response()->json([
            'user_id' => $user->id,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'total_transaksi' => $kinerja->total_transaksi ?? 0,
            'total_penjualan' => $kinerja->total_penjualan ?? 0,
        ]);
    }

    public function exportPdf(Request $request)
    {
        Gate::authorize('view-laporan');
        $validated = $request->validate([
            'jenis' => 'required|in:penjualan_produk,stok,kinerja_kasir',
            'cabang_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_akhir' => 'nullable|date',
            'kategori_id' => 'nullable|integer',
            'tipe' => 'nullable|string',
        ]);

        $jenis = $validated['jenis'];

        if ($jenis === 'penjualan_produk') {
            $response = $this->penjualanProduk($request->merge(['expectsJson' => true]));
        } elseif ($jenis === 'stok') {
            $response = $this->stok($request->merge(['expectsJson' => true]));
        } else {
            $response = $this->kinerjaKasir($request->merge(['expectsJson' => true]));
        }

        $payload = method_exists($response, 'getData')
            ? $response->getData(true)
            : [];

        $html = view('exports.laporan-generic', [
            'jenis' => $jenis,
            'data' => $payload,
            'generated_at' => now()->toDateTimeString(),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function exportExcel(Request $request)
    {
        Gate::authorize('view-laporan');
        $validated = $request->validate([
            'jenis' => 'required|in:penjualan_produk,stok,kinerja_kasir',
            'cabang_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_akhir' => 'nullable|date',
            'kategori_id' => 'nullable|integer',
            'tipe' => 'nullable|string',
        ]);

        $jenis = $validated['jenis'];

        if ($jenis === 'penjualan_produk') {
            $response = $this->penjualanProduk($request->merge(['expectsJson' => true]));
            $payload = method_exists($response, 'getData') ? $response->getData(true) : [];
            $rows = $payload['top_produk'] ?? [];
            $filename = 'laporan_penjualan_produk.csv';
            $header = ['Produk', 'Total Terjual', 'Pendapatan', 'Rata-rata/Transaksi', 'Kontribusi %'];
            $extract = function ($row) {
                return [
                    $row['nama'] ?? '',
                    $row['total_terjual'] ?? 0,
                    $row['pendapatan'] ?? 0,
                    $row['rata_rata_per_transaksi'] ?? 0,
                    $row['kontribusi_persen'] ?? 0,
                ];
            };
        } elseif ($jenis === 'stok') {
            $response = $this->stok($request->merge(['expectsJson' => true]));
            $payload = method_exists($response, 'getData') ? $response->getData(true) : [];
            $ringkasan = $payload['data'] ?? [];
            $rows = $ringkasan['per_cabang'] ?? [];
            $filename = 'laporan_stok.csv';
            $header = ['Cabang', 'Nilai Inventori', 'Jumlah Stok Rendah'];
            $extract = function ($row) {
                return [
                    $row['cabang'] ?? '',
                    $row['nilai_inventori'] ?? 0,
                    $row['stok_rendah_count'] ?? 0,
                ];
            };
        } else {
            $response = $this->kinerjaKasir($request->merge(['expectsJson' => true]));
            $payload = method_exists($response, 'getData') ? $response->getData(true) : [];
            $data = $payload['data'] ?? [];
            $rows = $data['kinerja'] ?? $data ?? [];
            $filename = 'laporan_kinerja_kasir.csv';
            $header = [
                'Kasir',
                'Total Shift',
                'Total Transaksi',
                'Total Penjualan',
                'Jam Kerja',
                'Rata-rata/Shift',
                'Penjualan Per Jam',
                'Total Selisih',
            ];
            $extract = function ($row) {
                return [
                    $row['name'] ?? '',
                    $row['total_shift'] ?? 0,
                    $row['total_transaksi'] ?? 0,
                    $row['total_penjualan'] ?? 0,
                    $row['jam_kerja'] ?? 0,
                    $row['rata_rata_per_shift'] ?? 0,
                    $row['penjualan_per_jam'] ?? 0,
                    $row['selisih_total'] ?? 0,
                ];
            };
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $header);
        foreach ($rows as $row) {
            $normalized = is_object($row) ? (array) $row : $row;
            fputcsv($handle, $extract($normalized));
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        $disposition = 'attachment; filename="' . $filename . '"';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => $disposition,
        ]);
    }

    private function tentukanCabangIds(User $user): array
    {
        if (in_array($user->role, ['manager', 'it_support'], true)) {
            return Cabang::pluck('id')->all();
        }
        if (method_exists($user, 'cabang') && $user->cabang) {
            return $user->cabang->pluck('id')->all();
        }
        return [];
    }
}
