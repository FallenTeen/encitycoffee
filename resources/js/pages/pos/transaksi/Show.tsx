import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosTransaksiShow(props: any) {
  const id = (props?.transaksi?.id ?? props?.id ?? '');
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Transaksi', href: '/pos/transaksi' }, { title: `#${id}`, href: `/pos/transaksi/${id}` }]}> 
      <Head title="POS - Detail Transaksi" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Transaksi - Detail</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}