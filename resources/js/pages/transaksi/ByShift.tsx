import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function TransaksiByShift({ shift, transaksis }: any) {
  return (
    <AppLayout title={`Transaksi Shift #${shift?.id ?? ''}`}>
      <Head title={`Transaksi Shift #${shift?.id ?? ''}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Transaksi per Shift</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(transaksis, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}