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
            'nama'     => 'IT Support',
            'email'    => 'it@coffeshop.com',
            'password' => Hash::make('password'),
            'role'     => 'it_support',
            'aktif'    => true,
        ]);

        $manager1 = User::create([
            'nama'     => 'Manager 1',
            'email'    => 'manager1@coffeshop.com',
            'password' => Hash::make('password'),
            'role'     => 'manager',
            'aktif'    => true,
        ]);

        $manager2 = User::create([
            'nama'     => 'Manager 2',
            'email'    => 'manager2@coffeshop.com',
            'password' => Hash::make('password'),
            'role'     => 'manager',
            'aktif'    => true,
        ]);

        $spv1 = User::create([
            'nama'     => 'Supervisor 1.1',
            'email'    => 'spv1.1@coffeshop.com',
            'password' => Hash::make('password'),
            'role'     => 'supervisor',
            'aktif'    => true,
        ]);

        $spv2 = User::create([
            'nama'     => 'Supervisor 2.1',
            'email'    => 'spv2.1@coffeshop.com',
            'password' => Hash::make('password'),
            'role'     => 'supervisor',
            'aktif'    => true,
        ]);

        $kasir1 = User::create([
            'nama'     => 'Kasir 1.1',
            'email'    => 'kasir1.1@coffeshop.com',
            'password' => Hash::make('password'),
            'role'     => 'kasir',
            'aktif'    => true,
        ]);

        $kasir2 = User::create([
            'nama'     => 'Kasir 2.1',
            'email'    => 'kasir2.1@coffeshop.com',
            'password' => Hash::make('password'),
            'role'     => 'kasir',
            'aktif'    => true,
        ]);

        echo "======== Data user berhasil dibuat ========\n";
    }
}
