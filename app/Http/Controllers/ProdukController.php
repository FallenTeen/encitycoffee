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

            // DEBUG: Log user and role info
            Log::info('=== PRODUK INDEX DEBUG ===', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_aktif' => $user->aktif,
                'assigned_cabang_ids' => $user->cabang->pluck('id')->all(),
                'assigned_cabang_names' => $user->cabang->pluck('nama')->all(),
            ]);

            $cabangId = $this->getCabangId($request, $user);

            // DEBUG: Log cabang resolution
            Log::info('PRODUK INDEX - Cabang Resolution', [
                'request_cabang_id' => $request->input('cabang_id'),
                'resolved_cabang_id' => $cabangId,
            ]);

            if (!$cabangId) {
                Log::warning('PRODUK INDEX - No cabang assigned for user', ['user_id' => $user->id]);
                // No cabang assigned / selected — return empty paginator and required props for frontend
                Log::info('PRODUK INDEX - Returning empty data (no cabang)', [
                    'user_id' => $user->id,
                    'reason' => 'No cabang assigned or selected',
                ]);

                $produks = [
                    'data' => collect()->toArray(),
                    'total' => 0,
                    'current_page' => 1,
                    'per_page' => 20,
                    'last_page' => 1,
                    'from' => null,
                    'to' => null,
                ];

                return Inertia::render('produk/Index', [
                    'produks' => $produks,
                    'cabangList' => $this->getCabangList($user),
                    'selectedCabang' => null,
                    'filter_aktif' => ['pencarian' => '', 'cabang_id' => null, 'kategori_id' => null, 'tipe' => null, 'aktif' => null],
                    'kategori_list' => KategoriProduk::select('id','nama')->get(),
                    'cacheInfo' => null,
                    'canManageProduk' => $this->canManageProduk($user),
                ]);
            }

            $produk = $this->getProdukByCabang($cabangId, $request);
            $selectedCabang = Cabang::find($cabangId);

            // DEBUG: Log filters and results
            Log::info('PRODUK INDEX - Filters Applied', [
                'cabang_id' => $cabangId,
                'cabang_name' => $selectedCabang?->nama,
                'search' => $request->input('search', ''),
                'kategori_id' => $request->input('kategori_id', ''),
                'tipe' => $request->input('tipe', ''),
                'use_cache' => $request->boolean('use_cache', true),
            ]);

            // Build simple paginator for the frontend
            $perPage = $request->input('per_page', 20);
            $page = max(1, (int) $request->input('page', 1));
            $total = $produk->count();
            $offset = ($page - 1) * $perPage;
            $items = $produk->slice($offset, $perPage)->values();

            // DEBUG: Log pagination and results
            Log::info('PRODUK INDEX - Results', [
                'total_produk' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'items_returned' => $items->count(),
                'first_produk_id' => $items->first()?->id,
                'last_produk_id' => $items->last()?->id,
            ]);

            $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
            $produks = [
                'data' => $items,
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => $total > 0 ? min($offset + $perPage, $total) : null,
                'prev_page_url' => $page > 1 ? route('produk.index', array_merge($request->query(), ['page' => $page - 1])) : null,
                'next_page_url' => $page < $lastPage ? route('produk.index', array_merge($request->query(), ['page' => $page + 1])) : null,
            ];

            // DEBUG: Log what will be sent to frontend
            Log::info('PRODUK INDEX - Sending to Frontend', [
                'produks_structure' => [
                    'data_count' => count($produks['data']),
                    'total' => $produks['total'],
                    'current_page' => $produks['current_page'],
                    'last_page' => $produks['last_page'],
                ],
                'kategori_list_count' => KategoriProduk::select('id','nama')->get()->count(),
                'selected_cabang_id' => $selectedCabang?->id,
                'can_manage' => $this->canManageProduk($user),
            ]);

            return Inertia::render('produk/Index', [
                'produks' => $produks,
                'cabangList' => $this->getCabangList($user),
                'selectedCabang' => $selectedCabang,
                'filter_aktif' => [
                    'pencarian' => $request->input('search', ''),
                    'cabang_id' => $cabangId,
                    'kategori_id' => $request->input('kategori_id', ''),
                    'tipe' => $request->input('tipe', ''),
                    'aktif' => $request->input('aktif', null),
                ],
                'kategori_list' => KategoriProduk::select('id','nama')->get(),
                'cacheInfo' => $this->productCacheService->getCacheStats($cabangId),
                'canManageProduk' => $this->canManageProduk($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching produk', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Gagal mengambil data produk');
        }
    }

    public function show(Request $request, $produkId)
    {
        try {
            $user = auth()->user();

            // DEBUG: Log show request
            Log::info('=== PRODUK SHOW DEBUG ===', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'produk_id' => $produkId,
                'assigned_cabang_ids' => $user->cabang->pluck('id')->all(),
            ]);

            $cabangId = $this->getCabangId($request, $user);

            Log::info('PRODUK SHOW - Cabang Info', [
                'resolved_cabang_id' => $cabangId,
            ]);

            if (!$cabangId) {
                Log::warning('PRODUK SHOW - No cabang assigned', ['user_id' => $user->id]);
                return back()->with('error', 'Cabang belum dipilih');
            }

            $produk = $this->productCacheService->getProdukById($cabangId, $produkId);

            // DEBUG: Log produk retrieval
            Log::info('PRODUK SHOW - Produk Retrieved', [
                'produk_id' => $produkId,
                'found' => $produk ? true : false,
                'cabang_id' => $cabangId,
                'produk_name' => $produk?->nama,
            ]);

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
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $produk = $this->getProdukForApi($cabangId, $search, $kategoriId);

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

    public function mobileProduk(Request $request)
    {
        try {
            $user = auth()->user();

            // DEBUG: Log user and role info
            Log::info('=== PRODUK INDEX DEBUG ===', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_aktif' => $user->aktif,
                'assigned_cabang_ids' => $user->cabang->pluck('id')->all(),
                'assigned_cabang_names' => $user->cabang->pluck('nama')->all(),
            ]);

            $cabangId = $this->getCabangId($request, $user);

            // DEBUG: Log cabang resolution
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
            $perPage = $request->input('per_page', 20);

            $produk = $this->getProdukForApi($cabangId, $search, $kategoriId, $tipe);

            // Apply pagination
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
            // it_support can access all cabang
            $cabangId = $request->input('cabang_id');

            if (!$cabangId) {
                $defaultCabang = Cabang::first();
                $cabangId = $defaultCabang ? $defaultCabang->id : null;
            }

            return $cabangId;
        }

        if (in_array($user->role, ['manager', 'supervisor'])) {
            // manager/supervisor can only access their assigned cabang
            $cabangId = $request->input('cabang_id');

            // Get assigned cabang IDs for this user
            $assignedCabangIds = $user->cabang->pluck('id')->all();

            if (empty($assignedCabangIds)) {
                Log::warning('User has no assigned cabang', ['user_id' => $user->id, 'role' => $user->role]);
                return null;
            }

            if ($cabangId) {
                // Verify the requested cabang is in their assigned cabang list
                if (!in_array((int)$cabangId, $assignedCabangIds)) {
                    Log::warning('Unauthorized cabang access attempt', [
                        'user_id' => $user->id,
                        'role' => $user->role,
                        'requested_cabang_id' => $cabangId,
                        'assigned_cabang_ids' => $assignedCabangIds
                    ]);
                    return null; // Return null to indicate unauthorized access
                }
                return $cabangId;
            }

            // If no cabang_id specified, use the first assigned cabang
            return $assignedCabangIds[0];
        }

        // For kasir role, check if they have cabang assignment
        if ($user->role === 'kasir') {
            $assignedCabangIds = $user->cabang->pluck('id')->all();
            if (empty($assignedCabangIds)) {
                Log::warning('Kasir has no assigned cabang', ['user_id' => $user->id]);
                return null;
            }

            $cabangId = $request->input('cabang_id');
            if ($cabangId) {
                if (!in_array((int)$cabangId, $assignedCabangIds)) {
                    Log::warning('Kasir trying to access unauthorized cabang', [
                        'user_id' => $user->id,
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
            // it_support can see all cabang
            return Cabang::select('id', 'nama', 'kode')->get();
        }

        if (in_array($user->role, ['manager', 'supervisor'])) {
            // manager/supervisor can only see their assigned cabang
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
        $useCache = $request->boolean('use_cache', true);
        $search = $request->input('search', '');
        $kategoriId = $request->input('kategori_id', '');

        if ($search) {
            $produk = $this->productCacheService->searchProduk($cabangId, $search, $useCache);
        } else {
            $produk = $this->productCacheService->getProdukByCabang($cabangId, $useCache);
        }

        if ($kategoriId) {
            $produk = $produk->where('kategori_id', $kategoriId);
        }

        return $produk;
    }

    public function create(Request $request)
    {
        try {
            $user = auth()->user();

            // DEBUG: Log create request
            Log::info('=== PRODUK CREATE DEBUG ===', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'assigned_cabang_ids' => $user->cabang->pluck('id')->all(),
                'assigned_cabang_names' => $user->cabang->pluck('nama')->all(),
            ]);

            // Check authorization
            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK CREATE - Unauthorized access attempt', [
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                ]);
                return back()->with('error', 'Anda tidak memiliki akses untuk menambah produk');
            }

            Log::info('PRODUK CREATE - Authorization passed');

            $cabangId = $this->getCabangId($request, $user);

            // DEBUG: Log kategori and satuan options
            $kategoriList = KategoriProduk::select('id','nama')->get();
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
                'tipe_options' => ['beans','minuman','snack'],
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

            // DEBUG: Log store request
            Log::info('=== PRODUK STORE DEBUG ===', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'request_data_keys' => array_keys($request->all()),
            ]);

            // Check authorization
            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK STORE - Unauthorized access', ['user_id' => $user->id, 'user_role' => $user->role]);
                return back()->with('error', 'Anda tidak memiliki akses untuk menambah produk');
            }

            Log::info('PRODUK STORE - Authorization passed');

            $validator = Validator::make($request->all(), [
                'sku' => 'required|string|max:50|unique:produk',
                'nama' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'kategori_id' => 'required|exists:kategori_produk,id',
                'tipe' => 'required|in:beans,minuman,snack',
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

            // Validate cabang access for manager/supervisor
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

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('foto-produk', 'public');
                $data['image_path'] = $path;
            }

            $produk = Produk::create($data);

            // Create stok etalase if provided
            if ($request->has('stok_etalase')) {
                foreach ($request->input('stok_etalase') as $stok) {
                    $produk->stokEtalase()->create($stok);
                }
            }

            // Clear cache for affected cabang
            if ($request->has('stok_etalase')) {
                foreach ($request->input('stok_etalase') as $stok) {
                    $this->productCacheService->clearCache($stok['cabang_id']);
                }
            }

            Log::info('PRODUK STORE - Success', [
                'produk_id' => $produk->id,
                'user_id' => $user->id,
                'produk_sku' => $produk->sku,
                'produk_nama' => $produk->nama,
                'stok_etalase_created' => $request->has('stok_etalase') ? count($request->input('stok_etalase', [])) : 0,
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

            $kategoriList = KategoriProduk::select('id','nama')->get();
            $satuanOptions = SatuanProduk::select('nama_satuan')->distinct()->pluck('nama_satuan')->values()->all();
            $stokTersedia = $produk->stokEtalase->map(function($s) {
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
                'tipe_options' => ['beans','minuman','snack'],
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

            // DEBUG: Log update request
            Log::info('=== PRODUK UPDATE DEBUG ===', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'produk_id' => $id,
                'request_data_keys' => array_keys($request->all()),
            ]);

            // Check authorization
            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK UPDATE - Unauthorized access', ['user_id' => $user->id, 'user_role' => $user->role, 'produk_id' => $id]);
                return back()->with('error', 'Anda tidak memiliki akses untuk mengedit produk');
            }

            Log::info('PRODUK UPDATE - Authorization passed');

            $produk = Produk::with(['stokEtalase'])->findOrFail($id);

            // DEBUG: Log produk details
            Log::info('PRODUK UPDATE - Produk Loaded', [
                'produk_id' => $produk->id,
                'produk_nama' => $produk->nama,
                'stok_etalase_count' => $produk->stokEtalase->count(),
                'affected_cabang_ids' => $produk->stokEtalase->pluck('cabang_id')->all(),
            ]);

            // For manager/supervisor, check if they have access to this produk's cabang
            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

                // DEBUG: Log access check
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
                'deskripsi' => 'nullable|string',
                'kategori_id' => 'required|exists:kategori_produk,id',
                'tipe' => 'required|in:beans,minuman,snack',
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

            // Validate cabang access for manager/supervisor
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

            $produk->update($data);

            // Update stok etalase
            if ($request->has('stok_etalase')) {
                // Get affected cabang IDs before update
                $affectedCabangIds = array_merge(
                    $produk->stokEtalase->pluck('cabang_id')->all(),
                    array_column($request->input('stok_etalase'), 'cabang_id')
                );

                // Delete existing stok etalase
                $produk->stokEtalase()->delete();

                // Create new stok etalase
                foreach ($request->input('stok_etalase') as $stok) {
                    $produk->stokEtalase()->create($stok);
                }

                // Clear cache for affected cabang
                foreach (array_unique($affectedCabangIds) as $cabangId) {
                    $this->productCacheService->clearCache($cabangId);
                }
            }

            Log::info('PRODUK UPDATE - Success', [
                'produk_id' => $produk->id,
                'user_id' => $user->id,
                'produk_sku' => $produk->sku,
                'affected_cabang_ids' => $affectedCabangIds,
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

            // DEBUG: Log destroy request
            Log::info('=== PRODUK DESTROY DEBUG ===', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'produk_id' => $id,
            ]);

            // Check authorization
            if (!$this->canManageProduk($user)) {
                Log::warning('PRODUK DESTROY - Unauthorized access', ['user_id' => $user->id, 'user_role' => $user->role, 'produk_id' => $id]);
                return back()->with('error', 'Anda tidak memiliki akses untuk menghapus produk');
            }

            Log::info('PRODUK DESTROY - Authorization passed');

            $produk = Produk::with(['stokEtalase'])->findOrFail($id);

            // DEBUG: Log produk details
            Log::info('PRODUK DESTROY - Produk Details', [
                'produk_id' => $produk->id,
                'produk_nama' => $produk->nama,
                'stok_etalase_count' => $produk->stokEtalase->count(),
                'affected_cabang_ids' => $produk->stokEtalase->pluck('cabang_id')->all(),
            ]);

            // For manager/supervisor, check if they have access to this produk's cabang
            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

                // DEBUG: Log access check
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

            // Get affected cabang IDs before deletion
            $affectedCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();

            Log::info('PRODUK DESTROY - Deleting', [
                'produk_id' => $produk->id,
                'affected_cabang_ids' => $affectedCabangIds,
            ]);

            // Soft delete the produk
            $produk->delete();

            // Clear cache for affected cabang
            foreach ($affectedCabangIds as $cabangId) {
                $this->productCacheService->clearCache($cabangId);
            }

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

    private function getProdukForApi($cabangId, $search = '', $kategoriId = '', $tipe = '')
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

        return $produk->map(function ($item) {
            $imagePath = $item->image_path;

            return [
                'id' => $item->id,
                'sku' => $item->sku,
                'nama' => $item->nama,
                'deskripsi' => $item->deskripsi,
                'harga_jual' => $item->harga_jual,
                'tipe' => $item->tipe,
                'kategori_id' => $item->kategori_id,
                'image_path' => $imagePath,
                'image_url' => $imagePath ? url('storage/' . ltrim($imagePath, '/')) : null,
                'stok_etalase' => $item->stokEtalase->first() ? [
                    'jumlah' => $item->stokEtalase->first()->jumlah,
                    'stok_minimum' => $item->stokEtalase->first()->stok_minimum
                ] : null
            ];
        });
    }
}
