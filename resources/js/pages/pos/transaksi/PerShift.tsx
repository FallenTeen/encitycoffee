import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosTransaksiPerShift(props: any) {
  const id = props?.shift?.id ?? props?.id ?? '';
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Transaksi', href: '/pos/transaksi' }, { title: `Shift #${id}`, href: `/pos/transaksi/shift/${id}` }]}> 
      <Head title="POS - Transaksi per Shift" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Transaksi per Shift</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}