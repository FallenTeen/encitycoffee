import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function PosShiftsClose(props: any) {
  return (
    <AppLayout>
      <Head title="POS - Tutup Shift" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Tutup Shift</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}
