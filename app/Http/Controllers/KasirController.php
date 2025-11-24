<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KasirController extends Controller
{
    public function __construct()
    {

    }

    public function index()
    {
        Gate::authorize('view-supervisor-dashboard');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $kasir = User::where('role', 'kasir')
            ->whereHas('cabang', function ($q) use ($cabangIds) {
                $q->whereIn('cabang.id', $cabangIds);
            })
            ->with('cabang')
            ->paginate(20);

        return Inertia::render('supervisor/kasir/Index', [
            'kasirs' => $kasir,
        ]);
    }

    public function create()
    {
        Gate::authorize('create-user');

        $user = Auth::user();
        $cabangList = $user->cabang;

        return Inertia::render('supervisor/kasir/Create', [
            'cabang_list' => $cabangList,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create-user');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'aktif' => ['boolean'],
            'cabang_ids' => ['required', 'array'],
            'cabang_ids.*' => [Rule::in($cabangIds)],
        ]);

        DB::transaction(function () use ($validated) {
            $kasir = new User();
            $kasir->name = $validated['name'];
            $kasir->email = $validated['email'];
            $kasir->password = Hash::make($validated['password']);
            $kasir->role = 'kasir';
            $kasir->aktif = $validated['aktif'] ?? true;
            $kasir->save();

            $kasir->cabang()->attach($validated['cabang_ids']);
        });

        return redirect()->route('supervisor.kasir.index')->with('success', 'Kasir berhasil dibuat');
    }

    public function edit(User $kasir)
    {
        Gate::authorize('edit-user');

        $user = Auth::user();
        $cabangList = $user->cabang;

        return Inertia::render('supervisor/kasir/Edit', [
            'kasir' => $kasir,
            'cabang_list' => $cabangList,
        ]);
    }

    public function update(Request $request, User $kasir)
    {
        Gate::authorize('edit-user');

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($kasir->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'aktif' => ['boolean'],
            'cabang_ids' => ['required', 'array'],
            'cabang_ids.*' => [Rule::in($cabangIds)],
        ]);

        DB::transaction(function () use ($validated, $kasir) {
            $kasir->name = $validated['name'];
            $kasir->email = $validated['email'];
            if (!empty($validated['password'])) {
                $kasir->password = Hash::make($validated['password']);
            }
            $kasir->aktif = $validated['aktif'] ?? $kasir->aktif;
            $kasir->save();

            $kasir->cabang()->sync($validated['cabang_ids']);
        });

        return redirect()->route('supervisor.kasir.index')->with('success', 'Kasir berhasil diperbarui');
    }

    public function destroy(User $kasir)
    {
        Gate::authorize('delete-user');


        $hasActiveShift = $kasir->shift()->where('status', 'buka')->exists();
        if ($hasActiveShift) {
            return redirect()->route('supervisor.kasir.index')->with('error', 'Kasir memiliki shift aktif');
        }

        $kasir->delete();
        return redirect()->route('supervisor.kasir.index')->with('success', 'Kasir berhasil dihapus');
    }
}
