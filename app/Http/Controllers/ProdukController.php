<?php

namespace App\Http\Controllers;

use App\Services\ProductCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\KategoriProduk;
use App\Models\SatuanProduk;

class ProdukController extends Controller
{
    protected $productCacheService;

    public function __construct(ProductCacheService $productCacheService)
    {
        $this->productCacheService = $productCacheService;
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $validated = $request->validate([
                'search' => 'nullable|string|max:255',
                'cabang_id' => 'nullable|integer|exists:cabang,id',
                'kategori_id' => 'nullable|string|max:50',
                'tipe' => 'nullable|string|in:beans,minuman,snack,__all__',
                'aktif' => 'nullable|string|in:0,1,__all__',
                'sort_by' => 'nullable|string|in:nama,sku,harga_jual,kategori,stok',
                'sort_dir' => 'nullable|string|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:1000',
                'page' => 'nullable|integer|min:1',
            ]);

            Log::info('=== PRODUK INDEX DEBUG ===', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'assigned_cabang_ids' => $user->cabang->pluck('id')->all(),
                'validated_input' => $validated,
            ]);

            $cabangId = $this->getCabangId($request, $user);

            if (!$cabangId) {
                Log::warning('PRODUK INDEX - No cabang assigned for user', ['user_id' => $user->id]);

                return $this->renderEmptyProductList($user);
            }

            $produk = $this->getProdukByCabang($cabangId, $request);
            $selectedCabang = Cabang::find($cabangId);

            $perPage = $this->getValidatedPerPage($request);
            $page = max(1, (int) $request->input('page', 1));
            $total = $produk->count();

            Log::info('PRODUK INDEX - After Filtering', [
                'total_produk' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
            ]);

            $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

            $page = min($page, $lastPage);

            $offset = ($page - 1) * $perPage;

            if ($perPage >= $total) {
                $items = $produk->values();
            } else {
                $items = $produk->slice($offset, $perPage)->values();
            }

            Log::info('PRODUK INDEX - Pagination Result', [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'offset' => $offset,
                'items_count' => $items->count(),
            ]);

            $produks = [
                'data' => $items->toArray(),
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'from' => $total > 0 ? ($offset + 1) : null,
                'to' => $total > 0 ? min($offset + $items->count(), $total) : null,
                'prev_page_url' => $page > 1 ? route('produk.index', array_merge($request->query(), ['page' => $page - 1])) : null,
                'next_page_url' => $page < $lastPage ? route('produk.index', array_merge($request->query(), ['page' => $page + 1])) : null,
            ];

            return Inertia::render('produk/Index', [
                'produks' => $produks,
                'cabangList' => $this->getCabangList($user),
                'selectedCabang' => $selectedCabang,
                'filter_aktif' => [
                    'search' => $request->input('search', ''),
                    'cabang_id' => $cabangId,
                    'kategori_id' => $request->input('kategori_id', ''),
                    'tipe' => $request->input('tipe', ''),
                    'aktif' => $request->input('aktif', null),
                    'sort_by' => $request->input('sort_by', ''),
                    'sort_dir' => $request->input('sort_dir', ''),
                    'per_page' => $perPage,
                ],
                'kategori_list' => KategoriProduk::select('id', 'nama')->get(),
                'cacheInfo' => $this->productCacheService->getCacheStats($cabangId),
                'canManageProduk' => $this->canManageProduk($user)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching produk', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Gagal mengambil data produk: ' . $e->getMessage());
        }
    }

    private function renderEmptyProductList($user)
    {
        $produks = [
            'data' => [],
            'total' => 0,
            'current_page' => 1,
            'per_page' => 20,
            'last_page' => 1,
            'from' => null,
            'to' => null,
            'prev_page_url' => null,
            'next_page_url' => null,
        ];

        return Inertia::render('produk/Index', [
            'produks' => $produks,
            'cabangList' => $this->getCabangList($user),
            'selectedCabang' => null,
            'filter_aktif' => [
                'search' => '',
                'cabang_id' => null,
                'kategori_id' => null,
                'tipe' => null,
                'aktif' => null,
                'sort_by' => null,
                'sort_dir' => null,
                'per_page' => 20,
            ],
            'kategori_list' => KategoriProduk::select('id', 'nama')->get(),
            'cacheInfo' => null,
            'canManageProduk' => $this->canManageProduk($user),
        ]);
    }

    private function getValidatedPerPage(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        $perPage = is_numeric($perPage) ? (int) $perPage : 20;

        return max(1, min(1000, $perPage));
    }

    public function show(Request $request, $produkId)
    {
        try {
            $user = auth()->user();

            Log::info('=== PRODUK SHOW DEBUG ===', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'produk_id' => $produkId,
            ]);

            $cabangId = $this->getCabangId($request, $user);

            if (!$cabangId) {
                Log::warning('PRODUK SHOW - No cabang assigned', ['user_id' => $user->id]);
                return back()->with('error', 'Cabang belum dipilih');
            }

            $produk = $this->productCacheService->getProdukById($cabangId, $produkId);

            if (!$produk) {
                Log::warning('PRODUK SHOW - Produk not found', [
                    'produk_id' => $produkId,
                    'cabang_id' => $cabangId,
                ]);
                return back()->with('error', 'Produk tidak ditemukan di cabang ini');
            }

            return Inertia::render('produk/Show', [
                'produk' => $produk,
                'selectedCabang' => Cabang::find($cabangId)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching produk detail', [
                'produk_id' => $produkId,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Gagal mengambil detail produk');
        }
    }

    public function clearCache(Request $request)
    {
        try {
            $user = auth()->user();
            $cabangId = $this->getCabangId($request, $user);

            if (!$cabangId) {
                return back()->with('error', 'Cabang belum dipilih');
            }

            $this->productCacheService->clearCache($cabangId);

            Log::info('Product cache cleared', [
                'cabang_id' => $cabangId,
                'user_id' => $user->id
            ]);

            return back()->with('success', 'Cache produk berhasil dibersihkan');
        } catch (\Exception $e) {
            Log::error('Error clearing cache', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal membersihkan cache');
        }
    }

    public function daftarProduk(Request $request)
    {
        try {
            $user = auth()->user();
            $cabangId = $this->getCabangId($request, $user);

            if (!$cabangId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cabang belum ditetapkan'
                ], 400);
            }

            $search = $request->input('search', '');
            $kategoriId = $request->input('kategori_id', '');
            $base = $request->input('base', '');
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $produk = $this->getProdukForApi($cabangId, $search, $kategoriId, '', $base);

            $total = $produk->count();
            $offset = ($page - 1) * $perPage;
            $items = $produk->slice($offset, $perPage)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total),
                ],
                'kategori_list' => KategoriProduk::select('id', 'nama')->get()
            ]);
        } catch (\Exception $e) {
            Log::error('API Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data produk'
            ], 500);
        }
    }

    public function toggleAktif(Request $request, $id)
    {
        try {
            $user = auth()->user();

            if (!$this->canManageProduk($user)) {
                return back()->with('error', 'Anda tidak memiliki akses untuk mengubah status produk');
            }

            $produk = Produk::with(['stokEtalase'])->findOrFail($id);

            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

                if (!array_intersect($assignedCabangIds, $produkCabangIds)) {
                    return back()->with('error', 'Anda tidak memiliki akses untuk mengubah status produk ini');
                }
            }

            $produk->aktif = !$produk->aktif;
            $produk->save();

            $affectedCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();
            foreach (array_unique($affectedCabangIds) as $cabangId) {
                $this->productCacheService->clearCache($cabangId);
            }

            return back()->with('success', 'Status produk berhasil diperbarui');
        } catch (\Exception $e) {
            Log::error('Error toggling produk status', ['error' => $e->getMessage(), 'produk_id' => $id]);
            return back()->with('error', 'Gagal mengubah status produk');
        }
    }

    public function mobileProduk(Request $request)
    {
        try {
            $user = auth()->user();

            Log::info('=== PRODUK INDEX DEBUG ===', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_aktif' => $user->aktif,
                'assigned_cabang_ids' => $user->cabang->pluck('id')->all(),
                'assigned_cabang_names' => $user->cabang->pluck('nama')->all(),
            ]);

            $cabangId = $this->getCabangId($request, $user);

            Log::info('PRODUK INDEX - Cabang Resolution', [
                'request_cabang_id' => $request->input('cabang_id'),
                'resolved_cabang_id' => $cabangId,
            ]);

            if (!$cabangId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cabang tidak tersedia'
                ], 400);
            }

            $search = $request->input('search', '');
            $kategoriId = $request->input('kategori_id', '');
            $tipe = $request->input('tipe', '');
            $base = $request->input('base', '');
            $perPage = $request->input('per_page', 20);

            $produk = $this->getProdukForApi($cabangId, $search, $kategoriId, $tipe, $base);

            $total = $produk->count();
            $produk = $produk->take($perPage)->values();

            return response()->json([
                'success' => true,
                'produk' => $produk,
                'kategori_list' => KategoriProduk::select('id', 'nama')->get(),
                'total' => $total,
                'per_page' => $perPage
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile API Error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data produk'
            ], 500);
        }
    }

    private function getCabangId(Request $request, $user)
    {
        if ($user->role === 'it_support') {
            $cabangId = $request->input('cabang_id');

            if (!$cabangId) {
                $defaultCabang = Cabang::first();
                $cabangId = $defaultCabang ? $defaultCabang->id : null;
            }

            return $cabangId;
        }

        if (in_array($user->role, ['manager', 'supervisor', 'kasir'])) {
            $assignedCabangIds = $user->cabang->pluck('id')->all();

            if (empty($assignedCabangIds)) {
                Log::warning('User has no assigned cabang', ['user_id' => $user->id, 'role' => $user->role]);
                return null;
            }

            $cabangId = $request->input('cabang_id');

            if ($cabangId) {
                if (!in_array((int)$cabangId, $assignedCabangIds)) {
                    Log::warning('Unauthorized cabang access attempt', [
                        'user_id' => $user->id,
                        'role' => $user->role,
                        'requested_cabang_id' => $cabangId,
                        'assigned_cabang_ids' => $assignedCabangIds
                    ]);
                    return null;
                }
                return $cabangId;
            }

            return $assignedCabangIds[0];
        }

        return null;
    }

    private function getCabangList($user)
    {
        if ($user->role === 'it_support') {
            return Cabang::select('id', 'nama', 'kode')->get();
        }

        if (in_array($user->role, ['manager', 'supervisor'])) {
            return $user->cabang->map(function ($cabang) {
                return [
                    'id' => $cabang->id,
                    'nama' => $cabang->nama,
                    'kode' => $cabang->kode,
                ];
            });
        }

        return null;
    }

    private function getProdukByCabang($cabangId, Request $request)
    {
        try {
            $useCache = $request->boolean('use_cache', true);
            $search = trim($request->input('search', ''));
            $kategoriId = $request->input('kategori_id', '');
            $aktif = $request->input('aktif', null);
            $tipe = $request->input('tipe', '');
            $sortBy = $request->input('sort_by', '');
            $sortDir = strtolower($request->input('sort_dir', 'asc'));

            if (!in_array($sortDir, ['asc', 'desc'])) {
                $sortDir = 'asc';
            }

            Log::info('getProdukByCabang - Start', [
                'cabang_id' => $cabangId,
                'search' => $search,
                'kategori_id' => $kategoriId,
                'tipe' => $tipe,
                'aktif' => $aktif,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ]);

            if ($search) {
                $produk = $this->productCacheService->searchProduk($cabangId, $search, $useCache);
            } else {
                $produk = $this->productCacheService->getProdukByCabang($cabangId, $useCache);
            }

            Log::info('getProdukByCabang - After base query', ['count' => $produk->count()]);

            if ($this->isValidFilterValue($kategoriId)) {
                $kategoriIdInt = (int) $kategoriId;
                $produk = $produk->filter(function ($item) use ($kategoriIdInt) {
                    return $item->kategori_id == $kategoriIdInt;
                });
                Log::info('getProdukByCabang - After kategori filter', [
                    'kategori_id' => $kategoriIdInt,
                    'count' => $produk->count(),
                ]);
            }

            if ($this->isValidFilterValue($tipe)) {
                $produk = $produk->filter(function ($item) use ($tipe) {
                    return $item->tipe === $tipe;
                });
                Log::info('getProdukByCabang - After tipe filter', [
                    'tipe' => $tipe,
                    'count' => $produk->count(),
                ]);
            }

            if ($this->isValidFilterValue($aktif)) {
                $isAktif = $this->normalizeAktifValue($aktif);
                $produk = $produk->filter(function ($item) use ($isAktif) {
                    return $item->aktif == $isAktif;
                });
                Log::info('getProdukByCabang - After aktif filter', [
                    'aktif' => $aktif,
                    'is_aktif' => $isAktif,
                    'count' => $produk->count(),
                ]);
            }

            $produk = $this->applySorting($produk, $sortBy, $sortDir);

            Log::info('getProdukByCabang - Final result', ['count' => $produk->count()]);

            return $produk;
        } catch (\Exception $e) {
            Log::error('Error in getProdukByCabang', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cabang_id' => $cabangId,
            ]);

            return collect();
        }
    }

    private function isValidFilterValue($value)
    {
        if ($value === null || $value === '' || $value === '__all__') {
            return false;
        }

        if ($value === '0' || $value === 0) {
            return false;
        }

        return true;
    }

    /**
     * Normalize aktif value to boolean
     */
    private function normalizeAktifValue($aktif)
    {
        // Handle various input formats
        if (is_bool($aktif)) {
            return $aktif;
        }

        if (is_numeric($aktif)) {
            return (int) $aktif === 1;
        }

        if (is_string($aktif)) {
            return in_array(strtolower($aktif), ['1', 'true', 'yes'], true);
        }

        return false;
    }

    /**
     * Apply sorting to product collection
     */
    private function applySorting($produk, $sortBy, $sortDir)
    {
        if (!$sortBy) {
            return $produk->values();
        }

        switch ($sortBy) {
            case 'nama':
            case 'sku':
            case 'harga_jual':
                $produk = $produk->sortBy($sortBy, SORT_REGULAR, $sortDir === 'desc');
                break;

            case 'kategori':
                $produk = $produk->sortBy(function ($item) {
                    return $item->kategori->nama ?? '';
                }, SORT_REGULAR, $sortDir === 'desc');
                break;

            case 'stok':
                $produk = $produk->sortBy(function ($item) {
                    $stokEtalase = $item->stokEtalase->first();
                    return $stokEtalase ? (float) $stokEtalase->jumlah : 0;
                }, SORT_REGULAR, $sortDir === 'desc');
                break;

            default:
                // Invalid sort field, return unsorted
                return $produk->values();
        }

        Log::info('getProdukByCabang - After sorting', [
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
        ]);

        return $produk->values();
    }

    public function create(Request $request)
    {
        try {
            $user = auth()->user();

            Log::info('=== PRODUK CREATE DEBUG ===', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'assigned_cabang_ids' => $user->cabang->pluck('id')->all(),
                'assigned_cabang_names' => $user->cabang->pluck('nama')->all(),
            ]);

            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK CREATE - Unauthorized access attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                ]);
                return back()->with('error', 'Anda tidak memiliki akses untuk menambah produk');
            }

            Log::info('PRODUK CREATE - Authorization passed');

            $cabangId = $this->getCabangId($request, $user);

            $kategoriList = KategoriProduk::select('id', 'nama')->get();
            $satuanOptions = SatuanProduk::select('nama_satuan')->distinct()->pluck('nama_satuan')->values()->all();
            Log::info('PRODUK CREATE - Options loaded', [
                'kategori_count' => $kategoriList->count(),
                'satuan_count' => count($satuanOptions),
                'satuan_list' => $satuanOptions,
                'selected_cabang_id' => $cabangId,
            ]);

            $cabangList = $this->getCabangList($user);
            $selectedCabang = $cabangId ? Cabang::find($cabangId) : null;

            Log::info('PRODUK CREATE - Rendering form', [
                'cabang_list_count' => is_array($cabangList) ? count($cabangList) : 0,
                'selected_cabang_id' => $selectedCabang?->id,
                'selected_cabang_name' => $selectedCabang?->nama,
            ]);

            return Inertia::render('produk/Create', [
                'kategori' => $kategoriList,
                'tipe_options' => ['beans', 'minuman', 'snack'],
                'satuan_options' => $satuanOptions,
                'cabangList' => $cabangList,
                'selectedCabang' => $selectedCabang
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading create produk form', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form tambah produk');
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();

            Log::info('=== PRODUK STORE DEBUG ===', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'request_data_keys' => array_keys($request->all()),
            ]);

            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK STORE - Unauthorized access', ['user_id' => $user->id, 'user_role' => $user->role]);
                return back()->with('error', 'Anda tidak memiliki akses untuk menambah produk');
            }

            Log::info('PRODUK STORE - Authorization passed');

            $validator = Validator::make($request->all(), [
                'sku' => 'required|string|max:50|unique:produk',
                'nama' => 'required|string|max:255',
                'kelompok_nama' => 'nullable|string|max:255',
                'varian' => 'nullable|string|max:50',
                'deskripsi' => 'nullable|string',
                'kategori_id' => 'required|exists:kategori_produk,id',
                'tipe' => 'required|in:beans,minuman,snack',
                'base' => 'nullable|in:coffee,milk,tea,others',
                'satuan_dasar' => 'required|string|max:50',
                'harga_modal' => 'required|numeric|min:0',
                'harga_jual' => 'required|numeric|min:0',
                'aktif' => 'boolean',
                'perlu_kalibrasi' => 'boolean',
                'stok_etalase' => 'nullable|array',
                'stok_etalase.*.cabang_id' => 'required_with:stok_etalase|exists:cabang,id',
                'stok_etalase.*.jumlah' => 'required_with:stok_etalase|numeric|min:0',
                'stok_etalase.*.stok_minimum' => 'required_with:stok_etalase|numeric|min:0',
                'image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                Log::warning('PRODUK STORE - Validation failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);
                return back()->withErrors($validator)->withInput();
            }

            if (in_array($user->role, ['manager', 'supervisor']) && $request->has('stok_etalase')) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                foreach ($request->input('stok_etalase') as $stok) {
                    if (!in_array($stok['cabang_id'], $assignedCabangIds)) {
                        Log::warning('PRODUK STORE - Unauthorized cabang access', ['user_id' => $user->id, 'requested_cabang_id' => $stok['cabang_id']]);
                        return back()->with('error', 'Anda tidak memiliki akses untuk menambah stok di cabang tersebut');
                    }
                }
            }

            $data = $validator->validated();

            if (empty($data['kelompok_nama'])) {
                $data['kelompok_nama'] = $data['nama'];
            }

            if (isset($data['satuan_dasar'])) {
                $data['satuan_dasar'] = strtolower(trim($data['satuan_dasar']));
            }

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('foto-produk', 'public');
                $data['image_path'] = $path;
            }

            $produk = Produk::create($data);

            $affectedCabangIds = [];

            if ($request->has('stok_etalase')) {
                foreach ($request->input('stok_etalase') as $stok) {
                    $produk->stokEtalase()->create($stok);
                    $affectedCabangIds[] = $stok['cabang_id'];
                }
            }

            foreach (array_unique($affectedCabangIds) as $cabangId) {
                $this->productCacheService->clearCache($cabangId);
            }

            $this->productCacheService->incrementCacheVersion();

            Log::info('PRODUK STORE - Success', [
                'produk_id' => $produk->id,
                'user_id' => $user->id,
                'produk_sku' => $produk->sku,
                'produk_nama' => $produk->nama,
                'stok_etalase_created' => $request->has('stok_etalase') ? count($request->input('stok_etalase', [])) : 0,
                'cleared_cache_for_cabang' => $affectedCabangIds,
            ]);

            return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan');
        } catch (\Exception $e) {
            Log::error('Error creating produk', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menambahkan produk')->withInput();
        }
    }

    public function edit(Request $request, $id)
    {
        try {
            $user = auth()->user();

            // DEBUG: Log edit request
            Log::info('=== PRODUK EDIT DEBUG ===', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'produk_id' => $id,
                'assigned_cabang_ids' => $user->cabang->pluck('id')->all(),
                'assigned_cabang_names' => $user->cabang->pluck('nama')->all(),
            ]);

            // Check authorization
            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK EDIT - Unauthorized access attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'produk_id' => $id,
                ]);
                return back()->with('error', 'Anda tidak memiliki akses untuk mengedit produk');
            }

            Log::info('PRODUK EDIT - Authorization passed');

            $produk = Produk::with(['stokEtalase', 'kategori'])->findOrFail($id);

            // DEBUG: Log produk details
            Log::info('PRODUK EDIT - Produk Loaded', [
                'produk_id' => $produk->id,
                'produk_nama' => $produk->nama,
                'produk_sku' => $produk->sku,
                'kategori_id' => $produk->kategori_id,
                'stok_etalase_count' => $produk->stokEtalase->count(),
                'stok_cabang_ids' => $produk->stokEtalase->pluck('cabang_id')->all(),
            ]);

            // For manager/supervisor, check if they have access to this produk's cabang
            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

                // DEBUG: Log access check
                Log::info('PRODUK EDIT - Cabang Access Check', [
                    'user_assigned_cabang' => $assignedCabangIds,
                    'produk_cabang_ids' => $produkCabangIds,
                    'has_intersection' => count(array_intersect($assignedCabangIds, $produkCabangIds)) > 0,
                ]);

                if (!array_intersect($assignedCabangIds, $produkCabangIds)) {
                    Log::warning('PRODUK EDIT - Unauthorized cabang access', [
                        'user_id' => $user->id,
                        'produk_id' => $id,
                        'user_cabang' => $assignedCabangIds,
                        'produk_cabang' => $produkCabangIds,
                    ]);
                    return back()->with('error', 'Anda tidak memiliki akses untuk mengedit produk ini');
                }
            }

            $kategoriList = KategoriProduk::select('id', 'nama')->get();
            $satuanOptions = SatuanProduk::select('nama_satuan')->distinct()->pluck('nama_satuan')->values()->all();
            $stokTersedia = $produk->stokEtalase->map(function ($s) {
                return [
                    'id' => $s->id,
                    'cabang' => $s->cabang ? ['id' => $s->cabang->id, 'nama' => $s->cabang->nama] : null,
                    'jumlah' => $s->jumlah,
                ];
            })->values();

            // DEBUG: Log rendering details
            Log::info('PRODUK EDIT - Rendering form', [
                'kategori_count' => $kategoriList->count(),
                'satuan_count' => count($satuanOptions),
                'stok_tersedia_count' => $stokTersedia->count(),
            ]);

            return Inertia::render('produk/Edit', [
                'produk' => $produk,
                'kategori' => $kategoriList,
                'tipe_options' => ['beans', 'minuman', 'snack'],
                'satuan_options' => $satuanOptions,
                'stok_tersedia' => $stokTersedia
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading edit produk form', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memuat form edit produk');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();

            Log::info('=== PRODUK UPDATE DEBUG ===', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'produk_id' => $id,
                'request_data_keys' => array_keys($request->all()),
            ]);

            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK UPDATE - Unauthorized access', ['user_id' => $user->id, 'user_role' => $user->role, 'produk_id' => $id]);
                return back()->with('error', 'Anda tidak memiliki akses untuk mengedit produk');
            }

            Log::info('PRODUK UPDATE - Authorization passed');

            $produk = Produk::with(['stokEtalase'])->findOrFail($id);

            Log::info('PRODUK UPDATE - Produk Loaded', [
                'produk_id' => $produk->id,
                'produk_nama' => $produk->nama,
                'stok_etalase_count' => $produk->stokEtalase->count(),
                'affected_cabang_ids' => $produk->stokEtalase->pluck('cabang_id')->all(),
            ]);

            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

                Log::info('PRODUK UPDATE - Cabang Access Check', [
                    'user_assigned_cabang' => $assignedCabangIds,
                    'produk_cabang_ids' => $produkCabangIds,
                ]);

                if (!array_intersect($assignedCabangIds, $produkCabangIds)) {
                    Log::warning('PRODUK UPDATE - Unauthorized cabang access', [
                        'user_id' => $user->id,
                        'produk_id' => $id,
                    ]);
                    return back()->with('error', 'Anda tidak memiliki akses untuk mengedit produk ini');
                }
            }

            $validator = Validator::make($request->all(), [
                'sku' => 'required|string|max:50|unique:produk,sku,' . $id,
                'nama' => 'required|string|max:255',
                'kelompok_nama' => 'nullable|string|max:255',
                'varian' => 'nullable|string|max:50',
                'deskripsi' => 'nullable|string',
                'kategori_id' => 'required|exists:kategori_produk,id',
                'tipe' => 'required|in:beans,minuman,snack',
                'base' => 'nullable|in:coffee,milk,tea,others',
                'satuan_dasar' => 'required|string|max:50',
                'harga_modal' => 'required|numeric|min:0',
                'harga_jual' => 'required|numeric|min:0',
                'aktif' => 'boolean',
                'perlu_kalibrasi' => 'boolean',
                'stok_etalase' => 'nullable|array',
                'stok_etalase.*.cabang_id' => 'required_with:stok_etalase|exists:cabang,id',
                'stok_etalase.*.jumlah' => 'required_with:stok_etalase|numeric|min:0',
                'stok_etalase.*.stok_minimum' => 'required_with:stok_etalase|numeric|min:0',
                'image' => 'nullable|image|max:2048',
                'hapus_gambar' => 'boolean',
            ]);

            if ($validator->fails()) {
                Log::warning('PRODUK UPDATE - Validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'produk_id' => $id,
                ]);
                return back()->withErrors($validator)->withInput();
            }

            if (in_array($user->role, ['manager', 'supervisor']) && $request->has('stok_etalase')) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                foreach ($request->input('stok_etalase') as $stok) {
                    if (!in_array($stok['cabang_id'], $assignedCabangIds)) {
                        Log::warning('PRODUK UPDATE - Unauthorized cabang access for stok', ['user_id' => $user->id, 'cabang_id' => $stok['cabang_id']]);
                        return back()->with('error', 'Anda tidak memiliki akses untuk mengedit stok di cabang tersebut');
                    }
                }
            }

            $data = $validator->validated();

            if (empty($data['kelompok_nama'])) {
                $data['kelompok_nama'] = $data['nama'];
            }

            if (isset($data['satuan_dasar'])) {
                $data['satuan_dasar'] = strtolower(trim($data['satuan_dasar']));
            }

            if ($request->boolean('hapus_gambar')) {
                if ($produk->image_path) {
                    Storage::disk('public')->delete($produk->image_path);
                }
                $data['image_path'] = null;
            } elseif ($request->hasFile('image')) {
                if ($produk->image_path) {
                    Storage::disk('public')->delete($produk->image_path);
                }
                $path = $request->file('image')->store('foto-produk', 'public');
                $data['image_path'] = $path;
            }

            $affectedCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

            $produk->update($data);

            if ($request->has('stok_etalase')) {
                $newCabangIds = array_column($request->input('stok_etalase'), 'cabang_id');
                $affectedCabangIds = array_merge($affectedCabangIds, $newCabangIds);

                $produk->stokEtalase()->delete();

                foreach ($request->input('stok_etalase') as $stok) {
                    $produk->stokEtalase()->create($stok);
                }
            }

            foreach (array_unique($affectedCabangIds) as $cabangId) {
                $this->productCacheService->clearCache($cabangId);
            }

            $this->productCacheService->incrementCacheVersion();

            Log::info('PRODUK UPDATE - Success', [
                'produk_id' => $produk->id,
                'user_id' => $user->id,
                'produk_sku' => $produk->sku,
                'affected_cabang_ids' => array_unique($affectedCabangIds),
            ]);

            return redirect()->route('produk.index')->with('success', 'Produk berhasil diupdate');
        } catch (\Exception $e) {
            Log::error('Error updating produk', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal mengupdate produk')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();

            Log::info('=== PRODUK DESTROY DEBUG ===', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'produk_id' => $id,
            ]);

            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK DESTROY - Unauthorized access', ['user_id' => $user->id, 'user_role' => $user->role, 'produk_id' => $id]);
                return back()->with('error', 'Anda tidak memiliki akses untuk menghapus produk');
            }

            Log::info('PRODUK DESTROY - Authorization passed');

            $produk = Produk::with(['stokEtalase'])->findOrFail($id);

            Log::info('PRODUK DESTROY - Produk Details', [
                'produk_id' => $produk->id,
                'produk_nama' => $produk->nama,
                'stok_etalase_count' => $produk->stokEtalase->count(),
                'affected_cabang_ids' => $produk->stokEtalase->pluck('cabang_id')->all(),
            ]);

            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

                Log::info('PRODUK DESTROY - Cabang Access Check', [
                    'user_assigned_cabang' => $assignedCabangIds,
                    'produk_cabang_ids' => $produkCabangIds,
                ]);

                if (!array_intersect($assignedCabangIds, $produkCabangIds)) {
                    Log::warning('PRODUK DESTROY - Unauthorized cabang access', [
                        'user_id' => $user->id,
                        'produk_id' => $id,
                    ]);
                    return back()->with('error', 'Anda tidak memiliki akses untuk menghapus produk ini');
                }
            }

            $affectedCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

            Log::info('PRODUK DESTROY - Deleting', [
                'produk_id' => $produk->id,
                'affected_cabang_ids' => $affectedCabangIds,
            ]);

            $produk->delete();

            foreach ($affectedCabangIds as $cabangId) {
                $this->productCacheService->clearCache($cabangId);
            }

            $this->productCacheService->incrementCacheVersion();

            Log::info('PRODUK DESTROY - Success', [
                'produk_id' => $id,
                'user_id' => $user->id,
                'produk_sku' => $produk->sku,
                'affected_cabang_ids' => $affectedCabangIds,
            ]);

            return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting produk', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menghapus produk');
        }
    }

    private function canManageProduk($user)
    {
        return in_array($user->role, ['it_support', 'manager', 'supervisor']);
    }

    private function getProdukForApi($cabangId, $search = '', $kategoriId = '', $tipe = '', $base = '')
    {
        if ($search) {
            $produk = $this->productCacheService->searchProduk($cabangId, $search);
        } else {
            $produk = $this->productCacheService->getProdukByCabang($cabangId);
        }

        if ($kategoriId) {
            $produk = $produk->where('kategori_id', $kategoriId);
        }

        if ($tipe) {
            $produk = $produk->where('tipe', $tipe);
        }

        if ($base) {
            $produk = $produk->where('base', $base);
        }

        $produk = $produk->where('aktif', true);

        return $produk->map(function ($item) {
            $imagePath = $item->image_path;

            return [
                'id' => $item->id,
                'sku' => $item->sku,
                'nama' => $item->nama,
                'kelompok_nama' => $item->kelompok_nama,
                'varian' => $item->varian,
                'deskripsi' => $item->deskripsi,
                'harga_jual' => $item->harga_jual,
                'tipe' => $item->tipe,
                'base' => $item->base,
                'kategori_id' => $item->kategori_id,
                'aktif' => $item->aktif,
                'image_path' => $imagePath,
                'image_url' => $imagePath ? url('storage/' . ltrim($imagePath, '/')) : null,
                'stok_etalase' => $item->stokEtalase->first() ? [
                    'jumlah' => $item->stokEtalase->first()->jumlah,
                    'stok_minimum' => $item->stokEtalase->first()->stok_minimum
                ] : null
            ];
        });
    }

    public function checkSku(Request $request)
    {
        try {
            $sku = $request->input('sku');
            $excludeId = $request->input('exclude_id');

            if (!$sku) {
                return response()->json(['exists' => false]);
            }

            $query = Produk::where('sku', $sku);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $exists = $query->exists();

            return response()->json(['exists' => $exists]);
        } catch (\Exception $e) {
            Log::error('Error checking SKU', ['error' => $e->getMessage()]);
            return response()->json(['exists' => false], 500);
        }
    }
    public function suggestSku(Request $request)
    {
        try {
            $tipe = $request->input('tipe');
            $nama = $request->input('nama', '');
            $varian = $request->input('varian', '');
            $kategoriId = $request->input('kategori_id');

            // Get prefix based on tipe
            $prefix = match ($tipe) {
                'beans' => 'BNS',
                'minuman' => 'BEV',
                'snack' => 'SNK',
                default => 'PRD'
            };

            // Get label from nama (first 3 letters or abbreviation)
            $label = '';
            if ($nama) {
                // Remove common words and get abbreviation
                $cleanName = preg_replace('/\b(kopi|coffee|teh|tea|susu|milk)\b/i', '', $nama);
                $cleanName = trim($cleanName);

                // Create abbreviation from words
                $words = preg_split('/[\s\-_]+/', $cleanName);
                if (count($words) > 1) {
                    // Multi-word: take first letter of each word
                    $label = strtoupper(substr(implode('', array_map(fn($w) => substr($w, 0, 1), $words)), 0, 3));
                } else {
                    // Single word: take first 3 letters
                    $label = strtoupper(substr($cleanName, 0, 3));
                }
            }

            // Add varian if exists
            $varianPart = '';
            if ($varian) {
                $varianPart = '-' . strtoupper(substr($varian, 0, 3));
            }

            // Find next available number
            $basePattern = $prefix . ($label ? "-{$label}" : '') . $varianPart;

            // Get the highest existing number for this pattern
            $lastProduct = Produk::where('sku', 'like', $basePattern . '-%')
                ->orderByRaw('CAST(SUBSTRING_INDEX(sku, "-", -1) AS UNSIGNED) DESC')
                ->first();

            $nextNumber = 1;
            if ($lastProduct) {
                $lastSku = $lastProduct->sku;
                $parts = explode('-', $lastSku);
                $lastNumber = (int) end($parts);
                $nextNumber = $lastNumber + 1;
            }

            $sku = $basePattern . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            return response()->json(['sku' => $sku]);
        } catch (\Exception $e) {
            Log::error('Error suggesting SKU', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to generate SKU'], 500);
        }
    }

    public function getKelompokNama(Request $request)
    {
        try {
            // Get unique kelompok_nama values
            $kelompokList = Produk::select('kelompok_nama')
                ->whereNotNull('kelompok_nama')
                ->distinct()
                ->orderBy('kelompok_nama')
                ->pluck('kelompok_nama')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'data' => $kelompokList
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching kelompok nama', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kelompok nama'
            ], 500);
        }
    }
}
