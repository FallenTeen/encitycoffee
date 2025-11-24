import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function KasirShow({ kasir }: any) {
  return (
    <AppLayout title={`Kasir #${kasir?.id ?? ''}`}>
      <Head title={`Kasir #${kasir?.id ?? ''}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Detail Kasir</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(kasir, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}