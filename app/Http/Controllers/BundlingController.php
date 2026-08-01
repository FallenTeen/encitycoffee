<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Cabang;
use App\Models\KategoriProduk;
use App\Models\BundleItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BundlingController extends Controller
{
    public function __construct()
    {
        // No auth middleware here if handled by routes, but safe to add
    }

    private function canManageProduk($user)
    {
        return in_array($user->role, ['it_support', 'manager', 'supervisor']);
    }

    private function canDeleteProduk($user)
    {
        return in_array($user->role, ['it_support', 'manager']);
    }

    private function getCabangList($user)
    {
        if ($user->role === 'it_support') {
            return Cabang::all();
        }
        $user->load('cabang');
        return collect($user->cabang);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Produk::with(['kategori', 'bundleItems.produk'])->where('tipe', 'bundling');

        if ($user->role !== 'it_support') {
            $user->load('cabang:id');
            $cabangIds = $user->cabang->pluck('id')->all();
            if (empty($cabangIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('cabang_id', $cabangIds);
            }
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->whereRaw('LOWER(nama) LIKE ?', ["%{$search}%"]);
        }

        $items = $query->orderBy('nama')->paginate(20);

        return Inertia::render('produk/bundling/Index', [
            'produks' => $items,
            'canManageProduk' => $this->canManageProduk($user),
            'canDeleteProduk' => $this->canDeleteProduk($user),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        if (!$this->canManageProduk($user)) {
            return redirect()->route('produk.index')->with('error', 'Anda tidak memiliki akses');
        }

        $allProducts = Produk::where('tipe', '!=', 'bundling')
                             ->where('aktif', true)
                             ->orderBy('nama')
                             ->get(['id', 'nama', 'harga_jual', 'sku']);

        return Inertia::render('produk/bundling/Create', [
            'cabangList' => $this->getCabangList($user),
            'kategori_list' => KategoriProduk::select('id', 'nama')->get(),
            'available_products' => $allProducts,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$this->canManageProduk($user)) {
            return back()->with('error', 'Anda tidak memiliki akses');
        }

        $validator = Validator::make($request->all(), [
            'sku' => 'required|string|max:50|unique:produk',
            'nama' => 'required|string|max:255',
            'kategori_id' => 'nullable|exists:kategori_produk,id',
            'cabang_id' => 'nullable|exists:cabang,id',
            'harga_jual' => 'required|numeric|min:0',
            'aktif' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'bundle_items' => 'required|array|min:1',
            'bundle_items.*.produk_id' => 'required|exists:produk,id',
            'bundle_items.*.jumlah' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $data = $request->except(['image', 'bundle_items']);
            $data['tipe'] = 'bundling';
            $data['satuan_dasar'] = 'paket';
            $data['harga_modal'] = 0; // Or calculated later

            if (empty($data['kategori_id'])) {
                $kategoriBundling = KategoriProduk::firstOrCreate(
                    ['nama' => 'Bundling'],
                    ['slug' => 'bundling']
                );
                $data['kategori_id'] = $kategoriBundling->id;
            }

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('produk', 'public');
                $data['image_path'] = '/' . $path;
            }

            $produk = Produk::create($data);

            foreach ($request->bundle_items as $item) {
                BundleItem::create([
                    'bundle_id' => $produk->id,
                    'produk_id' => $item['produk_id'],
                    'jumlah' => $item['jumlah'],
                ]);
            }

            DB::commit();
            return redirect()->route('produk.index')->with('success', 'Bundling berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bundling: ' . $e->getMessage());
            return back()->with('error', 'Gagal membuat bundling')->withInput();
        }
    }

    public function edit(Produk $bundling)
    {
        $user = auth()->user();
        if (!$this->canManageProduk($user)) {
            return redirect()->route('produk.index')->with('error', 'Anda tidak memiliki akses');
        }

        $bundling->load('bundleItems.produk');

        $allProducts = Produk::where('tipe', '!=', 'bundling')
                             ->where('aktif', true)
                             ->orderBy('nama')
                             ->get(['id', 'nama', 'harga_jual', 'sku']);

        return Inertia::render('produk/bundling/Edit', [
            'produk' => $bundling,
            'cabangList' => $this->getCabangList($user),
            'kategori_list' => KategoriProduk::select('id', 'nama')->get(),
            'available_products' => $allProducts,
        ]);
    }

    public function update(Request $request, Produk $bundling)
    {
        $user = auth()->user();
        if (!$this->canManageProduk($user)) {
            return back()->with('error', 'Anda tidak memiliki akses');
        }

        $validator = Validator::make($request->all(), [
            'sku' => 'required|string|max:50|unique:produk,sku,' . $bundling->id,
            'nama' => 'required|string|max:255',
            'kategori_id' => 'nullable|exists:kategori_produk,id',
            'cabang_id' => 'nullable|exists:cabang,id',
            'harga_jual' => 'required|numeric|min:0',
            'aktif' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'bundle_items' => 'required|array|min:1',
            'bundle_items.*.produk_id' => 'required|exists:produk,id',
            'bundle_items.*.jumlah' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $data = $request->except(['image', 'bundle_items', '_method']);

            if (empty($data['kategori_id'])) {
                $kategoriBundling = KategoriProduk::firstOrCreate(
                    ['nama' => 'Bundling'],
                    ['slug' => 'bundling']
                );
                $data['kategori_id'] = $kategoriBundling->id;
            }

            if ($request->hasFile('image')) {
                if ($bundling->image_path) {
                    $oldPath = ltrim($bundling->image_path, '/');
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                $path = $request->file('image')->store('produk', 'public');
                $data['image_path'] = '/' . $path;
            } elseif ($request->boolean('remove_image')) {
                if ($bundling->image_path) {
                    $oldPath = ltrim($bundling->image_path, '/');
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                $data['image_path'] = null;
            }

            $bundling->update($data);

            // Sync bundle items
            $bundling->bundleItems()->delete();
            foreach ($request->bundle_items as $item) {
                BundleItem::create([
                    'bundle_id' => $bundling->id,
                    'produk_id' => $item['produk_id'],
                    'jumlah' => $item['jumlah'],
                ]);
            }

            DB::commit();
            return redirect()->route('produk.index')->with('success', 'Bundling berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating bundling: ' . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui bundling')->withInput();
        }
    }

    public function destroy(Produk $bundling)
    {
        $user = auth()->user();
        if (!$this->canDeleteProduk($user)) {
            return back()->with('error', 'Anda tidak memiliki akses');
        }

        try {
            if ($bundling->image_path) {
                $oldPath = ltrim($bundling->image_path, '/');
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $bundling->delete();
            return redirect()->route('produk.index')->with('success', 'Bundling berhasil dihapus');
        } catch (\Exception $e) {
            Log::error('Error deleting bundling: ' . $e->getMessage());
            return back()->with('error', 'Gagal menghapus bundling');
        }
    }
}
