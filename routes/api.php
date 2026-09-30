<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\KalibrasiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MobileBundlingController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SinkronisasiController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\TransaksiController;
use App\Models\Cabang;
use App\Models\User;
use App\Services\HierarchyAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::middleware(['auth:sanctum', 'role:it_support'])->prefix('admin')->group(function () {
    Route::prefix('cabang')->group(function () {
        Route::get('/', function (Request $request) {
            return response()->json(Cabang::orderBy('kode')->paginate((int) $request->get('per_page', 20)));
        });

        Route::post('/', function (Request $request) {
            $validated = $request->validate([
                'kode' => ['required', 'string', 'max:255', 'unique:cabang,kode'],
                'nama' => ['nullable', 'string', 'max:255'],
                'alamat' => ['nullable', 'string'],
                'telepon' => ['nullable', 'string', 'max:255'],
                'aktif' => ['nullable', 'boolean'],
            ]);

            $cabang = Cabang::create($validated);

            return response()->json(['cabang' => $cabang], 201);
        });

        Route::put('{cabang}', function (Request $request, Cabang $cabang) {
            $validated = $request->validate([
                'kode' => ['sometimes', 'string', 'max:255', Rule::unique('cabang', 'kode')->ignore($cabang->id)],
                'nama' => ['sometimes', 'nullable', 'string', 'max:255'],
                'alamat' => ['sometimes', 'nullable', 'string'],
                'telepon' => ['sometimes', 'nullable', 'string', 'max:255'],
                'aktif' => ['sometimes', 'boolean'],
            ]);

            $cabang->fill($validated)->save();

            return response()->json(['cabang' => $cabang]);
        });

        Route::delete('{cabang}', function (Request $request, Cabang $cabang) {
            $cabang->delete();

            return response()->json(['deleted' => true]);
        });
    });

    Route::get('audit-logs', function (Request $request) {
        $perPage = (int) $request->get('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $query = DB::table('audit_logs')
            ->join('users as actor', 'audit_logs.actor_user_id', '=', 'actor.id')
            ->select(
                'audit_logs.id',
                'audit_logs.actor_user_id',
                'actor.name as actor_name',
                'actor.email as actor_email',
                'audit_logs.method',
                'audit_logs.path',
                'audit_logs.ip',
                'audit_logs.user_agent',
                'audit_logs.subject_type',
                'audit_logs.subject_id',
                'audit_logs.payload',
                'audit_logs.created_at'
            )
            ->orderByDesc('audit_logs.id');

        if ($request->filled('actor_user_id')) {
            $query->where('audit_logs.actor_user_id', $request->integer('actor_user_id'));
        }
        if ($request->filled('method')) {
            $query->where('audit_logs.method', strtoupper($request->string('method')->toString()));
        }
        if ($request->filled('path')) {
            $query->where('audit_logs.path', 'like', '%'.$request->string('path')->toString().'%');
        }
        if ($request->filled('subject_type')) {
            $query->where('audit_logs.subject_type', $request->string('subject_type')->toString());
        }
        if ($request->filled('subject_id')) {
            $query->where('audit_logs.subject_id', $request->integer('subject_id'));
        }

        return response()->json($query->paginate($perPage));
    });
});

Route::middleware(['auth:sanctum', 'role:it_support,manager,supervisor'])->prefix('user-management')->group(function () {
    Route::get('users', function (Request $request) {
        $actor = $request->user();
        $actorRole = strtolower((string) $actor->role);
        $perPage = (int) $request->get('per_page', 20);

        $query = User::query()->with(['cabang:id,kode,nama']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($actorRole === 'manager') {
            $cabangIds = $actor->cabang()->pluck('cabang.id')->all();
            $query->whereIn('role', ['supervisor', 'kasir'])
                ->whereHas('cabang', fn ($q) => $q->whereIn('cabang.id', $cabangIds))
                ->whereDoesntHave('cabang', fn ($q) => $q->whereNotIn('cabang.id', $cabangIds));
        } elseif ($actorRole === 'supervisor') {
            $cabangIds = $actor->cabang()->pluck('cabang.id')->all();
            $query->where('role', 'kasir')
                ->whereHas('cabang', fn ($q) => $q->whereIn('cabang.id', $cabangIds))
                ->whereDoesntHave('cabang', fn ($q) => $q->whereNotIn('cabang.id', $cabangIds));
        }

        if ($request->filled('role')) {
            $role = $request->string('role')->toString();
            $query->where('role', $role);
        }
        if ($request->filled('aktif')) {
            $aktif = filter_var($request->get('aktif'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($aktif !== null) {
                $query->where('aktif', $aktif);
            }
        }
        if ($request->filled('cabang_id')) {
            $query->whereHas('cabang', fn ($q) => $q->where('cabang.id', $request->integer('cabang_id')));
        }

        return response()->json($query->orderBy('name')->paginate($perPage));
    });

    Route::post('users', function (Request $request) {
        $actor = $request->user();
        $actorRole = strtolower((string) $actor->role);

        $allowedRoles = match ($actorRole) {
            'it_support' => ['manager', 'supervisor', 'kasir'],
            'manager' => ['supervisor', 'kasir'],
            default => ['kasir'],
        };

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in($allowedRoles)],
            'aktif' => ['nullable', 'boolean'],
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
        ]);

        $role = $validated['role'];
        $cabangIds = $validated['cabang_ids'] ?? [];
        unset($validated['cabang_ids']);

        $validated['password'] = Hash::make($validated['password']);
        $validated['aktif'] = $validated['aktif'] ?? true;

        $user = User::create($validated);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($actor, $user, (string) $role, array_map('intval', $cabangIds));

        $user->load('cabang:id,kode,nama');
        Log::info('API user created', ['actor_user_id' => (int) $actor->id, 'user_id' => (int) $user->id, 'role' => (string) $user->role]);

        return response()->json(['user' => $user], 201);
    });

    Route::put('users/{user}', function (Request $request, User $user) {
        $actor = $request->user();
        $actorRole = strtolower((string) $actor->role);
        $allowRoleField = $actorRole === 'it_support';

        if ($actorRole === 'it_support' && strtolower((string) $user->role) === 'it_support' && (int) $user->id !== (int) $actor->id) {
            abort(403);
        }

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'aktif' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'cabang_ids' => ['sometimes', 'array', 'min:1'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
        ];
        if ($allowRoleField) {
            $rules['role'] = ['sometimes', Rule::in(['manager', 'supervisor', 'kasir'])];
        }

        $validated = $request->validate($rules);

        if (array_key_exists('password', $validated)) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $cabangIds = null;
        if (array_key_exists('cabang_ids', $validated)) {
            $cabangIds = $validated['cabang_ids'];
            unset($validated['cabang_ids']);
        }

        $user->fill($validated)->save();
        $finalCabangIds = is_array($cabangIds) ? array_map('intval', $cabangIds) : $user->cabang()->pluck('cabang.id')->all();
        $finalRole = array_key_exists('role', $validated) ? (string) $validated['role'] : (string) $user->role;
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($actor, $user, $finalRole, $finalCabangIds);

        $user->load('cabang:id,kode,nama');
        Log::info('API user updated', ['actor_user_id' => (int) $actor->id, 'user_id' => (int) $user->id]);

        return response()->json(['user' => $user]);
    });

    Route::delete('users/{user}', function (Request $request, User $user) {
        $actor = $request->user();
        if (strtolower((string) $actor->role) === 'it_support' && strtolower((string) $user->role) === 'it_support' && (int) $user->id !== (int) $actor->id) {
            abort(403);
        }
        app(HierarchyAssignmentService::class)->clearHierarchyForUser($user);
        $user->delete();
        Log::info('API user deleted', ['actor_user_id' => (int) $actor->id, 'user_id' => (int) $user->id]);

        return response()->json(['deleted' => true]);
    });

    Route::post('users/{user}/cabang', function (Request $request, User $user) {
        if (strtolower((string) $user->role) === 'it_support') {
            return response()->json(['error' => 'it_support tidak boleh ditetapkan ke cabang'], 422);
        }
        $validated = $request->validate([
            'cabang_ids' => ['required', 'array', 'min:1'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
        ]);

        app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $user, (string) $user->role, array_map('intval', $validated['cabang_ids']));
        $user->load('cabang:id,kode,nama');

        return response()->json(['user' => $user]);
    });
});

// -----------------------------
// POS API (real routes, requires auth)
// prefix: /api/pos/...
// -----------------------------
Route::prefix('pos')->group(function () {
    // Authentication (login tanpa auth)
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        // auth:sanctum already rejects unknown and expired tokens with a 401,
        // so no extra token-checking middleware is needed here.
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // Semua endpoint POS menggunakan auth:sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::middleware('role:kasir,supervisor,manager,it_support')->group(function () {
            Route::prefix('cabang')->group(function () {
                Route::get('/', function (Request $request) {
                    $user = $request->user();
                    $role = strtolower((string) $user->role);

                    $base = Cabang::query()->select('id', 'kode', 'nama', 'alamat', 'telepon', 'aktif')->orderBy('id', 'asc');
                    if ($role !== 'it_support') {
                        $cabangIds = $user->cabang()->pluck('cabang.id')->all();
                        $base->whereIn('id', $cabangIds);
                    }

                    $cabangs = $base->get()->map(function ($cabang) {
                        return [
                            'id' => $cabang->id,
                            'kode' => $cabang->kode,
                            'nama' => $cabang->nama,
                            'alamat' => $cabang->alamat,
                            'telepon' => $cabang->telepon,
                            'status' => (bool) $cabang->aktif,
                        ];
                    });

                    return response()->json([
                        'success' => true,
                        'data' => $cabangs,
                    ]);
                });
            });

            Route::prefix('shift')->group(function () {
                Route::get('aktif', [ShiftController::class, 'dapatkanAktif']);
                Route::post('buka', [ShiftController::class, 'buka']);
                Route::post('{shift}/tutup', [ShiftController::class, 'tutup']);
                Route::get('{shift}/open-bills', [ShiftController::class, 'openBills']);
                Route::get('{shift}', [ShiftController::class, 'tampilkan']);
                Route::get('/', [ShiftController::class, 'daftar']);
            });

            Route::get('kasir', function (Request $request) {
                $user = $request->user();
                $cabangIds = $user->cabang()->pluck('cabang.id')->all();

                $cabangId = $request->integer('cabang_id');
                if ($cabangId !== null && ! in_array($cabangId, $cabangIds, true)) {
                    return response()->json([
                        'success' => false,
                        'data' => [],
                        'error' => 'Cabang tidak valid untuk user ini',
                    ], 403);
                }

                $query = User::query()
                    ->where('role', 'kasir')
                    ->whereHas('cabang', fn ($q) => $q->whereIn('cabang.id', $cabangIds))
                    ->whereDoesntHave('cabang', fn ($q) => $q->whereNotIn('cabang.id', $cabangIds));

                if ($cabangId !== null) {
                    $query->whereHas('cabang', fn ($q) => $q->where('cabang.id', $cabangId));
                }

                $kasirs = $query
                    ->orderBy('name')
                    ->get(['id', 'name', 'email', 'role', 'aktif'])
                    ->map(function (User $kasir) {
                        return [
                            'id' => (int) $kasir->id,
                            'name' => (string) $kasir->name,
                            'email' => (string) $kasir->email,
                            'role' => (string) $kasir->role,
                            'aktif' => (bool) $kasir->aktif,
                        ];
                    })
                    ->values();

                return response()->json([
                    'success' => true,
                    'data' => $kasirs,
                ]);
            });

            Route::prefix('kalibrasi')->group(function () {
                Route::post('/', [KalibrasiController::class, 'simpan']);
                Route::put('{kalibrasi}/pilih', [KalibrasiController::class, 'pilih']);
                Route::get('shift/{shift}', [KalibrasiController::class, 'dapatkanBerdasarkanShift']);
            });

            Route::prefix('produk')->group(function () {
                Route::get('/', [ProdukController::class, 'daftarProduk']);
                Route::get('mobile', [ProdukController::class, 'mobileProduk']);
                Route::get('{produk}', [ProdukController::class, 'tampilkanProduk']);
                Route::get('tipe/{tipe}', [ProdukController::class, 'produkBerdasarkanTipe']);
                Route::get('{produk}/satuan', [ProdukController::class, 'satuanProduk']);
            });

            Route::get('bundling', [MobileBundlingController::class, 'index']);

            Route::prefix('transaksi')->group(function () {
                Route::post('/', [TransaksiController::class, 'buatTransaksi']);
                Route::get('{transaksi}', [TransaksiController::class, 'tampilkanTransaksi']);
                Route::put('{transaksi}/batal', [TransaksiController::class, 'batalkanTransaksi']);
                Route::get('shift/{shift}', [TransaksiController::class, 'transaksiPerShift']);
                Route::post('open-bill', [TransaksiController::class, 'buatOpenBill']);
                Route::get('open-bill/list', [TransaksiController::class, 'daftarOpenBill']);
                Route::get('open-bill/{openBill}', [TransaksiController::class, 'tampilkanOpenBill']);
                Route::put('open-bill/{openBill}', [TransaksiController::class, 'updateOpenBill']);
                Route::post('open-bill/{openBill}/bayar', [TransaksiController::class, 'bayarOpenBill']);
                Route::delete('open-bill/{openBill}', [TransaksiController::class, 'hapusOpenBill']);
                Route::put('open-bill/{openBill}/discount', [DiscountController::class, 'applyToOpenBill'])
                    ->middleware([
                        \App\Http\Middleware\EnsureDiscountPermission::class.':kasir,supervisor,manager,it_support',
                        \App\Http\Middleware\AuditDiscountActivity::class.':OpenBill',
                        'throttle:pos-discount',
                    ]);
            });

            Route::match(['get', 'post'], 'discount/preview', [DiscountController::class, 'preview'])
                ->middleware([
                    \App\Http\Middleware\EnsureDiscountPermission::class.':kasir,supervisor,manager,it_support',
                    \App\Http\Middleware\AuditDiscountActivity::class.':DiscountPreview',
                    'throttle:pos-discount',
                ]);

            Route::prefix('laporan')->group(function () {
                Route::get('shift/{shift}/ringkasan', [LaporanController::class, 'ringkasanShift']);
                Route::get('user/{user}/kinerja', [LaporanController::class, 'kinerjaUser']);
            });
        });

        Route::middleware('role:kasir,it_support')->group(function () {
            Route::prefix('sinkronisasi')->group(function () {
                Route::post('antrian', [SinkronisasiController::class, 'tambahKeAntrian']);
                Route::post('proses', [SinkronisasiController::class, 'prosesAntrian']);
                Route::get('status', [SinkronisasiController::class, 'statusSinkronisasi']);
            });
        });

        Route::middleware('role:kasir,supervisor,manager,it_support')->group(function () {
            Route::prefix('stok')->group(function () {
                Route::get('cabang/{cabang}', [StokController::class, 'byCabang']);
                Route::get('cabang/{cabang}/rendah', [StokController::class, 'rendah']);
                Route::get('cabang/{cabang}/mendekati-kadaluarsa', [StokController::class, 'kadaluarsa']);
                Route::get('mutasi/{stokEtalase}', [StokController::class, 'mutasi']);
            });

            Route::prefix('laporan')->group(function () {
                Route::get('cabang/{cabang}/harian', [LaporanController::class, 'harian']);
                Route::get('cabang/{cabang}/penjualan-produk', [LaporanController::class, 'penjualanProduk']);
                Route::get('cabang/{cabang}/stok', [LaporanController::class, 'stok']);
            });
        });
    });
});

// -----------------------------
// Viewer API (read-only JSON endpoints)
// prefix: /api/viewer/...
//
// These expose POS data (shift, transaksi, stok, laporan), so they require an
// authenticated POS user just like /api/pos/*.
// -----------------------------
Route::middleware(['auth:sanctum', 'role:kasir,supervisor,manager,it_support'])->prefix('viewer')->group(function () {
    Route::prefix('shift')->group(function () {
        Route::get('aktif', [ShiftController::class, 'dapatkanAktif']);
        Route::get('{shift}', [ShiftController::class, 'tampilkan']);
        Route::get('/', [ShiftController::class, 'daftar']);
    });

    Route::prefix('kalibrasi')->group(function () {
        Route::get('shift/{shift}', [KalibrasiController::class, 'dapatkanBerdasarkanShift']);
    });

    Route::prefix('produk')->group(function () {
        Route::get('/', [ProdukController::class, 'daftarProduk']);
        Route::get('{produk}', [ProdukController::class, 'tampilkanProduk']);
        Route::get('tipe/{tipe}', [ProdukController::class, 'produkBerdasarkanTipe']);
        Route::get('{produk}/satuan', [ProdukController::class, 'satuanProduk']);
    });

    Route::prefix('stok')->group(function () {
        Route::get('cabang/{cabang}', [StokController::class, 'byCabang']);
        Route::get('cabang/{cabang}/rendah', [StokController::class, 'rendah']);
        Route::get('cabang/{cabang}/mendekati-kadaluarsa', [StokController::class, 'kadaluarsa']);
        Route::get('mutasi/{stokEtalase}', [StokController::class, 'mutasi']);
    });

    Route::prefix('transaksi')->group(function () {
        Route::get('{transaksi}', [TransaksiController::class, 'tampilkanTransaksi']);
        Route::get('shift/{shift}', [TransaksiController::class, 'transaksiPerShift']);
    });

    Route::prefix('laporan')->group(function () {
        Route::get('shift/{shift}/ringkasan', [LaporanController::class, 'ringkasanShift']);
        Route::get('cabang/{cabang}/harian', [LaporanController::class, 'harian']);
        Route::get('cabang/{cabang}/penjualan-produk', [LaporanController::class, 'penjualanProduk']);
        Route::get('cabang/{cabang}/stok', [LaporanController::class, 'stok']);
        Route::get('user/{user}/kinerja', [LaporanController::class, 'kinerjaUser']);
    });

    Route::prefix('sinkronisasi')->group(function () {
        Route::get('status', [SinkronisasiController::class, 'statusSinkronisasi']);
    });
});
