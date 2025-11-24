import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

interface ManagerProps {
  user?: { name: string };
  branchPerformance?: Array<{ cabang: string; omzet: number }>;
  lowStockCount?: number;
  activeShifts?: number;
}

export default function Manager({ user, branchPerformance = [], lowStockCount = 0, activeShifts = 0 }: ManagerProps) {
  return (
    <AppLayout title="Manager Dashboard">
      <Head title="Manager Dashboard" />
      <Breadcrumbs
        breadcrumbs={[
          { title: 'Dashboard', href: '/' },
          { title: 'Manager', href: '/' },
        ]}
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Shifts Aktif</div>
          <div className="mt-2 text-2xl font-bold">{activeShifts}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Item Stok Rendah</div>
          <div className="mt-2 text-2xl font-bold">{lowStockCount}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Manager</div>
          <div className="mt-2 text-2xl font-bold">{user?.name ?? '-'}</div>
        </div>
      </div>

      <div className="mt-8 rounded-lg border bg-card p-6 shadow-sm">
        <div className="mb-4 text-sm font-medium text-muted-foreground">Performa Cabang (Ringkas)</div>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {branchPerformance.length === 0 ? (
            <div className="text-sm text-muted-foreground">Belum ada data.</div>
          ) : (
            branchPerformance.map((b) => (
              <div key={b.cabang} className="rounded-md border p-4">
                <div className="text-sm text-muted-foreground">{b.cabang}</div>
                <div className="mt-1 text-xl font-semibold">Rp {b.omzet.toLocaleString('id-ID')}</div>
              </div>
            ))
          )}
        </div>
      </div>
    </AppLayout>
  );
}