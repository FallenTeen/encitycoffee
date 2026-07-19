<?php
namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\User;
use App\Services\HierarchyAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        $users = $cabang->users()
            ->select('users.id', 'users.name', 'users.email', 'users.role', 'users.aktif')
            ->orderBy('users.name')
            ->get();

        $available_users = User::where(function ($query) {
                // Only users not in any cabang, or managers who can be in multiple cabangs
                $query->whereNotIn('id', function ($q) {
                    $q->select('user_id')->from('user_cabang');
                })->orWhere('role', 'manager');
            })
            ->whereIn('role', ['kasir', 'manager', 'supervisor', 'admin'])
            ->where('aktif', true)
            ->whereNotIn('id', $users->pluck('id')) // Exclude users already in current cabang
            ->select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();

        $shift_count = $cabang->shift()->count();

        $cabang->setAttribute('status', (bool) $cabang->aktif);

        // Get current manager and supervisor
        $cabangHierarchy = DB::table('cabang_hierarchy')->where('cabang_id', $cabang->id)->first();
        $currentManager = null;
        $currentSupervisor = null;
        
        if ($cabangHierarchy) {
            if ($cabangHierarchy->manager_user_id) {
                $currentManager = User::select('id', 'name', 'email')->find($cabangHierarchy->manager_user_id);
            }
            if ($cabangHierarchy->supervisor_user_id) {
                $currentSupervisor = User::select('id', 'name', 'email')->find($cabangHierarchy->supervisor_user_id);
            }
        }

        // Get available managers and supervisors
        $availableManagers = User::where('role', 'manager')->where('aktif', true)->select('id', 'name', 'email')->get();
        $availableSupervisors = User::where('role', 'supervisor')->where('aktif', true)->select('id', 'name', 'email')->get();

        return Inertia::render('admin/cabang/Edit', [
            'cabang'          => $cabang,
            'users'           => $users,
            'users_count'     => $users->count(),
            'shift_count'     => $shift_count,
            'available_users' => $available_users,
            'current_manager' => $currentManager,
            'current_supervisor' => $currentSupervisor,
            'available_managers' => $availableManagers,
            'available_supervisors' => $availableSupervisors,
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

    public function attachUser(Request $request, Cabang $cabang)
    {
        Gate::authorize('edit-cabang');
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::query()->findOrFail((int) $validated['user_id']);
        $role = strtolower((string) $user->role);

        if ($role === 'it_support') {
            return back()->with('error', 'User it_support tidak boleh ditetapkan ke cabang.');
        }

        try {
            DB::transaction(function () use ($request, $cabang, $user, $role) {
                if ($role === 'admin') {
                    $cabang->users()->syncWithoutDetaching([(int) $user->id]);
                    return;
                }

                $service = app(HierarchyAssignmentService::class);
                $currentCabangIds = $user->cabang()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all();
                $nextCabangIds = $role === 'manager'
                    ? array_values(array_unique(array_merge($currentCabangIds, [(int) $cabang->id])))
                    : [(int) $cabang->id];

                $service->syncRoleAndCabang($request->user(), $user, (string) $user->role, $nextCabangIds);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal menambahkan user ke cabang. Silakan coba lagi.');
        }

        return back()->with('success', 'User berhasil ditambahkan / dipindahkan ke cabang.');
    }

    public function detachUser(Cabang $cabang, User $user)
    {
        Gate::authorize('edit-cabang');
        $role = strtolower((string) $user->role);

        if (! $cabang->users()->where('users.id', (int) $user->id)->exists()) {
            return back()->with('error', 'User tidak terdaftar pada cabang ini.');
        }

        try {
            DB::transaction(function () use ($cabang, $user, $role) {
                if ($role === 'admin') {
                    $cabang->users()->detach((int) $user->id);
                    return;
                }

                if ($role === 'it_support') {
                    $user->cabang()->sync([]);
                    return;
                }

                $service = app(HierarchyAssignmentService::class);
                $currentCabangIds = $user->cabang()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all();
                $remainingCabangIds = array_values(array_diff($currentCabangIds, [(int) $cabang->id]));

                if (empty($remainingCabangIds)) {
                    $service->clearHierarchyForUser($user);
                    $user->cabang()->sync([]);
                    return;
                }

                $service->syncRoleAndCabang(auth()->user(), $user, (string) $user->role, $remainingCabangIds);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal menghapus user dari cabang. Silakan coba lagi.');
        }

        return back()->with('success', 'User berhasil dihapus dari cabang.');
    }

    public function setManager(Request $request, Cabang $cabang)
    {
        Gate::authorize('edit-cabang');
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail((int) $validated['user_id']);
        if (strtolower($user->role) !== 'manager') {
            return back()->with('error', 'User harus berperan sebagai manager');
        }

        try {
            DB::transaction(function () use ($request, $cabang, $user) {
                $service = app(HierarchyAssignmentService::class);
                
                // Ensure the user is assigned to this cabang
                $currentCabangIds = $user->cabang()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all();
                $nextCabangIds = array_values(array_unique(array_merge($currentCabangIds, [(int) $cabang->id])));
                $service->syncRoleAndCabang($request->user(), $user, (string) $user->role, $nextCabangIds);
                
                // Set as manager for this cabang
                $service->syncManagerCabangHierarchy($request->user(), $user, [(int) $cabang->id]);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal menetapkan manager. Silakan coba lagi.');
        }

        return back()->with('success', 'Manager berhasil ditetapkan');
    }

    public function removeManager(Cabang $cabang)
    {
        Gate::authorize('edit-cabang');
        
        try {
            DB::transaction(function () use ($cabang) {
                // Clear manager from cabang hierarchy
                DB::table('cabang_hierarchy')->where('cabang_id', $cabang->id)->update([
                    'manager_user_id' => null,
                    'updated_at' => now()
                ]);
                
                // Update downlines
                $service = app(HierarchyAssignmentService::class);
                $service->syncDownlineForCabang((int) $cabang->id);
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal menghapus manager. Silakan coba lagi.');
        }

        return back()->with('success', 'Manager berhasil dihapus');
    }

    public function setSupervisor(Request $request, Cabang $cabang)
    {
        Gate::authorize('edit-cabang');
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail((int) $validated['user_id']);
        if (strtolower($user->role) !== 'supervisor') {
            return back()->with('error', 'User harus berperan sebagai supervisor');
        }

        try {
            DB::transaction(function () use ($request, $cabang, $user) {
                $service = app(HierarchyAssignmentService::class);
                
                // Ensure the user is assigned to this cabang
                $currentCabangIds = $user->cabang()->pluck('cabang.id')->map(fn ($id) => (int) $id)->all();
                $nextCabangIds = array_values(array_unique(array_merge($currentCabangIds, [(int) $cabang->id])));
                $service->syncRoleAndCabang($request->user(), $user, (string) $user->role, $nextCabangIds);
                
                // Set as supervisor for this cabang
                $service->assignSupervisor($request->user(), $user, (int) $cabang->id);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal menetapkan supervisor. Silakan coba lagi.');
        }

        return back()->with('success', 'Supervisor berhasil ditetapkan');
    }

    public function removeSupervisor(Cabang $cabang)
    {
        Gate::authorize('edit-cabang');
        
        try {
            DB::transaction(function () use ($cabang) {
                // Clear supervisor from cabang hierarchy
                DB::table('cabang_hierarchy')->where('cabang_id', $cabang->id)->update([
                    'supervisor_user_id' => null,
                    'updated_at' => now()
                ]);
                
                // Update downlines
                $service = app(HierarchyAssignmentService::class);
                $service->syncDownlineForCabang((int) $cabang->id);
                
                // Also clear supervisor hierarchy if needed
                $supervisorRow = DB::table('supervisor_hierarchy')->where('cabang_id', $cabang->id)->first();
                if ($supervisorRow) {
                    DB::table('supervisor_hierarchy')->where('supervisor_user_id', (int) $supervisorRow->supervisor_user_id)->delete();
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal menghapus supervisor. Silakan coba lagi.');
        }

        return back()->with('success', 'Supervisor berhasil dihapus');
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
        $users = $cabang->users()
            ->select('users.id', 'users.name', 'users.email', 'users.role', 'users.aktif')
            ->orderBy('users.name')
            ->get();
        $users_count = $users->count();
        $shift_count = $cabang->shift()->count();
        $stok_info = $cabang->stokEtalase()->with('produk')->get();

        $cabang->setAttribute('status', (bool) $cabang->aktif);

        return Inertia::render('admin/cabang/Show', [
            'cabang' => $cabang,
            'users' => $users,
            'users_count' => $users_count,
            'shift_count' => $shift_count,
            'stok_info' => $stok_info,
        ]);
    }
}
