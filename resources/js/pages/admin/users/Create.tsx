import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Props {
  cabangs: any;
}

export default function AdminUsersCreate({ cabangs }: Props) {
  return (
    <AppLayout title="Tambah User">
      <Head title="Admin - Tambah User" />
      <div className="space-y-4">
        <div className="rounded-md border p-4">Form placeholder untuk nama, email, role, status, cabang, password.</div>
      </div>
    </AppLayout>
  );
}