import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function PosLaporanStockReport(props: any) {
  return (
    <AppLayout>
      <Head title="POS - Laporan Stok" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Laporan Stok</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}
