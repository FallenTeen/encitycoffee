import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface KasirProps {
  user?: { name: string };
  currentShift?: { id: number; waktu_buka: string; saldo_awal: number } | null;
  lastTransactions?: Array<{ id: number; total: number; created_at: string }>;
}

export default function Kasir({ user, currentShift = null, lastTransactions = [] }: KasirProps) {
  return (
    <AppLayout title="Dashboard Kasir">
      <Head title="Dashboard Kasir" />
      <div className="grid gap-6 lg:grid-cols-3">
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Kasir
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">
                      {user?.name ?? '-'}
                  </div>
              </CardContent>
          </Card>
          <Card className="lg:col-span-2">
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Shift Saat Ini
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  {currentShift ? (
                      <div className="space-y-1 text-sm">
                          <div>ID Shift: {currentShift.id}</div>
                          <div>Waktu Buka: {currentShift.waktu_buka}</div>
                          <div>
                              Saldo Awal: Rp{' '}
                              {currentShift.saldo_awal.toLocaleString('id-ID')}
                          </div>
                      </div>
                  ) : (
                      <div className="text-sm text-muted-foreground">
                          Belum ada shift aktif.
                      </div>
                  )}
              </CardContent>
          </Card>
      </div>

      <Card className="mt-8">
          <CardHeader>
              <CardTitle className="text-sm font-medium text-muted-foreground">
                  Transaksi Terakhir
              </CardTitle>
          </CardHeader>
          <CardContent>
              {lastTransactions.length === 0 ? (
                  <div className="text-sm text-muted-foreground">
                      Belum ada transaksi.
                  </div>
              ) : (
                  <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                      {lastTransactions.map((t) => (
                          <Card key={t.id} className="py-0">
                              <CardContent className="px-6 py-4">
                                  <div className="text-sm text-muted-foreground">
                                      #{t.id}
                                  </div>
                                  <div className="mt-1 text-xl font-semibold">
                                      Rp {t.total.toLocaleString('id-ID')}
                                  </div>
                                  <div className="text-xs text-muted-foreground">
                                      {t.created_at}
                                  </div>
                              </CardContent>
                          </Card>
                      ))}
                  </div>
              )}
          </CardContent>
      </Card>
    </AppLayout>
  );
}
