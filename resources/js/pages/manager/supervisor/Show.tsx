import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import manager from '@/routes/manager';
import { Head, Link } from '@inertiajs/react';

interface Props {
  supervisor: {
    id: number;
    name: string;
    email: string;
    aktif: boolean;
    cabang?: Array<{ id: number; kode?: string; nama?: string }>;
  };
}

export default function ManagerSupervisorShow({ supervisor }: Props) {
  return (
    <AppLayout title={`Supervisor #${supervisor?.id ?? ''}`}>
      <Head title={`Supervisor #${supervisor?.id ?? ''}`} />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Detail Supervisor</h1>

        <div className="rounded-md border p-4">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <div className="text-sm text-muted-foreground">Nama</div>
              <div className="font-medium">{supervisor.name}</div>
            </div>
            <div>
              <div className="text-sm text-muted-foreground">Email</div>
              <div className="font-medium">{supervisor.email}</div>
            </div>
            <div>
              <div className="text-sm text-muted-foreground">Status</div>
              <div className="font-medium">{supervisor.aktif ? 'Aktif' : 'Nonaktif'}</div>
            </div>
            <div>
              <div className="text-sm text-muted-foreground">Cabang</div>
              <div className="font-medium">
                {(supervisor.cabang ?? []).length > 0
                  ? (supervisor.cabang ?? []).map((c) => c.nama ?? c.kode ?? String(c.id)).join(', ')
                  : '-'}
              </div>
            </div>
          </div>
        </div>

        <div className="flex gap-2">
          <Button asChild>
            <Link href={manager.supervisor.edit(supervisor.id)}>Edit</Link>
          </Button>
          <Button asChild variant="secondary">
            <Link href={manager.supervisor.index()}>Kembali</Link>
          </Button>
        </div>
      </div>
    </AppLayout>
  );
}

