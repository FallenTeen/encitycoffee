import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosProdukList(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Produk', href: '/pos/produk' }, { title: 'List', href: '/pos/produk' }]}> 
      <Head title="POS - Produk List" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Produk - Daftar</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}