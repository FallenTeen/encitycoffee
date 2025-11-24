import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

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
      <Breadcrumbs
        breadcrumbs={[
          { title: 'Admin', href: '/admin/dashboard' },
          { title: 'Cabang', href: '/admin/cabang' },
          { title: `#${cabang.id}`, href: `/admin/cabang/${cabang.id}` },
        ]}
      />

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Kode</div>
          <div className="mt-1 text-xl font-semibold">{cabang.kode}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Nama</div>
          <div className="mt-1 text-xl font-semibold">{cabang.nama}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm lg:col-span-2">
          <div className="text-sm font-medium text-muted-foreground">Alamat</div>
          <div className="mt-1 text-base">{cabang.alamat}</div>
        </div>
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          <div className="text-sm font-medium text-muted-foreground">Status</div>
          <div className="mt-1 text-xl font-semibold">
            {cabang.status ? 'Aktif' : 'Nonaktif'}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}