import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosLaporanUserPerformance(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Laporan', href: '/pos/laporan' }, { title: 'Kinerja User', href: '/pos/laporan/kinerja-user' }]}> 
      <Head title="POS - Kinerja User" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Kinerja User</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}