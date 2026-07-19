<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cabang;
use App\Services\HierarchyAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KaryawanController extends Controller
{
    /**
     * Display unified employee management for managers
     * Shows both supervisors and kasirs assigned to manager's branches
     */
    public function index(Request $request)
    {
        Gate::authorize('view-manager-userManagement');

        $user = Auth::user();
        $user->load('cabang:id,nama,kode');
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // If user has no assigned branches, return empty data
        if (empty($assignedCabangIds)) {
            return Inertia::render('manager/Karyawan/Index', [
                'employees' => [],
                'assignedBranches' => [],
                'unassignedEmployees' => [],
                'filter_aktif' => [
                    'role' => '',
                    'cabang_id' => '',
                    'search' => '',
                ],
                'stats' => [
                    'total_supervisors' => 0,
                    'total_kasirs' => 0,
                ],
                'error' => 'Anda belum memiliki cabang yang diampu. Hubungi administrator untuk menetapkan cabang.',
            ]);
        }

        $validated = $request->validate([
            'role' => ['nullable', 'in:supervisor,kasir,all'],
            'cabang_id' => ['nullable', 'integer', Rule::in($assignedCabangIds)],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Get assigned employees (supervisors and kasirs assigned to manager's branches)
        $query = User::query()
            ->whereIn('role', ['supervisor', 'kasir'])
            ->whereHas('cabang', fn($q) => $q->whereIn('cabang.id', $assignedCabangIds))
            ->with(['cabang:id,kode,nama']);

        // Filter by role
        if (! empty($validated['role']) && $validated['role'] !== 'all') {
            $query->where('role', $validated['role']);
        }

        // Filter by branch - if cabang_id is provided, use it; otherwise show all assigned branches
        if (! empty($validated['cabang_id'])) {
            $cabangId = (int) $validated['cabang_id'];
            $query->whereHas('cabang', fn($q) => $q->where('cabang.id', $cabangId));
        }

        // Search by name or email
        if (! empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($validated['per_page'] ?? 20);
        $employees = $query->orderBy('role')->orderBy('name')->paginate($perPage)->withQueryString();

        // Get unassigned employees (can be assigned to manager's branches)
        // These are employees with role 'supervisor' or 'kasir' who have NO branch assignments
        // This includes employees created by it_support that haven't been assigned yet
        $unassignedQuery = User::query()
            ->whereIn('role', ['supervisor', 'kasir'])
            ->whereDoesntHave('cabang');

        // Only show unassigned employees - they can be assigned to any branch
        $unassignedEmployees = $unassignedQuery
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'aktif'])
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'aktif' => $u->aktif,
            ])
            ->all();

        // Statistics - based on current filtered branch or all assigned branches
        $statsQuery = User::whereIn('role', ['supervisor', 'kasir'])
            ->whereHas('cabang', fn($q) => $q->whereIn('cabang.id', $assignedCabangIds));

        if (! empty($validated['cabang_id'])) {
            $cabangId = (int) $validated['cabang_id'];
            $statsQuery->whereHas('cabang', fn($q) => $q->where('cabang.id', $cabangId));
        }

        $stats = [
            'total_supervisors' => (int) (clone $statsQuery)->where('role', 'supervisor')->count(),
            'total_kasirs' => (int) (clone $statsQuery)->where('role', 'kasir')->count(),
        ];

        // Pass current filter state for UI sync
        $currentFilterCabangId = ! empty($validated['cabang_id']) ? (int) $validated['cabang_id'] : null;

        Log::info('Karyawan index accessed', [
            'user_id' => Auth::id(),
            'role' => Auth::user()->role,
            'assigned_branches' => count($assignedCabangIds),
            'filter_cabang_id' => $currentFilterCabangId,
        ]);

        return Inertia::render('manager/Karyawan/Index', [
            'employees' => $employees,
            'assignedBranches' => $user->cabang->map(fn($c) => [
                'id' => $c->id,
                'nama' => $c->nama,
                'kode' => $c->kode,
            ])->all(),
            'unassignedEmployees' => $unassignedEmployees,
            'filter_aktif' => [
                'role' => $validated['role'] ?? 'all',
                'cabang_id' => $validated['cabang_id'] ?? '',
                'search' => $validated['search'] ?? '',
            ],
            'stats' => $stats,
            'currentFilterCabangId' => $currentFilterCabangId,
        ]);
    }

    /**
     * Create new employee (supervisor or kasir)
     */
    public function create(Request $request)
    {
        Gate::authorize('create-user');

        $user = Auth::user();
        $user->load('cabang:id,nama,kode');
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // Get unassigned employees that can be assigned
        $availableEmployees = User::query()
            ->whereIn('role', ['supervisor', 'kasir'])
            ->whereDoesntHave('cabang')
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role'])
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'email' => $e->email,
                'role' => ucfirst($e->role),
            ])
            ->all();

        return Inertia::render('manager/Karyawan/Create', [
            'assignedBranches' => $user->cabang->map(fn($c) => [
                'id' => $c->id,
                'nama' => $c->nama,
                'kode' => $c->kode,
            ])->all(),
            'availableEmployees' => $availableEmployees,
        ]);
    }

    /**
     * Store new employee assignment
     */
    public function store(Request $request)
    {
        Gate::authorize('create-user');

        $user = Auth::user();
        $user->load('cabang:id');
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        Log::info('KaryawanController@store called', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'assigned_cabang_ids' => $assignedCabangIds,
            'request_data' => $request->all(),
        ]);

        $validated = $request->validate([
            'mode' => ['required', 'in:create_new,assign_existing'],
            // For create new
            'name' => ['required_if:mode,create_new', 'string', 'max:255'],
            'email' => ['required_if:mode,create_new', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required_if:mode,create_new', 'string', 'min:8', 'confirmed'],
            'role' => ['required_if:mode,create_new', 'in:supervisor,kasir'],
            // For assign existing
            'employee_id' => ['required_if:mode,assign_existing', 'integer', 'exists:users,id'],
            // Common
            'aktif' => ['nullable', 'boolean'],
            'cabang_id' => ['required', 'integer', Rule::in($assignedCabangIds)],
        ]);

        try {
            DB::transaction(function () use ($validated, $request, $user) {
                if ($validated['mode'] === 'create_new') {
                    $employee = User::create([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'password' => Hash::make($validated['password']),
                        'role' => $validated['role'],
                        'aktif' => $validated['aktif'] ?? true,
                    ]);
                    $role = $validated['role'];
                    Log::info('Created new employee', ['employee_id' => $employee->id, 'role' => $role]);
                } else {
                    $employee = User::findOrFail($validated['employee_id']);

                    // Check if employee already has a branch assignment
                    if ($employee->cabang()->exists()) {
                        abort(403, 'Karyawan ini sudah ditugaskan ke cabang lain');
                    }
                    $role = $employee->role;
                    Log::info('Assigning existing employee', ['employee_id' => $employee->id, 'role' => $role]);
                }

                // Assign to the specified branch
                app(HierarchyAssignmentService::class)->syncRoleAndCabang(
                    $user,
                    $employee,
                    $role,
                    [(int) $validated['cabang_id']]
                );

                Log::info('Employee assigned to branch', [
                    'actor_user_id' => Auth::id(),
                    'employee_id' => $employee->id,
                    'role' => $role,
                    'cabang_id' => $validated['cabang_id'],
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to assign employee', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return redirect()->route('manager.karyawan.index')->with('success', 'Karyawan berhasil ditugaskan ke cabang');
    }

    /**
     * Show employee details
     */
    public function show(Request $request, User $karyawan)
    {
        Gate::authorize('view-manager-userManagement');

        $user = Auth::user();
        $user->load('cabang:id');
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // Check if employee is assigned to manager's branches
        $employeeCabangIds = $karyawan->cabang()->pluck('cabang.id')->toArray();
        $hasAccess = !empty(array_intersect($employeeCabangIds, $assignedCabangIds));

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke data karyawan ini');
        }

        $karyawan->load(['cabang:id,kode,nama']);

        return Inertia::render('manager/Karyawan/Show', [
            'karyawan' => [
                'id' => $karyawan->id,
                'name' => $karyawan->name,
                'email' => $karyawan->email,
                'role' => $karyawan->role,
                'aktif' => $karyawan->aktif,
                'cabang' => $karyawan->cabang->map(fn($c) => [
                    'id' => $c->id,
                    'nama' => $c->nama,
                    'kode' => $c->kode,
                ])->all(),
                'created_at' => $karyawan->created_at,
            ],
        ]);
    }

    /**
     * Edit employee
     */
    public function edit(Request $request, User $karyawan)
    {
        Gate::authorize('edit-user');

        $user = Auth::user();
        $user->load('cabang:id');
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // Check if employee is assigned to manager's branches
        $employeeCabangIds = $karyawan->cabang()->pluck('cabang.id')->toArray();
        $hasAccess = !empty(array_intersect($employeeCabangIds, $assignedCabangIds));

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke data karyawan ini');
        }

        $karyawan->load('cabang:id,kode,nama');

        return Inertia::render('manager/Karyawan/Edit', [
            'karyawan' => [
                'id' => $karyawan->id,
                'name' => $karyawan->name,
                'email' => $karyawan->email,
                'role' => $karyawan->role,
                'aktif' => $karyawan->aktif,
                'cabang_id' => $karyawan->cabang->first()?->id,
            ],
            'assignedBranches' => $user->cabang->map(fn($c) => [
                'id' => $c->id,
                'nama' => $c->nama,
                'kode' => $c->kode,
            ])->all(),
        ]);
    }

    /**
     * Update employee
     */
    public function update(Request $request, User $karyawan)
    {
        Gate::authorize('edit-user');

        $user = Auth::user();
        $user->load('cabang:id');
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // Check if employee is assigned to manager's branches
        $employeeCabangIds = $karyawan->cabang()->pluck('cabang.id')->toArray();
        $hasAccess = !empty(array_intersect($employeeCabangIds, $assignedCabangIds));

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke data karyawan ini');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($karyawan->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'aktif' => ['nullable', 'boolean'],
            'cabang_id' => ['required', 'integer', Rule::in($assignedCabangIds)],
        ]);

        DB::transaction(function () use ($validated, $karyawan, $user) {
            $karyawan->name = $validated['name'];
            $karyawan->email = $validated['email'];
            if (!empty($validated['password'])) {
                $karyawan->password = Hash::make($validated['password']);
            }
            $karyawan->aktif = $validated['aktif'] ?? $karyawan->aktif;
            $karyawan->save();

            // Update branch assignment
            app(HierarchyAssignmentService::class)->syncRoleAndCabang(
                $user,
                $karyawan,
                $karyawan->role,
                [(int) $validated['cabang_id']]
            );

            Log::info('Employee updated', [
                'actor_user_id' => Auth::id(),
                'employee_id' => $karyawan->id,
                'cabang_id' => $validated['cabang_id'],
            ]);
        });

        return redirect()->route('manager.karyawan.index')->with('success', 'Data karyawan berhasil diperbarui');
    }

    /**
     * Remove employee from branch (unassign)
     */
    public function destroy(Request $request, User $karyawan)
    {
        Gate::authorize('delete-user');

        $user = Auth::user();
        $user->load('cabang:id');
        $assignedCabangIds = $user->cabang->pluck('id')->toArray();

        // Check if employee is assigned to manager's branches
        $employeeCabangIds = $karyawan->cabang()->pluck('cabang.id')->toArray();
        $hasAccess = !empty(array_intersect($employeeCabangIds, $assignedCabangIds));

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke data karyawan ini');
        }

        // Check if employee has active shift
        $hasActiveShift = $karyawan->shift()->where('status', 'buka')->exists();
        if ($hasActiveShift) {
            return redirect()->back()->with('error', 'Karyawan memiliki shift aktif. Tutup shift terlebih dahulu.');
        }

        DB::transaction(function () use ($karyawan) {
            // Clear branch assignment
            app(HierarchyAssignmentService::class)->clearHierarchyForUser($karyawan);

            Log::info('Employee unassigned from branch', [
                'actor_user_id' => Auth::id(),
                'employee_id' => $karyawan->id,
            ]);
        });

        return redirect()->route('manager.karyawan.index')->with('success', 'Penugasan karyawan berhasil dihapus');
    }
}
