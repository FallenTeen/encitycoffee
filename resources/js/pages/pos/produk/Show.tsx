import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosProdukShow(props: any) {
  const id = (props?.produk?.id ?? props?.id ?? '');
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Produk', href: '/pos/produk' }, { title: `#${id}`, href: `/pos/produk/${id}` }]}> 
      <Head title="POS - Produk Detail" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Produk - Detail</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}