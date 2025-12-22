import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Cabang {
  id: number;
  kode: string;
  nama: string;
  alamat: string;
  status: boolean;
}

interface Props {
  cabang: Cabang;
}

export default function Show({ cabang }: Props) {
  return (
    <AppLayout title={`Detail Cabang ${cabang.nama}`}>
      <Head title={`Detail Cabang ${cabang.nama}`} />
      <div className="grid gap-6 lg:grid-cols-2">
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Kode
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-xl font-semibold">{cabang.kode}</div>
              </CardContent>
          </Card>
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Nama
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-xl font-semibold">{cabang.nama}</div>
              </CardContent>
          </Card>
          <Card className="lg:col-span-2">
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Alamat
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-sm">{cabang.alamat}</div>
              </CardContent>
          </Card>
          <Card>
              <CardHeader>
                  <CardTitle className="text-sm font-medium text-muted-foreground">
                      Status
                  </CardTitle>
              </CardHeader>
              <CardContent>
                  <div className="text-xl font-semibold">
                      {cabang.status ? 'Aktif' : 'Nonaktif'}
                  </div>
              </CardContent>
          </Card>
      </div>
    </AppLayout>
  );
}
