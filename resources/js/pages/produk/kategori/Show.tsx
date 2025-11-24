import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function KategoriShow({ kategori, produk_list }: any) {
  return (
    <AppLayout title={`Kategori: ${kategori?.nama ?? ''}`}>
      <Head title={`Kategori: ${kategori?.nama ?? ''}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Detail Kategori</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(kategori, null, 2)}</pre>
        <div className="rounded-md border p-4">
          <h2 className="text-lg font-semibold mb-2">Produk dalam kategori</h2>
          <pre className="bg-muted p-4 rounded">{JSON.stringify(produk_list, null, 2)}</pre>
        </div>
      </div>
    </AppLayout>
  );
}