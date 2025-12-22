import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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
      <div className="grid gap-6 lg:grid-cols-3">
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Total Revenue
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">
                      Rp {totalRevenue.toLocaleString('id-ID')}
                  </div>
              </CardContent>
          </Card>
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Jumlah Cabang
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">{cabangCount}</div>
              </CardContent>
          </Card>
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Jumlah Pengguna
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">{userCount}</div>
              </CardContent>
          </Card>
      </div>
    </AppLayout>
  );
}
