import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function StokMutasi({ stokEtalaseId, mutasi }: any) {
  return (
    <AppLayout title={`Riwayat Mutasi Stok #${stokEtalaseId}`}> 
      <Head title={`Riwayat Mutasi Stok #${stokEtalaseId}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Riwayat Mutasi</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(mutasi, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}