import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

interface AdminProps {
  user: { name: string };
  totalRevenue: number;
  cabangCount: number;
  userCount: number;
}

export default function Admin({ user, totalRevenue, cabangCount, userCount }: AdminProps) {
  return (
    <AppLayout title="Admin Dashboard">
      <Head title="Admin Dashboard" />
      <Breadcrumbs
        breadcrumbs={[
          { title: 'Dashboard', href: '/' },
          { title: 'Admin', href: '/' },
        ]}
      />
      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Total Revenue</div>
          <div className="mt-2 text-2xl font-bold">Rp {totalRevenue.toLocaleString('id-ID')}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Jumlah Cabang</div>
          <div className="mt-2 text-2xl font-bold">{cabangCount}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Jumlah Pengguna</div>
          <div className="mt-2 text-2xl font-bold">{userCount}</div>
        </div>
      </div>
    </AppLayout>
  );
}