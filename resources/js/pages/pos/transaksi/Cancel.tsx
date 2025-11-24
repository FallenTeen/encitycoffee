import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosTransaksiCancel(props: any) {
  const id = (props?.transaksi?.id ?? props?.id ?? '');
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Transaksi', href: '/pos/transaksi' }, { title: `Batal #${id}`, href: `/pos/transaksi/${id}/batal` }]}> 
      <Head title="POS - Batalkan Transaksi" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Batalkan Transaksi</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}