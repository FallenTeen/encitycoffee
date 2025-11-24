import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function ProdukShow({ produk }: any) {
  return (
    <AppLayout title={`Produk: ${produk?.nama ?? ''}`}>
      <Head title={`Produk: ${produk?.nama ?? ''}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Detail Produk</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(produk, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}