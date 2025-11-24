import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function PosAuthLogin(props: any) {
  return (
    <AppLayout breadcrumbs={[{ title: 'POS', href: '/pos' }, { title: 'Auth', href: '/pos/auth/login' }]}>
      <Head title="POS - Login" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">POS Login</h1>
        <pre className="bg-muted p-4 rounded">{JSON.stringify(props, null, 2)}</pre>
      </div>
    </AppLayout>
  );
}