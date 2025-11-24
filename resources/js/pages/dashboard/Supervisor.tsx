import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

interface SupervisorProps {
  user: { name: string };
  problematic: number;
  kasDifference: number;
  pendingHandovers: number;
}

export default function Supervisor({ problematic, kasDifference, pendingHandovers }: SupervisorProps) {
  return (
    <AppLayout title="Supervisor Dashboard">
      <Head title="Supervisor Dashboard" />
      <Breadcrumbs
        breadcrumbs={[
          { title: 'Dashboard', href: '/' },
          { title: 'Supervisor', href: '/' },
        ]}
      />
      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Transaksi Bermasalah</div>
          <div className="mt-2 text-2xl font-bold">{problematic}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Selisih Kas</div>
          <div className="mt-2 text-2xl font-bold">Rp {kasDifference.toLocaleString('id-ID')}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Serah Terima Tertunda</div>
          <div className="mt-2 text-2xl font-bold">{pendingHandovers}</div>
        </div>
      </div>
    </AppLayout>
  );
}