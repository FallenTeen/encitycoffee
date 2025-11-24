import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosStokLowStock(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Stok', href: '/pos/stok' }, { title: 'Stok Rendah', href: '/pos/stok/rendah' }]}> 
      <Head title="POS - Stok Rendah" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Stok Rendah</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}