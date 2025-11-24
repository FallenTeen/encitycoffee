import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function StokKadaluarsa({ stoks, days }: any) {
  return (
    <AppLayout title="Stok Mendekati Kadaluarsa">
      <Head title="Stok - Kadaluarsa" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Mendekati Kadaluarsa (≤ {days} hari)</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(stoks, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}