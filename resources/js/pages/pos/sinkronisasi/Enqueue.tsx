import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosSinkEnqueue(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Sinkronisasi', href: '/pos/sinkronisasi' }, { title: 'Enqueue', href: '/pos/sinkronisasi/enqueue' }]}> 
      <Head title="POS - Sinkronisasi Enqueue" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Antrikan Sinkronisasi</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}