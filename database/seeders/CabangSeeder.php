<?php
namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\User;
use Illuminate\Database\Seeder;

class CabangSeeder extends Seeder
{
    public function run()
    {
        $cabang1 = Cabang::create([
            'kode'    => 'CBG-001',
            'nama'    => 'Cabang Pusat',
            'alamat'  => 'Jl. Raya Utama No. 123, Jakarta',
            'telepon' => '021-1234567',
            'aktif'   => true,
        ]);

        $cabang2 = Cabang::create([
            'kode'    => 'CBG-002',
            'nama'    => 'Cabang Bandung',
            'alamat'  => 'Jl. Braga No. 45, Bandung',
            'telepon' => '022-9876543',
            'aktif'   => true,
        ]);

        $cabang3 = Cabang::create([
            'kode'    => 'CBG-003',
            'nama'    => 'Cabang Surabaya',
            'alamat'  => 'Jl. Tunjungan No. 78, Surabaya',
            'telepon' => '031-5555555',
            'aktif'   => true,
        ]);

        $manager1 = User::where('email', 'manager1@coffeshop.com')->first();
        $manager2 = User::where('email', 'manager2@coffeshop.com')->first();
        $spv1     = User::where('email', 'spv1.1@coffeshop.com')->first();
        $spv2     = User::where('email', 'spv2.1@coffeshop.com')->first();
        $kasir1   = User::where('email', 'kasir1.1@coffeshop.com')->first();
        $kasir2   = User::where('email', 'kasir2.1@coffeshop.com')->first();

        $manager1->cabang()->attach([$cabang1->id, $cabang2->id]);
        $manager2->cabang()->attach($cabang3->id);
        $spv1->cabang()->attach($cabang1->id);
        $kasir1->cabang()->attach($cabang1->id);
        $spv2->cabang()->attach($cabang2->id);
        $kasir2->cabang()->attach($cabang2->id);

        echo "======== Data cabang berhasil dibuat dan user berhasil ditugaskan ========\n";
    }
}
