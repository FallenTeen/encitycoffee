import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function PosKalibrasiCreate(props: any) {
  return (
    <AppLayout>
      <Head title="POS - Kalibrasi Create" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Kalibrasi - Buat</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}
