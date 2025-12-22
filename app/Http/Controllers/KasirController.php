<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\HierarchyAssignmentService;
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

        $validated = request()->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'cabang_id' => ['nullable', Rule::in($cabangIds)],
        ]);

        $kasirQuery = User::where('role', 'kasir')
            ->whereHas('cabang', fn ($q) => $q->whereIn('cabang.id', $cabangIds))
            ->whereDoesntHave('cabang', fn ($q) => $q->whereNotIn('cabang.id', $cabangIds))
            ->with('cabang');

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $kasirQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }
        if (! empty($validated['cabang_id'])) {
            $cabangId = (int) $validated['cabang_id'];
            $kasirQuery->whereHas('cabang', fn ($q) => $q->where('cabang.id', $cabangId));
        }

        $kasir = $kasirQuery->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('supervisor/kasir/Index', [
            'kasirs' => $kasir,
            'cabang_list' => $user->cabang()->get(['cabang.id', 'cabang.nama'])->map(fn ($c) => ['id' => (int) $c->id, 'nama' => $c->nama]),
            'filter_aktif' => [
                'search' => $validated['search'] ?? '',
                'cabang_id' => $validated['cabang_id'] ?? '',
            ],
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
            'cabang_ids' => ['required', 'array', 'size:1'],
            'cabang_ids.*' => [Rule::in($cabangIds)],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $kasir = new User();
            $kasir->name = $validated['name'];
            $kasir->email = $validated['email'];
            $kasir->password = Hash::make($validated['password']);
            $kasir->role = 'kasir';
            $kasir->aktif = $validated['aktif'] ?? true;
            $kasir->save();

            app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $kasir, 'kasir', array_map('intval', $validated['cabang_ids']));
        });

        Log::info('Kasir created', ['actor_user_id' => (int) $request->user()->id, 'role' => 'kasir', 'email' => $validated['email']]);
        return redirect()->route('supervisor.kasir.index')->with('success', 'Kasir berhasil dibuat');
    }

    public function edit(User $kasir)
    {
        Gate::authorize('edit-user');
        $this->assertKasirWithinScope(Auth::user(), $kasir);

        $user = Auth::user();
        $cabangList = $user->cabang;

        $kasir->load('cabang:id,kode,nama');
        return Inertia::render('supervisor/kasir/Edit', [
            'kasir' => $kasir,
            'cabang_list' => $cabangList,
        ]);
    }

    public function update(Request $request, User $kasir)
    {
        Gate::authorize('edit-user');
        $this->assertKasirWithinScope(Auth::user(), $kasir);

        $user = Auth::user();
        $cabangIds = $user->cabang->pluck('id')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($kasir->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'aktif' => ['boolean'],
            'cabang_ids' => ['required', 'array', 'size:1'],
            'cabang_ids.*' => [Rule::in($cabangIds)],
        ]);

        DB::transaction(function () use ($validated, $kasir, $request) {
            $kasir->name = $validated['name'];
            $kasir->email = $validated['email'];
            if (!empty($validated['password'])) {
                $kasir->password = Hash::make($validated['password']);
            }
            $kasir->aktif = $validated['aktif'] ?? $kasir->aktif;
            $kasir->save();

            app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $kasir, 'kasir', array_map('intval', $validated['cabang_ids']));
        });

        Log::info('Kasir updated', ['actor_user_id' => (int) $request->user()->id, 'kasir_id' => (int) $kasir->id]);
        return redirect()->route('supervisor.kasir.index')->with('success', 'Kasir berhasil diperbarui');
    }

    public function destroy(User $kasir)
    {
        Gate::authorize('delete-user');
        $this->assertKasirWithinScope(Auth::user(), $kasir);


        $hasActiveShift = $kasir->shift()->where('status', 'buka')->exists();
        if ($hasActiveShift) {
            return redirect()->route('supervisor.kasir.index')->with('error', 'Kasir memiliki shift aktif');
        }

        app(HierarchyAssignmentService::class)->clearHierarchyForUser($kasir);
        $kasir->delete();
        Log::info('Kasir deleted', ['actor_user_id' => (int) request()->user()->id, 'kasir_id' => (int) $kasir->id]);
        return redirect()->route('supervisor.kasir.index')->with('success', 'Kasir berhasil dihapus');
    }

    public function show(User $kasir)
    {
        Gate::authorize('view-supervisor-dashboard');
        $this->assertKasirWithinScope(Auth::user(), $kasir);
        $kasir->load('cabang:id,kode,nama');
        return Inertia::render('supervisor/kasir/Show', [
            'kasir' => $kasir,
        ]);
    }

    public function indexManager(Request $request)
    {
        Gate::authorize('view-manager-userManagement');

        $user = $request->user();
        $cabangIds = $user->cabang()->pluck('cabang.id')->all();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'cabang_id' => ['nullable', Rule::in($cabangIds)],
        ]);

        $query = User::where('role', 'kasir')
            ->whereHas('cabang', fn ($q) => $q->whereIn('cabang.id', $cabangIds))
            ->whereDoesntHave('cabang', fn ($q) => $q->whereNotIn('cabang.id', $cabangIds))
            ->with('cabang');

        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }
        if (! empty($validated['cabang_id'])) {
            $cabangId = (int) $validated['cabang_id'];
            $query->whereHas('cabang', fn ($q) => $q->where('cabang.id', $cabangId));
        }

        $kasirs = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('manager/kasir/Index', [
            'kasirs' => $kasirs,
            'cabang_list' => $user->cabang()->get(['cabang.id', 'cabang.nama'])->map(fn ($c) => ['id' => (int) $c->id, 'nama' => $c->nama]),
            'filter_aktif' => [
                'search' => $validated['search'] ?? '',
                'cabang_id' => $validated['cabang_id'] ?? '',
            ],
        ]);
    }

    public function createManager(Request $request)
    {
        Gate::authorize('create-user');

        $user = $request->user();
        $cabangList = $user->cabang()->get(['cabang.id', 'cabang.kode', 'cabang.nama']);

        return Inertia::render('manager/kasir/Create', [
            'cabang_list' => $cabangList,
        ]);
    }

    public function storeManager(Request $request)
    {
        Gate::authorize('create-user');

        $user = $request->user();
        $cabangIds = $user->cabang()->pluck('cabang.id')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'aktif' => ['boolean'],
            'cabang_ids' => ['required', 'array', 'size:1'],
            'cabang_ids.*' => [Rule::in($cabangIds)],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $kasir = new User();
            $kasir->name = $validated['name'];
            $kasir->email = $validated['email'];
            $kasir->password = Hash::make($validated['password']);
            $kasir->role = 'kasir';
            $kasir->aktif = $validated['aktif'] ?? true;
            $kasir->save();

            app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $kasir, 'kasir', array_map('intval', $validated['cabang_ids']));
        });

        Log::info('Kasir created by manager', ['actor_user_id' => (int) $request->user()->id, 'role' => 'kasir', 'email' => $validated['email']]);
        return redirect()->route('manager.kasir.index')->with('success', 'Kasir berhasil dibuat');
    }

    public function editManager(Request $request, User $kasir)
    {
        Gate::authorize('edit-user');
        $this->assertKasirWithinScope($request->user(), $kasir);

        $user = $request->user();
        $cabangList = $user->cabang()->get(['cabang.id', 'cabang.kode', 'cabang.nama']);
        $kasir->load('cabang:id,kode,nama');

        return Inertia::render('manager/kasir/Edit', [
            'kasir' => $kasir,
            'cabang_list' => $cabangList,
        ]);
    }

    public function updateManager(Request $request, User $kasir)
    {
        Gate::authorize('edit-user');
        $this->assertKasirWithinScope($request->user(), $kasir);

        $user = $request->user();
        $cabangIds = $user->cabang()->pluck('cabang.id')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($kasir->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'aktif' => ['boolean'],
            'cabang_ids' => ['required', 'array', 'size:1'],
            'cabang_ids.*' => [Rule::in($cabangIds)],
        ]);

        DB::transaction(function () use ($validated, $kasir, $request) {
            $kasir->name = $validated['name'];
            $kasir->email = $validated['email'];
            if (!empty($validated['password'])) {
                $kasir->password = Hash::make($validated['password']);
            }
            $kasir->aktif = $validated['aktif'] ?? $kasir->aktif;
            $kasir->save();

            app(HierarchyAssignmentService::class)->syncRoleAndCabang($request->user(), $kasir, 'kasir', array_map('intval', $validated['cabang_ids']));
        });

        Log::info('Kasir updated by manager', ['actor_user_id' => (int) $request->user()->id, 'kasir_id' => (int) $kasir->id]);
        return redirect()->route('manager.kasir.index')->with('success', 'Kasir berhasil diperbarui');
    }

    public function destroyManager(Request $request, User $kasir)
    {
        Gate::authorize('delete-user');
        $this->assertKasirWithinScope($request->user(), $kasir);

        $hasActiveShift = $kasir->shift()->where('status', 'buka')->exists();
        if ($hasActiveShift) {
            return redirect()->route('manager.kasir.index')->with('error', 'Kasir memiliki shift aktif');
        }

        app(HierarchyAssignmentService::class)->clearHierarchyForUser($kasir);
        $kasir->delete();
        Log::info('Kasir deleted by manager', ['actor_user_id' => (int) $request->user()->id, 'kasir_id' => (int) $kasir->id]);
        return redirect()->route('manager.kasir.index')->with('success', 'Kasir berhasil dihapus');
    }

    private function assertKasirWithinScope(User $actor, User $kasir): void
    {
        if ((string) $kasir->role !== 'kasir') {
            abort(404);
        }

        $cabangIds = $actor->cabang()->pluck('cabang.id')->all();
        if (empty($cabangIds)) {
            abort(403);
        }

        $targetCabangIds = $kasir->cabang()->pluck('cabang.id')->all();
        $diff = array_values(array_diff($targetCabangIds, $cabangIds));
        if (! empty($diff)) {
            abort(403);
        }
    }
}
