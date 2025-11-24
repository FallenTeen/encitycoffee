import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosStokMutasiHistory(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Stok', href: '/pos/stok' }, { title: 'Riwayat Mutasi', href: '/pos/stok/mutasi' }]}> 
      <Head title="POS - Riwayat Mutasi" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Riwayat Mutasi Stok</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}