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
            'nama'    => 'Cabang Purwokerto',
            'alamat'  => 'Jl. Contoh No. 1, Purwokerto',
            'telepon' => '0281-000000',
            'aktif'   => true,
        ]);

        $cabang2 = Cabang::create([
            'kode'    => 'CBG-002',
            'nama'    => 'Cabang Ajibarang',
            'alamat'  => 'Jl. Contoh No. 2, Ajibarang',
            'telepon' => '0281-000001',
            'aktif'   => true,
        ]);

        $manager1 = User::where('email', 'manager1@coffeshop.com')->first();
        $spv1     = User::where('email', 'spv1.1@coffeshop.com')->first();
        $kasir1   = User::where('email', 'kasir1.1@coffeshop.com')->first();
        $kasir2   = User::where('email', 'kasir2.1@coffeshop.com')->first();

        if ($manager1) {
            $manager1->cabang()->syncWithoutDetaching([$cabang1->id]);
        }
        if ($spv1) {
            $spv1->cabang()->syncWithoutDetaching([$cabang1->id]);
        }
        if ($kasir1) {
            $kasir1->cabang()->syncWithoutDetaching([$cabang1->id]);
        }
        if ($kasir2) {
            $kasir2->cabang()->syncWithoutDetaching([$cabang1->id]);
        }

        echo "======== Data cabang berhasil dibuat dan user berhasil ditugaskan ========\n";
    }
}
