import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Props {
  user: any;
  cabangs: any;
}

export default function AdminUsersEdit({ user, cabangs }: Props) {
  return (
    <AppLayout title={`Edit User - ${user?.name ?? ''}`}> 
      <Head title="Admin - Edit User" />
      <div className="space-y-4">
        <div className="rounded-md border p-4">Form placeholder edit data user dan assign cabang.</div>
      </div>
    </AppLayout>
  );
}