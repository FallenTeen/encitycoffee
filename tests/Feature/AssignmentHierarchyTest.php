<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\User;
use App\Services\HierarchyAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssignmentHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_support_can_assign_manager_to_multiple_cabangs_and_set_cabang_hierarchy(): void
    {
        $it = User::factory()->create(['role' => 'it_support']);
        $manager = User::factory()->create(['role' => 'manager']);

        $c1 = Cabang::create(['kode' => 'CB1', 'nama' => 'Cabang 1', 'aktif' => true]);
        $c2 = Cabang::create(['kode' => 'CB2', 'nama' => 'Cabang 2', 'aktif' => true]);

        app(HierarchyAssignmentService::class)->syncRoleAndCabang($it, $manager, 'manager', [$c1->id, $c2->id]);

        $this->assertDatabaseHas('user_cabang', ['user_id' => $manager->id, 'cabang_id' => $c1->id]);
        $this->assertDatabaseHas('user_cabang', ['user_id' => $manager->id, 'cabang_id' => $c2->id]);
        $this->assertDatabaseHas('cabang_hierarchy', ['cabang_id' => $c1->id, 'manager_user_id' => $manager->id]);
        $this->assertDatabaseHas('cabang_hierarchy', ['cabang_id' => $c2->id, 'manager_user_id' => $manager->id]);
    }

    public function test_manager_assigning_supervisor_sets_responsibility_and_branch(): void
    {
        $it = User::factory()->create(['role' => 'it_support']);
        $manager = User::factory()->create(['role' => 'manager']);
        $c1 = Cabang::create(['kode' => 'CB1', 'nama' => 'Cabang 1', 'aktif' => true]);

        app(HierarchyAssignmentService::class)->syncRoleAndCabang($it, $manager, 'manager', [$c1->id]);

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($manager, $supervisor, 'supervisor', [$c1->id]);

        $this->assertDatabaseHas('supervisor_hierarchy', [
            'supervisor_user_id' => $supervisor->id,
            'cabang_id' => $c1->id,
            'manager_user_id' => $manager->id,
        ]);
        $this->assertDatabaseHas('cabang_hierarchy', [
            'cabang_id' => $c1->id,
            'manager_user_id' => $manager->id,
            'supervisor_user_id' => $supervisor->id,
        ]);
        $this->assertDatabaseHas('assignment_histories', [
            'actor_user_id' => $manager->id,
            'subject_user_id' => $supervisor->id,
            'action' => 'assign_supervisor',
        ]);
    }

    public function test_supervisor_assigning_kasir_links_to_manager_and_supervisor_in_same_cabang(): void
    {
        $it = User::factory()->create(['role' => 'it_support']);
        $manager = User::factory()->create(['role' => 'manager']);
        $c1 = Cabang::create(['kode' => 'CB1', 'nama' => 'Cabang 1', 'aktif' => true]);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($it, $manager, 'manager', [$c1->id]);

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($manager, $supervisor, 'supervisor', [$c1->id]);

        $kasir = User::factory()->create(['role' => 'kasir']);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($supervisor, $kasir, 'kasir', [$c1->id]);

        $this->assertDatabaseHas('kasir_hierarchy', [
            'kasir_user_id' => $kasir->id,
            'cabang_id' => $c1->id,
            'manager_user_id' => $manager->id,
            'supervisor_user_id' => $supervisor->id,
        ]);
    }

    public function test_moving_kasir_updates_supervisor_and_manager_to_new_cabang(): void
    {
        $it = User::factory()->create(['role' => 'it_support']);
        $manager = User::factory()->create(['role' => 'manager']);
        $c1 = Cabang::create(['kode' => 'CB1', 'nama' => 'Cabang 1', 'aktif' => true]);
        $c2 = Cabang::create(['kode' => 'CB2', 'nama' => 'Cabang 2', 'aktif' => true]);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($it, $manager, 'manager', [$c1->id, $c2->id]);

        $s1 = User::factory()->create(['role' => 'supervisor']);
        $s2 = User::factory()->create(['role' => 'supervisor']);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($manager, $s1, 'supervisor', [$c1->id]);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($manager, $s2, 'supervisor', [$c2->id]);

        $kasir = User::factory()->create(['role' => 'kasir']);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($it, $kasir, 'kasir', [$c1->id]);

        app(HierarchyAssignmentService::class)->syncRoleAndCabang($it, $kasir, 'kasir', [$c2->id]);

        $this->assertDatabaseHas('kasir_hierarchy', [
            'kasir_user_id' => $kasir->id,
            'cabang_id' => $c2->id,
            'supervisor_user_id' => $s2->id,
            'manager_user_id' => $manager->id,
        ]);
        $this->assertDatabaseHas('assignment_histories', [
            'actor_user_id' => $it->id,
            'subject_user_id' => $kasir->id,
            'action' => 'move_kasir',
        ]);
    }

    public function test_cannot_assign_two_managers_to_one_cabang(): void
    {
        $it = User::factory()->create(['role' => 'it_support']);
        $m1 = User::factory()->create(['role' => 'manager']);
        $m2 = User::factory()->create(['role' => 'manager']);
        $c1 = Cabang::create(['kode' => 'CB1', 'nama' => 'Cabang 1', 'aktif' => true]);

        app(HierarchyAssignmentService::class)->syncRoleAndCabang($it, $m1, 'manager', [$c1->id]);

        $this->expectException(ValidationException::class);
        app(HierarchyAssignmentService::class)->syncRoleAndCabang($it, $m2, 'manager', [$c1->id]);
    }
}

