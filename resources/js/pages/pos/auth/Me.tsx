import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosAuthMe(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Auth', href: '/pos/auth/me' }]}> 
      <Head title="POS - Me" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">POS Me</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}