import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function KasirIndex({ kasirs }: any) {
  return (
    <AppLayout title="Daftar Kasir">
      <Head title="Supervisor - Kasir" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Kasir</h1>
        <div className="rounded-md border p-4">Total: {kasirs?.total ?? kasirs?.length ?? 0}</div>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(kasirs, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}