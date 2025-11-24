import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function AdminSystemLogs({ entries = [] }: { entries?: Array<any> }) {
  return (
    <AppLayout title="System Logs">
      <Head title="Admin - System Logs" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">System Logs</h1>
        <p className="text-sm text-muted-foreground">This is a stub. Future work: tail storage/logs.</p>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(entries, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}