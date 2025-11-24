import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function StokRendah({ stoks }: any) {
  return (
    <AppLayout title="Stok Rendah">
      <Head title="Stok - Rendah" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Stok Rendah</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(stoks, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}