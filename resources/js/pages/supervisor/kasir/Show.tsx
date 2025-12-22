import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import supervisor from '@/routes/supervisor';
import { Head, Link } from '@inertiajs/react';

interface Props {
  kasir: {
    id: number;
    name: string;
    email: string;
    aktif: boolean;
    cabang?: Array<{ id: number; kode?: string; nama?: string }>;
  };
}

export default function KasirShow({ kasir }: Props) {
  return (
    <AppLayout title={`Kasir #${kasir?.id ?? ''}`}>
      <Head title={`Kasir #${kasir?.id ?? ''}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Detail Kasir</h1>
        <div className="rounded-md border p-4">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <div className="text-sm text-muted-foreground">Nama</div>
              <div className="font-medium">{kasir.name}</div>
            </div>
            <div>
              <div className="text-sm text-muted-foreground">Email</div>
              <div className="font-medium">{kasir.email}</div>
            </div>
            <div>
              <div className="text-sm text-muted-foreground">Status</div>
              <div className="font-medium">{kasir.aktif ? 'Aktif' : 'Nonaktif'}</div>
            </div>
            <div>
              <div className="text-sm text-muted-foreground">Cabang</div>
              <div className="font-medium">
                {(kasir.cabang ?? []).length > 0 ? (kasir.cabang ?? []).map((c) => c.nama ?? c.kode ?? String(c.id)).join(', ') : '-'}
              </div>
            </div>
          </div>
        </div>

        <div className="flex gap-2">
          <Button asChild>
            <Link href={supervisor.kasir.edit(kasir.id)}>Edit</Link>
          </Button>
          <Button asChild variant="secondary">
            <Link href={supervisor.kasir.index()}>Kembali</Link>
          </Button>
        </div>
      </div>
    </AppLayout>
  );
}
