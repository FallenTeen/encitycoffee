<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct()
    {

    }

    public function index()
    {
        Gate::authorize('view-it-support');
        $users = User::with('cabang')->paginate(15);
        $cabangs = Cabang::all();
        return Inertia::render('admin/users/Index', compact('users', 'cabangs'));
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
            'cabang_id' => 'nullable|exists:cabang,id',
            'aktif' => 'required|boolean',
        ]);

        $cabangId = $validated['cabang_id'] ?? null;
        unset($validated['cabang_id']);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        if (!empty($cabangId)) {
            $user->cabang()->sync([$cabangId]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        Gate::authorize('edit-user');
        $cabangs = Cabang::all();
        return Inertia::render('admin/users/Edit', compact('user', 'cabangs'));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('edit-user');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|confirmed|min:8',
            'role' => ['required', Rule::in(['it_support', 'manager', 'supervisor', 'kasir'])],
            'cabang_id' => 'nullable|exists:cabang,id',
            'aktif' => 'required|boolean',
        ]);

        $cabangId = $validated['cabang_id'] ?? null;
        unset($validated['cabang_id']);

        if(!empty($validated['password'])){
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if (!empty($cabangId)) {
            $user->cabang()->sync([$cabangId]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete-user');
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function assignCabang(Request $request, User $user)
    {
        Gate::authorize('assign-cabang-user');
        $validated = $request->validate([
            'cabang_id' => 'required|exists:cabang,id',
        ]);

        $user->cabang()->sync([$validated['cabang_id']]);
        return redirect()->route('admin.users.index')->with('success', 'Cabang assigned successfully.');
    }
}
