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
        Gate::authorize('view-it-support');

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::in(['it_support', 'manager', 'supervisor', 'kasir'])],
            'aktif' => ['nullable', Rule::in(['1', '0'])],
            'cabang_id' => ['nullable', 'integer', 'exists:cabang,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()->with('cabang');
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }
        if (array_key_exists('aktif', $validated) && $validated['aktif'] !== null) {
            $query->where('aktif', $validated['aktif'] === '1');
        }
        if (! empty($validated['cabang_id'])) {
            $cabangId = (int) $validated['cabang_id'];
            $query->whereHas('cabang', fn ($q) => $q->where('cabang.id', $cabangId));
        }

        $perPage = (int) ($validated['per_page'] ?? 15);
        $users = $query->orderBy('name')->paginate($perPage)->withQueryString();
        $cabangs = Cabang::orderBy('kode')->get(['id', 'kode', 'nama']);

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
        $cabangs = Cabang::all();
        return Inertia::render('admin/users/Create', compact('cabangs'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create-user');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'role' => ['required', Rule::in(['it_support', 'manager', 'supervisor', 'kasir'])],
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
            'aktif' => 'required|boolean',
        ]);

        $cabangIds = array_map('intval', $validated['cabang_ids'] ?? []);
        unset($validated['cabang_ids']);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $user, (string) $user->role, $cabangIds);

        Log::info('User created', ['actor_user_id' => (int) $request->user()->id, 'user_id' => (int) $user->id, 'role' => (string) $user->role]);
        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        Gate::authorize('edit-user');
        $cabangs = Cabang::orderBy('kode')->get(['id', 'kode', 'nama']);
        $user->load('cabang:id,kode,nama');
        return Inertia::render('admin/users/Edit', [
            'user' => $user,
            'cabangs' => $cabangs,
        ]);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('edit-user');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|confirmed|min:8',
            'role' => ['required', Rule::in(['it_support', 'manager', 'supervisor', 'kasir'])],
            'cabang_ids' => ['nullable', 'array'],
            'cabang_ids.*' => ['integer', 'exists:cabang,id'],
            'aktif' => 'required|boolean',
        ]);

        $cabangIds = array_key_exists('cabang_ids', $validated)
            ? array_map('intval', $validated['cabang_ids'] ?? [])
            : $user->cabang()->pluck('cabang.id')->all();
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
