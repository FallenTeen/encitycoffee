<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $itSupport = User::create([
            'name'     => 'IT Support',
            'email'    => 'it@168.com',
            'password' => Hash::make('111111'),
            'role'     => 'it_support',
            'aktif'    => true,
        ]);

        $manager1 = User::create([
            'name'     => 'Manager 1',
            'email'    => 'man1@168.com',
            'password' => Hash::make('111111'),
            'role'     => 'manager',
            'aktif'    => true,
        ]);

        $manager2 = User::create([
            'name'     => 'Manager 2',
            'email'    => 'man2@168.com',
            'password' => Hash::make('111111'),
            'role'     => 'manager',
            'aktif'    => true,
        ]);

        $spv1 = User::create([
            'name'     => 'Supervisor 1.1',
            'email'    => 'spv1@168.com',
            'password' => Hash::make('111111'),
            'role'     => 'supervisor',
            'aktif'    => true,
        ]);

        $spv2 = User::create([
            'name'     => 'Supervisor 2.1',
            'email'    => 'spv2@168.com',
            'password' => Hash::make('111111'),
            'role'     => 'supervisor',
            'aktif'    => true,
        ]);

        $kasir1 = User::create([
            'name'     => 'Kasir 1.1',
            'email'    => '1@168.com',
            'password' => Hash::make('111111'),
            'role'     => 'kasir',
            'aktif'    => true,
        ]);

        $kasir2 = User::create([
            'name'     => 'Kasir 2.1',
            'email'    => '2@168.com',
            'password' => Hash::make('111111'),
            'role'     => 'kasir',
            'aktif'    => true,
        ]);

        echo "======== Data user berhasil dibuat ========\n";
    }
}
