<?php

namespace App\Http\Controllers;

use App\Services\ProductCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Models\Cabang;
use App\Models\Produk;
use App\Models\KategoriProduk;

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
            $cabangId = $this->getCabangId($request, $user);
            
            if (!$cabangId) {
                return Inertia::render('produk/Index', [
                    'produk' => collect(),
                    'cabangList' => $this->getCabangList($user),
                    'selectedCabang' => null,
                    'filters' => ['search' => '', 'cabang_id' => null],
                    'kategoriList' => KategoriProduk::all()
                ]);
            }
            
            $produk = $this->getProdukByCabang($cabangId, $request);
            $selectedCabang = Cabang::find($cabangId);
            
            return Inertia::render('produk/Index', [
                'produk' => $produk,
                'cabangList' => $this->getCabangList($user),
                'selectedCabang' => $selectedCabang,
                'filters' => [
                    'search' => $request->input('search', ''),
                    'cabang_id' => $cabangId,
                    'kategori_id' => $request->input('kategori_id', '')
                ],
                'kategoriList' => KategoriProduk::all(),
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
            $cabangId = $this->getCabangId($request, $user);
            
            if (!$cabangId) {
                return back()->with('error', 'Cabang belum dipilih');
            }
            
            $produk = $this->productCacheService->getProdukById($cabangId, $produkId);
            
            if (!$produk) {
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
            $cabangId = $this->getCabangId($request, $user);
            
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
            
            // Check authorization
            if (!$this->canManageProduk($user)) {
                return back()->with('error', 'Anda tidak memiliki akses untuk menambah produk');
            }
            
            $cabangId = $this->getCabangId($request, $user);
            
            return Inertia::render('produk/Create', [
                'kategoriList' => KategoriProduk::all(),
                'cabangList' => $this->getCabangList($user),
                'selectedCabang' => $cabangId ? Cabang::find($cabangId) : null
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
            
            // Check authorization
            if (!$this->canManageProduk($user)) {
                return back()->with('error', 'Anda tidak memiliki akses untuk menambah produk');
            }
            
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
            ]);
            
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            
            // Validate cabang access for manager/supervisor
            if (in_array($user->role, ['manager', 'supervisor']) && $request->has('stok_etalase')) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                foreach ($request->input('stok_etalase') as $stok) {
                    if (!in_array($stok['cabang_id'], $assignedCabangIds)) {
                        return back()->with('error', 'Anda tidak memiliki akses untuk menambah stok di cabang tersebut');
                    }
                }
            }
            
            $produk = Produk::create($validator->validated());
            
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
            
            Log::info('Produk created successfully', [
                'produk_id' => $produk->id,
                'user_id' => $user->id,
                'sku' => $produk->sku
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
            
            // Check authorization
            if (!$this->canManageProduk($user)) {
                return back()->with('error', 'Anda tidak memiliki akses untuk mengedit produk');
            }
            
            $produk = Produk::with(['stokEtalase', 'kategori'])->findOrFail($id);
            
            // For manager/supervisor, check if they have access to this produk's cabang
            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();
                
                if (!array_intersect($assignedCabangIds, $produkCabangIds)) {
                    return back()->with('error', 'Anda tidak memiliki akses untuk mengedit produk ini');
                }
            }
            
            return Inertia::render('produk/Edit', [
                'produk' => $produk,
                'kategoriList' => KategoriProduk::all(),
                'cabangList' => $this->getCabangList($user)
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
            
            // Check authorization
            if (!$this->canManageProduk($user)) {
                return back()->with('error', 'Anda tidak memiliki akses untuk mengedit produk');
            }
            
            $produk = Produk::with(['stokEtalase'])->findOrFail($id);
            
            // For manager/supervisor, check if they have access to this produk's cabang
            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();
                
                if (!array_intersect($assignedCabangIds, $produkCabangIds)) {
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
            ]);
            
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            
            // Validate cabang access for manager/supervisor
            if (in_array($user->role, ['manager', 'supervisor']) && $request->has('stok_etalase')) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                foreach ($request->input('stok_etalase') as $stok) {
                    if (!in_array($stok['cabang_id'], $assignedCabangIds)) {
                        return back()->with('error', 'Anda tidak memiliki akses untuk mengedit stok di cabang tersebut');
                    }
                }
            }
            
            $produk->update($validator->validated());
            
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
            
            Log::info('Produk updated successfully', [
                'produk_id' => $produk->id,
                'user_id' => $user->id,
                'sku' => $produk->sku
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
            
            // Check authorization
            if (!$this->canManageProduk($user)) {
                return back()->with('error', 'Anda tidak memiliki akses untuk menghapus produk');
            }
            
            $produk = Produk::with(['stokEtalase'])->findOrFail($id);
            
            // For manager/supervisor, check if they have access to this produk's cabang
            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                $produkCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();
                
                if (!array_intersect($assignedCabangIds, $produkCabangIds)) {
                    return back()->with('error', 'Anda tidak memiliki akses untuk menghapus produk ini');
                }
            }
            
            // Get affected cabang IDs before deletion
            $affectedCabangIds = $produk->stokEtalase->pluck('cabang_id')->all();
            
            // Soft delete the produk
            $produk->delete();
            
            // Clear cache for affected cabang
            foreach ($affectedCabangIds as $cabangId) {
                $this->productCacheService->clearCache($cabangId);
            }
            
            Log::info('Produk deleted successfully', [
                'produk_id' => $id,
                'user_id' => $user->id,
                'sku' => $produk->sku
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
            return [
                'id' => $item->id,
                'sku' => $item->sku,
                'nama' => $item->nama,
                'deskripsi' => $item->deskripsi,
                'harga_jual' => $item->harga_jual,
                'tipe' => $item->tipe,
                'kategori_id' => $item->kategori_id,
                'image_path' => $item->image_path,
                'stok_etalase' => $item->stokEtalase->first() ? [
                    'jumlah' => $item->stokEtalase->first()->jumlah,
                    'stok_minimum' => $item->stokEtalase->first()->stok_minimum
                ] : null
            ];
        });
    }
}