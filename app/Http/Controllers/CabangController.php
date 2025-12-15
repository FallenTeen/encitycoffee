<?php
namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CabangController extends Controller
{
    public function __construct()
    {

    }

    public function index()
    {
        Gate::authorize('view-it-support');
        $cabangs = Cabang::withCount('users', 'shift')->paginate(20);
        $cabangs->getCollection()->transform(function ($cabang) {
            $cabang->setAttribute('status', (bool) $cabang->aktif);
            return $cabang;
        });

        return Inertia::render('admin/cabang/Index', compact('cabangs'));
    }

    public function daftarApi()
    {
    
        $cabangs = Cabang::select(
                'id',
                'kode',
                'nama',
                'alamat',
                'telepon',
                'aktif'
            )
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($cabang) {
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
    
    }

    public function create()
    {
        Gate::authorize('create-cabang');
        return Inertia::render('admin/cabang/Create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create-cabang');
        $validated = $request->validate([
            'kode' => 'required|string|max:20|unique:cabang,kode',
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
            'aktif' => 'sometimes|boolean',
        ]);

        if (!array_key_exists('aktif', $validated)) {
            $validated['aktif'] = true;
        }

        Cabang::create($validated);

        return redirect()->route('admin.cabang.index')->with('success', 'Cabang berhasil dibuat');
    }

    public function edit(Cabang $cabang)
    {
        Gate::authorize('edit-cabang');
        $users_count = $cabang->users()->count();
        $shift_count = $cabang->shift()->count();

        $cabang->setAttribute('status', (bool) $cabang->aktif);

        return Inertia::render('admin/cabang/Edit', [
            'cabang' => $cabang,
            'users_count' => $users_count,
            'shift_count' => $shift_count,
        ]);
    }

    public function update(Request $request, Cabang $cabang)
    {
        Gate::authorize('edit-cabang');
        $validated = $request->validate([
            'kode' => 'required|string|max:20|unique:cabang,kode,' . $cabang->id,
            'nama' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:20',
            'aktif' => 'required|boolean',
        ]);

        if (($validated['aktif'] === false) && ($cabang->aktif === true)) {
            $adaShiftAktif = $cabang->shift()->where('status', 'buka')->exists();
            if ($adaShiftAktif) {
                return back()->withErrors(['aktif' => 'Tidak bisa menonaktifkan cabang dengan shift aktif']);
            }
        }

        $cabang->update($validated);

        return redirect()->route('admin.cabang.index')->with('success', 'Cabang berhasil diupdate');
    }

    public function destroy(Cabang $cabang)
    {
        Gate::authorize('delete-cabang');
        if ($cabang->users()->count() > 0) {
            return back()->withErrors(['cabang' => 'Cabang masih memiliki user']);
        }

        if ($cabang->shift()->count() > 0) {
            return back()->withErrors(['cabang' => 'Cabang masih memiliki data shift']);
        }

        if ($cabang->stokEtalase()->count() > 0) {
            return back()->withErrors(['cabang' => 'Cabang masih memiliki data stok']);
        }

        $cabang->delete();

        return redirect()->route('admin.cabang.index')->with('success', 'Cabang berhasil dihapus');
    }

    public function show(Cabang $cabang)
    {
        Gate::authorize('view-it-support');
        $users_count = $cabang->users()->count();
        $shift_count = $cabang->shift()->count();
        $stok_info = $cabang->stokEtalase()->with('produk')->get();

        $cabang->setAttribute('status', (bool) $cabang->aktif);

        return Inertia::render('admin/cabang/Show', [
            'cabang' => $cabang,
            'users_count' => $users_count,
            'shift_count' => $shift_count,
            'stok_info' => $stok_info,
        ]);
    }
}
