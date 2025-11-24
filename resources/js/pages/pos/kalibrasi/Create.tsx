import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosKalibrasiCreate(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Kalibrasi', href: '/pos/kalibrasi' }, { title: 'Create', href: '/pos/kalibrasi/create' }]}> 
      <Head title="POS - Kalibrasi Create" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Kalibrasi - Buat</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}