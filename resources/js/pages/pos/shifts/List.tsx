import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosShiftsList(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Shift', href: '/pos/shifts' }, { title: 'List', href: '/pos/shifts' }]}> 
      <Head title="POS - Shift List" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Daftar Shift</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}