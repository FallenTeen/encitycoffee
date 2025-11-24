import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function KasirEdit({ kasir }: any) {
  return (
    <AppLayout title={`Edit Kasir - ${kasir?.name ?? ''}`}>
      <Head title="Supervisor - Edit Kasir" />
      <div className="space-y-4">
        <div className="rounded-md border p-4">Form placeholder edit data kasir.</div>
      </div>
    </AppLayout>
  );
}