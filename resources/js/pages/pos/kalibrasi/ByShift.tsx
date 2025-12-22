import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function PosKalibrasiByShift(props: any) {
  const id = (props?.shift?.id ?? props?.id ?? '');
  return (
    <AppLayout>
      <Head title="POS - Kalibrasi per Shift" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Kalibrasi - Per Shift</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}
