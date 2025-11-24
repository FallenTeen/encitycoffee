import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function KategoriEdit({ kategori }: any) {
  return (
    <AppLayout title={`Edit Kategori - ${kategori?.nama ?? ''}`}>
      <Head title="Produk - Edit Kategori" />
      <div className="rounded-md border p-4">Form placeholder edit kategori produk.</div>
    </AppLayout>
  );
}