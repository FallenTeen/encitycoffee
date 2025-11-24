import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Props { activeShifts: any[] }

export default function MonitoringShift({ activeShifts }: Props) {
  return (
    <AppLayout title="Monitoring Shift">
      <Head title="Supervisor - Monitoring Shift" />
      <div className="space-y-4">
        <div className="rounded-md border p-4">Realtime cards placeholder. Active: {activeShifts?.length ?? 0}</div>
      </div>
    </AppLayout>
  );
}