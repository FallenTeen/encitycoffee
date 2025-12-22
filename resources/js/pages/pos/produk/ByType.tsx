import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function PosProdukByType(props: any) {
  const tipe = props?.tipe ?? '-';
  return (
    <AppLayout>
      <Head title={`POS - Produk Tipe ${String(tipe)}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Produk - {String(tipe)}</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}
