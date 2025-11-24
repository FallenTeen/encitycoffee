import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

interface KasirProps {
  user?: { name: string };
  currentShift?: { id: number; waktu_buka: string; saldo_awal: number } | null;
  lastTransactions?: Array<{ id: number; total: number; created_at: string }>;
}

export default function Kasir({ user, currentShift = null, lastTransactions = [] }: KasirProps) {
  return (
    <AppLayout title="Dashboard Kasir">
      <Head title="Dashboard Kasir" />
      <Breadcrumbs
        breadcrumbs={[
          { title: 'Dashboard', href: '/' },
          { title: 'Kasir', href: '/' },
        ]}
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Kasir</div>
          <div className="mt-2 text-2xl font-bold">{user?.name ?? '-'}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm lg:col-span-2">
          <div className="text-sm font-medium text-muted-foreground">Shift Saat Ini</div>
          {currentShift ? (
            <div className="mt-2">
              <div className="text-sm">ID Shift: {currentShift.id}</div>
              <div className="text-sm">Waktu Buka: {currentShift.waktu_buka}</div>
              <div className="text-sm">Saldo Awal: Rp {currentShift.saldo_awal.toLocaleString('id-ID')}</div>
            </div>
          ) : (
            <div className="mt-2 text-sm text-muted-foreground">Belum ada shift aktif.</div>
          )}
        </div>
      </div>

      <div className="mt-8 rounded-lg border bg-card p-6 shadow-sm">
        <div className="mb-4 text-sm font-medium text-muted-foreground">Transaksi Terakhir</div>
        {lastTransactions.length === 0 ? (
          <div className="text-sm text-muted-foreground">Belum ada transaksi.</div>
        ) : (
          <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            {lastTransactions.map((t) => (
              <div key={t.id} className="rounded-md border p-4">
                <div className="text-sm text-muted-foreground">#{t.id}</div>
                <div className="mt-1 text-xl font-semibold">Rp {t.total.toLocaleString('id-ID')}</div>
                <div className="text-xs text-muted-foreground">{t.created_at}</div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* View-only: tanpa tombol aksi */}
    </AppLayout>
  );
}