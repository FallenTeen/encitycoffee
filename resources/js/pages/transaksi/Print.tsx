import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function TransaksiPrint({ transaksi }: any) {
  return (
    <AppLayout title={`Print Struk #${transaksi?.id ?? ''}`}>
      <Head title={`Print Struk #${transaksi?.id ?? ''}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Li Liu Ba</h1>
        <div className="rounded-md border p-4">
          <p>ID: {transaksi?.id}</p>
          <p>Shift: {transaksi?.shift?.id}</p>
          <p>Status: {transaksi?.status}</p>
        </div>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(transaksi, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}
