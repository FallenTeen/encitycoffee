import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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
      <div className="grid gap-6 lg:grid-cols-3">
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Transaksi Bermasalah
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">{problematic}</div>
              </CardContent>
          </Card>
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Selisih Kas
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">
                      Rp {kasDifference.toLocaleString('id-ID')}
                  </div>
              </CardContent>
          </Card>
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Serah Terima Tertunda
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-2xl font-semibold">
                      {pendingHandovers}
                  </div>
              </CardContent>
          </Card>
      </div>
    </AppLayout>
  );
}
