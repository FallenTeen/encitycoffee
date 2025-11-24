import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosLaporanShiftSummary(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Laporan', href: '/pos/laporan' }, { title: 'Ringkasan Shift', href: '/pos/laporan/shift-summary' }]}> 
      <Head title="POS - Ringkasan Shift" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Laporan - Ringkasan Shift</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}