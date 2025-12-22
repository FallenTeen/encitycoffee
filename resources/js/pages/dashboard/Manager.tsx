import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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
      <div className="grid gap-6 lg:grid-cols-3">
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Shifts Aktif
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">{activeShifts}</div>
              </CardContent>
          </Card>
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Item Stok Rendah
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">{lowStockCount}</div>
              </CardContent>
          </Card>
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Manager
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">
                      {user?.name ?? '-'}
                  </div>
              </CardContent>
          </Card>
      </div>

      <Card className="mt-8">
          <CardHeader>
              <CardTitle className="text-sm font-medium text-muted-foreground">
                  Performa Cabang (Ringkas)
              </CardTitle>
          </CardHeader>
          <CardContent>
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                  {branchPerformance.length === 0 ? (
                      <div className="text-sm text-muted-foreground">
                          Belum ada data.
                      </div>
                  ) : (
                      branchPerformance.map((b) => (
                          <Card key={b.cabang} className="py-0">
                              <CardContent className="px-6 py-4">
                                  <div className="text-sm text-muted-foreground">
                                      {b.cabang}
                                  </div>
                                  <div className="mt-1 text-xl font-semibold">
                                      Rp {b.omzet.toLocaleString('id-ID')}
                                  </div>
                              </CardContent>
                          </Card>
                      ))
                  )}
              </div>
          </CardContent>
      </Card>
    </AppLayout>
  );
}
