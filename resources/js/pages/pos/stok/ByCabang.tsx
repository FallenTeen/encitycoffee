import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosStokByCabang(props: any) {
  const id = props?.cabang_id ?? props?.cabang?.id ?? '';
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Stok', href: '/pos/stok' }, { title: `Cabang #${id}`, href: `/pos/stok/cabang/${id}` }]}> 
      <Head title="POS - Stok per Cabang" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Stok - Per Cabang</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}