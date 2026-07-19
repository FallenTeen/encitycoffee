<?php

namespace App\Services;

use App\Models\Cabang;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HierarchyAssignmentService
{
    public function syncRoleAndCabang(User $actor, User $subject, string $newRole, array $newCabangIds): void
    {
        $newRole = strtolower($newRole);
        $newCabangIds = array_values(array_unique(array_map('intval', $newCabangIds)));

        $oldRole = strtolower((string) $subject->role);
        $oldCabangIds = $subject->cabang()->pluck('cabang.id')->all();
        $oldCabangId = count($oldCabangIds) === 1 ? (int) $oldCabangIds[0] : null;
        $hadCabangBefore = ! empty($oldCabangIds);
        $oldSupervisor = $this->getKasirHierarchy($subject->id)['supervisor_user_id'] ?? null;
        $oldManager = $this->getKasirHierarchy($subject->id)['manager_user_id'] ?? null;

        DB::transaction(function () use ($actor, $subject, $newRole, $newCabangIds, $oldRole, $oldCabangId, $oldSupervisor, $oldManager, $hadCabangBefore) {
            if ($oldRole !== $newRole) {
                $this->clearHierarchyForUser($subject);
                $subject->cabang()->sync([]);
            }

            if ($newRole === 'it_support') {
                if (! empty($newCabangIds)) {
                    throw ValidationException::withMessages(['cabang_ids' => 'it_support tidak boleh ditetapkan ke cabang']);
                }
                $this->clearHierarchyForUser($subject);
                $subject->cabang()->sync([]);
                $this->logHistory($actor, $subject, 'role_change', $oldCabangId, null, $oldManager, null, $oldSupervisor, null, [
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                ]);
                return;
            }

            if ($newRole === 'manager') {
                if (empty($newCabangIds)) {
                    throw ValidationException::withMessages(['cabang_ids' => 'Cabang wajib untuk role ini']);
                }
                $oldManagedCabangIds = DB::table('cabang_hierarchy')->where('manager_user_id', (int) $subject->id)->pluck('cabang_id')->all();
                $subject->cabang()->sync($newCabangIds);
                $this->syncManagerCabangHierarchy($actor, $subject, $newCabangIds);
                $removed = array_values(array_diff(array_map('intval', $oldManagedCabangIds), $newCabangIds));
                foreach ($removed as $cabangId) {
                    DB::table('cabang_hierarchy')
                        ->where('cabang_id', (int) $cabangId)
                        ->where('manager_user_id', (int) $subject->id)
                        ->update(['manager_user_id' => null, 'updated_at' => now()]);
                    $this->syncDownlineForCabang((int) $cabangId);
                }
                $action = $oldRole !== 'manager' || ! $hadCabangBefore ? 'assign_manager' : 'update_manager_cabangs';
                $this->logHistory($actor, $subject, $action, $oldCabangId, null, $oldManager, null, $oldSupervisor, null, [
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                    'cabang_ids' => $newCabangIds,
                ]);
                return;
            }

            if (in_array($newRole, ['supervisor', 'kasir'], true)) {
                if (count($newCabangIds) !== 1) {
                    throw ValidationException::withMessages(['cabang_ids' => 'Role ini wajib tepat 1 cabang']);
                }
                $newCabangId = (int) $newCabangIds[0];
                $subject->cabang()->sync([$newCabangId]);

                if ($newRole === 'supervisor') {
                    $this->assignSupervisor($actor, $subject, $newCabangId);
                    $action = $oldRole !== 'supervisor' || ! $hadCabangBefore ? 'assign_supervisor' : 'move_supervisor';
                    $this->logHistory($actor, $subject, $action, $oldCabangId, $newCabangId, $oldManager, $this->getSupervisorHierarchy($subject->id)['manager_user_id'] ?? null, $oldSupervisor, null, [
                        'old_role' => $oldRole,
                        'new_role' => $newRole,
                    ]);
                    return;
                }

                $this->assignKasir($actor, $subject, $newCabangId);
                $newKasir = $this->getKasirHierarchy($subject->id);
                $action = $oldRole !== 'kasir' || ! $hadCabangBefore ? 'assign_kasir' : 'move_kasir';
                $this->logHistory($actor, $subject, $action, $oldCabangId, $newCabangId, $oldManager, $newKasir['manager_user_id'] ?? null, $oldSupervisor, $newKasir['supervisor_user_id'] ?? null, [
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                ]);
                return;
            }

            throw ValidationException::withMessages(['role' => 'Role tidak dikenali']);
        });
    }

    public function syncManagerCabangHierarchy(User $actor, User $manager, array $cabangIds): void
    {
        $cabangIds = array_values(array_unique(array_map('intval', $cabangIds)));
        if (empty($cabangIds)) {
            return;
        }

        $existing = DB::table('cabang_hierarchy')
            ->whereIn('cabang_id', $cabangIds)
            ->whereNotNull('manager_user_id')
            ->where('manager_user_id', '!=', $manager->id)
            ->pluck('cabang_id')
            ->all();
        if (! empty($existing)) {
            throw ValidationException::withMessages(['cabang_ids' => 'Cabang sudah memiliki manager']);
        }

        foreach ($cabangIds as $cabangId) {
            $this->upsertCabangHierarchy($cabangId, ['manager_user_id' => (int) $manager->id]);
            $this->syncDownlineForCabang((int) $cabangId);
        }
    }

    public function assignSupervisor(User $actor, User $supervisor, int $cabangId): void
    {
        $this->assertCabangExists($cabangId);
        $role = strtolower((string) $supervisor->role);
        if ($role !== 'supervisor') {
            throw ValidationException::withMessages(['role' => 'User bukan supervisor']);
        }

        $actorRole = strtolower((string) $actor->role);
        if ($actorRole === 'manager') {
            $allowedCabangIds = $actor->cabang()->pluck('cabang.id')->all();
            if (! in_array((int) $cabangId, array_map('intval', $allowedCabangIds), true)) {
                throw ValidationException::withMessages(['cabang_ids' => 'Tidak memiliki akses cabang ini']);
            }
        }

        $currentSupervisor = DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->value('supervisor_user_id');
        if ($currentSupervisor !== null && (int) $currentSupervisor !== (int) $supervisor->id) {
            throw ValidationException::withMessages(['cabang_ids' => 'Cabang sudah memiliki supervisor']);
        }

        $managerId = null;
        if ($actorRole === 'manager') {
            $managerId = (int) $actor->id;
        } else {
            $managerId = DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->value('manager_user_id');
            $managerId = $managerId !== null ? (int) $managerId : null;
        }

        // Ensure manager exists in cabang_hierarchy for this branch
        $existingRow = DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->first();
        if (! $existingRow) {
            // Create the row first with manager
            $this->upsertCabangHierarchy($cabangId, ['manager_user_id' => $managerId]);
        } elseif ($existingRow->manager_user_id === null && $managerId !== null) {
            // Update manager if not set
            DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->update([
                'manager_user_id' => $managerId,
                'updated_at' => now(),
            ]);
        }

        $oldSupervisorRow = DB::table('supervisor_hierarchy')->where('supervisor_user_id', (int) $supervisor->id)->first();
        $oldCabangId = $oldSupervisorRow ? (int) $oldSupervisorRow->cabang_id : null;

        DB::table('supervisor_hierarchy')->updateOrInsert(
            ['supervisor_user_id' => (int) $supervisor->id],
            [
                'cabang_id' => (int) $cabangId,
                'manager_user_id' => $managerId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if ($oldCabangId !== null && $oldCabangId !== (int) $cabangId) {
            DB::table('cabang_hierarchy')
                ->where('cabang_id', (int) $oldCabangId)
                ->where('supervisor_user_id', (int) $supervisor->id)
                ->update(['supervisor_user_id' => null, 'updated_at' => now()]);
            $this->syncDownlineForCabang((int) $oldCabangId);
        }

        $this->upsertCabangHierarchy($cabangId, [
            'supervisor_user_id' => (int) $supervisor->id,
            'manager_user_id' => $managerId,
        ]);

        $this->syncDownlineForCabang($cabangId);
    }

    public function assignKasir(User $actor, User $kasir, int $cabangId): void
    {
        $this->assertCabangExists($cabangId);
        $role = strtolower((string) $kasir->role);
        if ($role !== 'kasir') {
            throw ValidationException::withMessages(['role' => 'User bukan kasir']);
        }

        $actorRole = strtolower((string) $actor->role);
        if (in_array($actorRole, ['manager', 'supervisor'], true)) {
            $allowedCabangIds = $actor->cabang()->pluck('cabang.id')->all();
            if (! in_array((int) $cabangId, array_map('intval', $allowedCabangIds), true)) {
                throw ValidationException::withMessages(['cabang_ids' => 'Tidak memiliki akses cabang ini']);
            }
        }

        $managerId = DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->value('manager_user_id');
        $supervisorId = DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->value('supervisor_user_id');

        $managerId = $managerId !== null ? (int) $managerId : null;
        $supervisorId = $supervisorId !== null ? (int) $supervisorId : null;

        // If actor is manager and manager_id is null, use actor's ID
        if ($actorRole === 'manager' && $managerId === null) {
            $managerId = (int) $actor->id;
            // Update cabang_hierarchy with manager
            $existingRow = DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->first();
            if ($existingRow) {
                DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->update([
                    'manager_user_id' => $managerId,
                    'updated_at' => now(),
                ]);
            } else {
                $this->upsertCabangHierarchy($cabangId, ['manager_user_id' => $managerId]);
            }
        }

        // If actor is supervisor and supervisor_id is null, self-assign as supervisor
        if ($actorRole === 'supervisor' && $supervisorId === null) {
            $this->assignSupervisor($actor, $actor, $cabangId);
            $supervisorId = (int) $actor->id;
            $managerId = DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->value('manager_user_id');
            $managerId = $managerId !== null ? (int) $managerId : null;
        }

        // For managers: ensure supervisor is set if manager exists but supervisor doesn't
        if ($actorRole === 'manager' && $managerId !== null && $supervisorId === null) {
            // We can't auto-create a supervisor, but we should not block if manager is setting up
            // Just log warning and allow - supervisor can be assigned later
        }

        if ($managerId === null) {
            throw ValidationException::withMessages(['cabang_ids' => 'Cabang belum memiliki manager. Hubungi administrator untuk menetapkan manager terlebih dahulu.']);
        }
        if ($supervisorId === null) {
            throw ValidationException::withMessages(['cabang_ids' => 'Cabang belum memiliki supervisor. Supervisor harus ditugaskan sebelum kasir dapat ditambahkan.']);
        }

        DB::table('kasir_hierarchy')->updateOrInsert(
            ['kasir_user_id' => (int) $kasir->id],
            [
                'cabang_id' => (int) $cabangId,
                'manager_user_id' => $managerId,
                'supervisor_user_id' => $supervisorId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function syncDownlineForCabang(int $cabangId): void
    {
        $row = DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->first();
        $managerId = $row?->manager_user_id !== null ? (int) $row->manager_user_id : null;
        $supervisorId = $row?->supervisor_user_id !== null ? (int) $row->supervisor_user_id : null;

        if ($supervisorId !== null) {
            DB::table('supervisor_hierarchy')->updateOrInsert(
                ['supervisor_user_id' => $supervisorId],
                [
                    'cabang_id' => (int) $cabangId,
                    'manager_user_id' => $managerId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('kasir_hierarchy')
            ->where('cabang_id', $cabangId)
            ->update([
                'manager_user_id' => $managerId,
                'supervisor_user_id' => $supervisorId,
                'updated_at' => now(),
            ]);
    }

    public function clearHierarchyForUser(User $user): void
    {
        $role = strtolower((string) $user->role);
        if ($role === 'manager') {
            $cabangIds = $user->cabang()->pluck('cabang.id')->all();
            foreach ($cabangIds as $cabangId) {
                $current = DB::table('cabang_hierarchy')->where('cabang_id', (int) $cabangId)->value('manager_user_id');
                if ($current !== null && (int) $current === (int) $user->id) {
                    DB::table('cabang_hierarchy')->where('cabang_id', (int) $cabangId)->update([
                        'manager_user_id' => null,
                        'updated_at' => now(),
                    ]);
                    $this->syncDownlineForCabang((int) $cabangId);
                }
            }
            return;
        }

        if ($role === 'supervisor') {
            $supervisorRow = DB::table('supervisor_hierarchy')->where('supervisor_user_id', (int) $user->id)->first();
            if ($supervisorRow) {
                $cabangId = (int) $supervisorRow->cabang_id;
                DB::table('supervisor_hierarchy')->where('supervisor_user_id', (int) $user->id)->delete();
                DB::table('cabang_hierarchy')->where('cabang_id', $cabangId)->where('supervisor_user_id', (int) $user->id)->update([
                    'supervisor_user_id' => null,
                    'updated_at' => now(),
                ]);
                $this->syncDownlineForCabang($cabangId);
            }
            return;
        }

        if ($role === 'kasir') {
            DB::table('kasir_hierarchy')->where('kasir_user_id', (int) $user->id)->delete();
        }
    }

    public function getSupervisorHierarchy(int $supervisorUserId): array
    {
        $row = DB::table('supervisor_hierarchy')->where('supervisor_user_id', $supervisorUserId)->first();
        if (! $row) {
            return [];
        }
        return [
            'cabang_id' => (int) $row->cabang_id,
            'manager_user_id' => $row->manager_user_id !== null ? (int) $row->manager_user_id : null,
        ];
    }

    public function getKasirHierarchy(int $kasirUserId): array
    {
        $row = DB::table('kasir_hierarchy')->where('kasir_user_id', $kasirUserId)->first();
        if (! $row) {
            return [];
        }
        return [
            'cabang_id' => (int) $row->cabang_id,
            'manager_user_id' => $row->manager_user_id !== null ? (int) $row->manager_user_id : null,
            'supervisor_user_id' => $row->supervisor_user_id !== null ? (int) $row->supervisor_user_id : null,
        ];
    }

    private function upsertCabangHierarchy(int $cabangId, array $fields): void
    {
        $data = array_merge($fields, ['updated_at' => now()]);
        DB::table('cabang_hierarchy')->updateOrInsert(
            ['cabang_id' => (int) $cabangId],
            array_merge($data, ['created_at' => now()])
        );
    }

    private function assertCabangExists(int $cabangId): void
    {
        if (! Cabang::whereKey($cabangId)->exists()) {
            throw ValidationException::withMessages(['cabang_ids' => 'Cabang tidak ditemukan']);
        }
    }

    private function logHistory(
        User $actor,
        User $subject,
        string $action,
        ?int $fromCabangId,
        ?int $toCabangId,
        ?int $fromManagerId,
        ?int $toManagerId,
        ?int $fromSupervisorId,
        ?int $toSupervisorId,
        array $payload = []
    ): void {
        DB::table('assignment_histories')->insert([
            'actor_user_id' => (int) $actor->id,
            'subject_user_id' => (int) $subject->id,
            'subject_role' => strtolower((string) $subject->role),
            'action' => $action,
            'from_cabang_id' => $fromCabangId,
            'to_cabang_id' => $toCabangId,
            'from_manager_user_id' => $fromManagerId,
            'to_manager_user_id' => $toManagerId,
            'from_supervisor_user_id' => $fromSupervisorId,
            'to_supervisor_user_id' => $toSupervisorId,
            'payload' => empty($payload) ? null : json_encode($payload),
            'created_at' => now(),
        ]);
    }
}
