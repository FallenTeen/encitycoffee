import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function KategoriIndex({ kategori }: any) {
  return (
    <AppLayout title="Kategori Produk">
      <Head title="Produk - Kategori" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Kategori Produk</h1>
        <div className="rounded-md border p-4">Total: {kategori?.total ?? kategori?.data?.length ?? 0}</div>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(kategori, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}