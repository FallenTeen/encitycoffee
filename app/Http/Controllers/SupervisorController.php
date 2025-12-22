<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\HierarchyAssignmentService;
use Carbon\Carbon;
use App\Models\Shift;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Cabang;
use App\Models\StokEtalase;
use App\Models\BatchStok;
use Inertia\Inertia;

class SupervisorController extends Controller
{

    public function index(Request $request)
    {
        Gate::authorize('view-manager-userManagement');

        $user = Auth::user();
        $role = strtolower((string) $user->role);
        $allowedCabangIds = $role === 'it_support'
            ? Cabang::pluck('id')->all()
            : $user->cabang()->pluck('cabang.id')->all();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'cabang_id' => ['nullable', Rule::in($allowedCabangIds)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()
            ->where('role', 'supervisor')
            ->with('cabang');

        if ($role !== 'it_support') {
            $query->whereHas('cabang', fn ($q) => $q->whereIn('cabang.id', $allowedCabangIds))
                ->whereDoesntHave('cabang', fn ($q) => $q->whereNotIn('cabang.id', $allowedCabangIds));
        }

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }
        if (! empty($validated['cabang_id'])) {
            $cabangId = (int) $validated['cabang_id'];
            $query->whereHas('cabang', fn ($q) => $q->where('cabang.id', $cabangId));
        }

        $perPage = (int) ($validated['per_page'] ?? 20);
        $supervisors = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $cabangList = $role === 'it_support'
            ? Cabang::orderBy('kode')->get(['id', 'kode', 'nama'])
            : $user->cabang()->get(['cabang.id', 'cabang.kode', 'cabang.nama']);

        return Inertia::render('manager/supervisor/Index', [
            'supervisors' => $supervisors,
            'cabang_list' => $cabangList,
            'filter_aktif' => [
                'search' => $validated['search'] ?? '',
                'cabang_id' => $validated['cabang_id'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request)
    {
        Gate::authorize('view-manager-userManagement');

        $user = $request->user();
        $role = strtolower((string) $user->role);
        $cabangList = $role === 'it_support'
            ? Cabang::orderBy('kode')->get(['id', 'kode', 'nama'])
            : $user->cabang()->get(['cabang.id', 'cabang.kode', 'cabang.nama']);

        return Inertia::render('manager/supervisor/Create', [
            'cabang_list' => $cabangList,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create-user');

        $actor = $request->user();
        $role = strtolower((string) $actor->role);
        $allowedCabangIds = $role === 'it_support'
            ? Cabang::pluck('id')->all()
            : $actor->cabang()->pluck('cabang.id')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'aktif' => ['nullable', 'boolean'],
            'cabang_ids' => ['required', 'array', 'size:1'],
            'cabang_ids.*' => ['integer', Rule::in($allowedCabangIds)],
        ]);

        $supervisor = null;
        DB::transaction(function () use ($validated, &$supervisor, $actor) {
            $supervisor = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'supervisor',
                'aktif' => $validated['aktif'] ?? true,
            ]);
            app(HierarchyAssignmentService::class)->syncRoleAndCabang($actor, $supervisor, 'supervisor', array_map('intval', $validated['cabang_ids']));
        });

        Log::info('Supervisor created', ['actor_user_id' => (int) $actor->id, 'user_id' => (int) $supervisor->id]);
        return redirect()->route('manager.supervisor.index')->with('success', 'Supervisor berhasil dibuat');
    }

    public function edit(Request $request, User $supervisor)
    {
        Gate::authorize('edit-user');
        $this->assertCanManageSupervisor($request->user(), $supervisor);

        $actor = $request->user();
        $role = strtolower((string) $actor->role);
        $cabangList = $role === 'it_support'
            ? Cabang::orderBy('kode')->get(['id', 'kode', 'nama'])
            : $actor->cabang()->get(['cabang.id', 'cabang.kode', 'cabang.nama']);

        $supervisor->load('cabang:id,kode,nama');

        return Inertia::render('manager/supervisor/Edit', [
            'supervisor' => $supervisor,
            'cabang_list' => $cabangList,
        ]);
    }

    public function update(Request $request, User $supervisor)
    {
        Gate::authorize('edit-user');
        $this->assertCanManageSupervisor($request->user(), $supervisor);

        $actor = $request->user();
        $role = strtolower((string) $actor->role);
        $allowedCabangIds = $role === 'it_support'
            ? Cabang::pluck('id')->all()
            : $actor->cabang()->pluck('cabang.id')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($supervisor->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'aktif' => ['nullable', 'boolean'],
            'cabang_ids' => ['required', 'array', 'size:1'],
            'cabang_ids.*' => ['integer', Rule::in($allowedCabangIds)],
        ]);

        DB::transaction(function () use ($validated, $supervisor, $actor) {
            $supervisor->name = $validated['name'];
            $supervisor->email = $validated['email'];
            if (! empty($validated['password'])) {
                $supervisor->password = Hash::make($validated['password']);
            }
            $supervisor->aktif = $validated['aktif'] ?? $supervisor->aktif;
            $supervisor->save();
            app(HierarchyAssignmentService::class)->syncRoleAndCabang($actor, $supervisor, 'supervisor', array_map('intval', $validated['cabang_ids']));
        });

        Log::info('Supervisor updated', ['actor_user_id' => (int) $actor->id, 'user_id' => (int) $supervisor->id]);
        return redirect()->route('manager.supervisor.index')->with('success', 'Supervisor berhasil diperbarui');
    }

    public function destroy(Request $request, User $supervisor)
    {
        Gate::authorize('delete-user');
        $this->assertCanManageSupervisor($request->user(), $supervisor);

        app(HierarchyAssignmentService::class)->clearHierarchyForUser($supervisor);
        $supervisor->delete();
        Log::info('Supervisor deleted', ['actor_user_id' => (int) $request->user()->id, 'user_id' => (int) $supervisor->id]);
        return redirect()->route('manager.supervisor.index')->with('success', 'Supervisor berhasil dihapus');
    }

    public function show(Request $request, User $supervisor)
    {
        Gate::authorize('view-manager-userManagement');
        $this->assertCanManageSupervisor($request->user(), $supervisor);
        $supervisor->load('cabang:id,kode,nama');
        return Inertia::render('manager/supervisor/Show', [
            'supervisor' => $supervisor,
        ]);
    }

    public function dashboard()
    {
        Gate::authorize('view-supervisor-dashboard');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $shiftAktif = Shift::whereIn('cabang_id', $cabangIds)
            ->where('status', 'buka')
            ->with(['user', 'cabang'])
            ->get();

        $totalTransaksiHariIni = Transaksi::whereIn('cabang_id', $cabangIds)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $totalPenjualanHariIni = Transaksi::whereIn('cabang_id', $cabangIds)
            ->whereDate('created_at', Carbon::today())
            ->sum('total');

        $statistikHariIni = [
            'total_shift_aktif' => $shiftAktif->count(),
            'total_transaksi' => $totalTransaksiHariIni,
            'total_penjualan' => (float) $totalPenjualanHariIni,
            'rata_rata_per_transaksi' => $totalTransaksiHariIni > 0
                ? (float) ($totalPenjualanHariIni / $totalTransaksiHariIni)
                : 0.0,
        ];

        $stokRendah = StokEtalase::whereIn('cabang_id', $cabangIds)
            ->whereColumn('jumlah', '<=', 'stok_minimum')
            ->with(['produk', 'cabang'])
            ->get();

        $mendekatiKadaluarsa = BatchStok::whereHas('stokEtalase', function ($q) use ($cabangIds) {
                $q->whereIn('cabang_id', $cabangIds);
            })
            ->whereDate('tanggal_kadaluarsa', '<=', Carbon::now()->addDays(7))
            ->with(['stokEtalase.produk', 'stokEtalase.cabang'])
            ->orderBy('tanggal_kadaluarsa')
            ->get();

        $stokAlert = [
            'stok_rendah' => $stokRendah,
            'mendekati_kadaluarsa' => $mendekatiKadaluarsa,
        ];

        $transaksiTerbaru = Transaksi::whereIn('cabang_id', $cabangIds)
            ->with(['user', 'item.produk'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $kasirAktif = User::whereHas('shift', function ($q) {
                $q->where('status', 'buka');
            })
            ->where('role', 'kasir')
            ->get();

        return Inertia::render('supervisor/Dashboard', [
            'shift_aktif' => $shiftAktif,
            'statistik_hari_ini' => $statistikHariIni,
            'stok_alert' => $stokAlert,
            'transaksi_terbaru' => $transaksiTerbaru,
            'kasir_aktif' => $kasirAktif,
        ]);
    }

    private function assertCanManageSupervisor(User $actor, User $supervisor): void
    {
        if ((string) $supervisor->role !== 'supervisor') {
            abort(404);
        }

        $actorRole = strtolower((string) $actor->role);
        if ($actorRole === 'it_support') {
            return;
        }

        $allowedCabangIds = $actor->cabang()->pluck('cabang.id')->all();
        if (empty($allowedCabangIds)) {
            abort(403);
        }

        $targetCabangIds = $supervisor->cabang()->pluck('cabang.id')->all();
        $diff = array_values(array_diff($targetCabangIds, $allowedCabangIds));
        if (! empty($diff)) {
            abort(403);
        }
    }

    public function monitoringShift(Request $request)
    {
        Gate::authorize('view-supervisor-monitorShift');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'closed'])],
            'tanggal' => ['nullable', 'date'],
            'cabang_id' => ['nullable', Rule::in($cabangIds)],
            'user_id' => ['nullable', 'integer'],
        ]);

        $query = Shift::whereIn('cabang_id', $cabangIds);

        if (!empty($validated['status'])) {
            $status = $validated['status'] === 'open' ? 'buka' : 'tutup';
            $query->where('status', $status);
        }

        if (!empty($validated['tanggal'])) {
            $tanggal = Carbon::parse($validated['tanggal'])->toDateString();
            $query->whereDate('waktu_buka', $tanggal);
        } else {
            $query->whereDate('waktu_buka', Carbon::today());
        }

        if (!empty($validated['cabang_id'])) {
            $query->where('cabang_id', (int) $validated['cabang_id']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }

        $shift = $query->with(['user', 'cabang', 'transaksi', 'kalibrasi'])
            ->orderByDesc('waktu_buka')
            ->paginate(20)
            ->through(function (Shift $s) {
                $totalTransaksi = $s->transaksi->count();
                $totalPenjualan = (float) $s->transaksi->sum('total');
                $durasiShift = null;
                $statusSelisih = null;

                if ($s->status === 'tutup' && $s->waktu_tutup) {
                    $durasiShift = Carbon::parse($s->waktu_buka)->diffInMinutes(Carbon::parse($s->waktu_tutup));
                    $statusSelisih = ((float) ($s->selisih ?? 0)) === 0.0 ? 'sesuai' : 'beda';
                }

                return [
                    'id' => $s->id,
                    'user' => $s->user,
                    'cabang' => $s->cabang,
                    'waktu_buka' => $s->waktu_buka,
                    'waktu_tutup' => $s->waktu_tutup,
                    'status' => $s->status,
                    'total_transaksi' => $totalTransaksi,
                    'total_penjualan' => $totalPenjualan,
                    'durasi_shift_menit' => $durasiShift,
                    'status_selisih' => $statusSelisih,
                ];
            });

        $collection = $shift->getCollection();
        $ringkasan = [
            'total_shift' => $shift->total(),
            'total_transaksi' => (int) $collection->sum('total_transaksi'),
            'total_penjualan' => (float) $collection->sum('total_penjualan'),
        ];

        return Inertia::render('supervisor/MonitorShift', [
            'shift' => $shift,
            'filter_aktif' => $validated,
            'statistik_ringkasan' => $ringkasan,
        ]);
    }

    public function shiftDetails(Shift $shift)
    {
        Gate::authorize('view-supervisor-shiftDetails');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        if (!in_array($shift->cabang_id, $cabangIds)) {
            abort(403, 'Tidak memiliki akses');
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
        $totalPenjualan = (float) $shift->transaksi->sum('total');
        $durasi = $shift->waktu_tutup
            ? Carbon::parse($shift->waktu_buka)->diffInMinutes(Carbon::parse($shift->waktu_tutup))
            : null;

        $statistik = [
            'total_transaksi' => $totalTransaksi,
            'total_penjualan' => $totalPenjualan,
            'total_tunai' => (float) ($shift->total_tunai ?? 0),
            'total_qris' => (float) ($shift->total_qris ?? 0),
            'saldo_awal' => (float) ($shift->saldo_awal ?? 0),
            'saldo_akhir' => (float) ($shift->saldo_akhir ?? 0),
            'selisih' => (float) ($shift->selisih ?? 0),
            'durasi_menit' => $durasi,
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

        return Inertia::render('supervisor/ShiftDetails', [
            'shift' => $shift,
            'statistik' => $statistik,
            'produk_terjual' => $produkTerjual,
            'kalibrasi_detail' => $kalibrasiDetail,
            'timeline' => $timeline,
        ]);
    }

    public function laporanStok()
    {
        Gate::authorize('view-supervisor-laporanStok');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $stok = StokEtalase::whereIn('cabang_id', $cabangIds)
            ->with(['produk.kategori', 'batch', 'cabang'])
            ->get();

        $stokPerCabang = $stok->groupBy('cabang_id')->map(function ($items, $cabangId) {
            $normal = $items->filter(fn($s) => (float) $s->jumlah > (float) $s->stok_minimum);
            $rendah = $items->filter(fn($s) => (float) $s->jumlah <= (float) $s->stok_minimum);
            $nilaiTotal = $items->sum(function ($s) {
                $hargaModal = (float) optional($s->produk)->harga_modal;
                return (float) $s->jumlah * $hargaModal;
            });
            return [
                'cabang_id' => (int) $cabangId,
                'stok_normal' => $normal->values(),
                'stok_rendah' => $rendah->values(),
                'nilai_total' => $nilaiTotal,
            ];
        })->values();

        $nilaiInventoriTotal = $stokPerCabang->sum('nilai_total');
        $stokRendahCount = $stokPerCabang->sum(fn($c) => count($c['stok_rendah']));

        $batchKadaluarsa = BatchStok::whereHas('stokEtalase', function ($q) use ($cabangIds) {
                $q->whereIn('cabang_id', $cabangIds);
            })
            ->whereDate('tanggal_kadaluarsa', '<=', Carbon::now()->addDays(30))
            ->with(['stokEtalase.produk', 'stokEtalase.cabang'])
            ->orderBy('tanggal_kadaluarsa')
            ->get();

        $lowStockList = StokEtalase::whereIn('cabang_id', $cabangIds)
            ->whereColumn('jumlah', '<', 'stok_minimum')
            ->with(['produk', 'cabang'])
            ->get()
            ->map(function ($s) {
                return [
                    'stok_etalase_id' => $s->id,
                    'produk' => optional($s->produk)->nama,
                    'cabang_id' => $s->cabang_id,
                    'rekomendasi' => max(0.0, (float) $s->stok_minimum - (float) $s->jumlah),
                ];
            });

        return Inertia::render('supervisor/LaporanStok', [
            'lowStock' => $lowStockList,
            'expired' => $batchKadaluarsa,
            // Props lengkap sesuai spesifikasi Anda
            'stok_per_cabang' => $stokPerCabang,
            'total_nilai_inventori' => $nilaiInventoriTotal,
            'stok_rendah_count' => $stokRendahCount,
            'batch_kadaluarsa' => $batchKadaluarsa,
            'rekomendasi_pembelian' => $lowStockList,
        ]);
    }
}
