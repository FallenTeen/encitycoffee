import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosShiftsDetail(props: any) {
  const id = (props?.shift?.id ?? props?.id ?? '');
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Shift', href: '/pos/shifts' }, { title: `#${id}`, href: `/pos/shifts/${id}` }]}> 
      <Head title="POS - Detail Shift" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Detail Shift</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}