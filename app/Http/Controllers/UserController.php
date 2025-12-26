<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\HierarchyAssignmentService;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct()
    {

    }

    public function index(Request $request)
    {
        Gate::authorize('manage-kasir');

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(['it_support', 'manager', 'supervisor', 'kasir'])],
            'aktif' => ['nullable', Rule::in(['1', '0'])],
            'cabang_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();
        $query = User::query()->with('cabang');
        
        // Filter based on user role
        if ($user->role === 'it_support') {
            // it_support can see all users
        } elseif (in_array($user->role, ['manager', 'supervisor'])) {
            // manager/supervisor can only see kasir users in their assigned cabang
            $query->where('role', 'kasir');
            $assignedCabangIds = $user->cabang->pluck('id')->all();
            $query->whereHas('cabang', fn ($q) => $q->whereIn('cabang.id', $assignedCabangIds));
        }
        
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if (! empty($validated['role'])) {
            // Only apply role filter if it doesn't conflict with role-based restrictions
            if ($user->role !== 'it_support' && $validated['role'] !== 'kasir') {
                // manager/supervisor trying to filter for non-kasir roles - show no results
                $query->whereRaw('1 = 0');
            } else {
                $query->where('role', $validated['role']);
            }
        }
        if (array_key_exists('aktif', $validated) && $validated['aktif'] !== null) {
            $query->where('aktif', $validated['aktif'] === '1');
        }
        if (! empty($validated['cabang_id'])) {
            $cabangId = (int) $validated['cabang_id'];
            // Verify the requested cabang is in their assigned cabang list (for manager/supervisor)
            if (in_array($user->role, ['manager', 'supervisor'])) {
                $assignedCabangIds = $user->cabang->pluck('id')->all();
                if (!in_array($cabangId, $assignedCabangIds)) {
                    // Trying to filter by unauthorized cabang - show no results
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereHas('cabang', fn ($q) => $q->where('cabang.id', $cabangId));
                }
            } else {
                $query->whereHas('cabang', fn ($q) => $q->where('cabang.id', $cabangId));
            }
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $users = $query->orderBy('name')->paginate($perPage)->withQueryString();
        
        // Filter cabang list based on user role
        if (in_array($user->role, ['manager', 'supervisor'])) {
            $cabangs = $user->cabang->sortBy('kode');
        } else {
            $cabangs = Cabang::orderBy('kode')->get(['id', 'kode', 'nama']);
        }

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'cabangs' => $cabangs,
            'filter_aktif' => [
                'search' => $validated['search'] ?? '',
                'role' => $validated['role'] ?? '',
                'aktif' => $validated['aktif'] ?? '',
                'cabang_id' => $validated['cabang_id'] ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        Gate::authorize('create-user');
        
        $user = auth()->user();
        
        // Filter cabang list based on user role
        if (in_array($user->role, ['manager', 'supervisor'])) {
            $cabangs = $user->cabang;
        } else {
            $cabangs = Cabang::all();
        }
        
        return Inertia::render('admin/users/Create', compact('cabangs'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create-user');
        
        $user = $request->user();
        
        // Role-based validation for manager/supervisor
        if (in_array($user->role, ['manager', 'supervisor'])) {
            // manager/supervisor can only create kasir users
            $roleValidation = ['required', Rule::in(['kasir'])];
        } else {
            // it_support can create any role
            $roleValidation = ['required', Rule::in(['it_support', 'manager', 'supervisor', 'kasir'])];
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'role' => $roleValidation,
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
            'aktif' => 'required|boolean',
        ]);

        $cabangIds = array_map('intval', $validated['cabang_ids'] ?? []);
        
        // Validate cabang_ids for manager/supervisor
        if (in_array($user->role, ['manager', 'supervisor']) && !empty($cabangIds)) {
            $assignedCabangIds = $user->cabang->pluck('id')->all();
            foreach ($cabangIds as $cabangId) {
                if (!in_array($cabangId, $assignedCabangIds)) {
                    return back()->withErrors(['cabang_ids' => 'Tidak berhak menambah user untuk cabang ini'])->withInput();
                }
            }
        }
        
        unset($validated['cabang_ids']);

        $validated['password'] = Hash::make($validated['password']);
        $newUser = User::create($validated);

        app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $newUser, (string) $newUser->role, $cabangIds);

        Log::info('User created', ['actor_user_id' => (int) $request->user()->id, 'user_id' => (int) $newUser->id, 'role' => (string) $newUser->role]);
        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        Gate::authorize('edit-user');
        
        $currentUser = auth()->user();
        
        // Role-based restrictions for manager/supervisor
        if (in_array($currentUser->role, ['manager', 'supervisor'])) {
            // manager/supervisor can only edit kasir users
            if ($user->role !== 'kasir') {
                abort(403, 'Tidak berhak mengedit user ini');
            }
            
            // Verify the user is in their assigned cabang
            $assignedCabangIds = $currentUser->cabang->pluck('id')->all();
            $userCabangIds = $user->cabang->pluck('id')->all();
            if (empty(array_intersect($assignedCabangIds, $userCabangIds))) {
                abort(403, 'Tidak berhak mengedit user dari cabang ini');
            }
            
            // Filter cabang list to only show assigned cabang
            $cabangs = $currentUser->cabang;
        } else {
            $cabangs = Cabang::orderBy('kode')->get(['id', 'kode', 'nama']);
        }
        
        $user->load('cabang:id,kode,nama');
        return Inertia::render('admin/users/Edit', [
            'user' => $user,
            'cabangs' => $cabangs,
        ]);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('edit-user');
        
        $currentUser = $request->user();
        
        // Role-based restrictions for manager/supervisor
        if (in_array($currentUser->role, ['manager', 'supervisor'])) {
            // manager/supervisor can only edit kasir users
            if ($user->role !== 'kasir') {
                abort(403, 'Tidak berhak mengedit user ini');
            }
            
            // Verify the user is in their assigned cabang
            $assignedCabangIds = $currentUser->cabang->pluck('id')->all();
            $userCabangIds = $user->cabang->pluck('id')->all();
            if (empty(array_intersect($assignedCabangIds, $userCabangIds))) {
                abort(403, 'Tidak berhak mengedit user dari cabang ini');
            }
            
            // manager/supervisor cannot change role (must remain kasir)
            $roleValidation = ['required', Rule::in(['kasir'])];
        } else {
            // it_support can change to any role
            $roleValidation = ['required', Rule::in(['it_support', 'manager', 'supervisor', 'kasir'])];
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|confirmed|min:8',
            'role' => $roleValidation,
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
            'aktif' => 'required|boolean',
        ]);

        $cabangIds = array_key_exists('cabang_ids', $validated)
            ? array_map('intval', $validated['cabang_ids'] ?? [])
            : $user->cabang()->pluck('cabang.id')->all();
        
        // Validate cabang_ids for manager/supervisor
        if (in_array($currentUser->role, ['manager', 'supervisor']) && !empty($cabangIds)) {
            $assignedCabangIds = $currentUser->cabang->pluck('id')->all();
            foreach ($cabangIds as $cabangId) {
                if (!in_array($cabangId, $assignedCabangIds)) {
                    return back()->withErrors(['cabang_ids' => 'Tidak berhak menambah user untuk cabang ini'])->withInput();
                }
            }
        }
        
        unset($validated['cabang_ids']);

        if(!empty($validated['password'])){
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $user, (string) $validated['role'], $cabangIds);

        Log::info('User updated', ['actor_user_id' => (int) $request->user()->id, 'user_id' => (int) $user->id]);
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete-user');
        
        $currentUser = auth()->user();
        
        // Role-based restrictions for manager/supervisor
        if (in_array($currentUser->role, ['manager', 'supervisor'])) {
            // manager/supervisor can only delete kasir users
            if ($user->role !== 'kasir') {
                abort(403, 'Tidak berhak menghapus user ini');
            }
            
            // Verify the user is in their assigned cabang
            $assignedCabangIds = $currentUser->cabang->pluck('id')->all();
            $userCabangIds = $user->cabang->pluck('id')->all();
            if (empty(array_intersect($assignedCabangIds, $userCabangIds))) {
                abort(403, 'Tidak berhak menghapus user dari cabang ini');
            }
        }
        
        app(HierarchyAssignmentService::class)->clearHierarchyForUser($user);
        $user->delete();
        Log::info('User deleted', ['actor_user_id' => (int) request()->user()->id, 'user_id' => (int) $user->id]);
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function assignCabang(Request $request, User $user)
    {
        Gate::authorize('edit-user');
        $validated = $request->validate([
            'cabang_ids' => ['required', 'array', 'min:1'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
        ]);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $user, (string) $user->role, array_map('intval', $validated['cabang_ids']));
        return redirect()->route('admin.users.index')->with('success', 'Cabang assigned successfully.');
    }
}
