import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Props {
  users: any;
  cabangs: any;
}

export default function AdminUsersIndex({ users, cabangs }: Props) {
  return (
    <AppLayout title="Manajemen User">
      <Head title="Admin - Users" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Users</h1>
        <div className="text-sm text-muted-foreground">Total: {users?.total ?? users?.length ?? 0}</div>
        <div className="rounded-md border">
          <div className="p-4">Table placeholder. Filter by role, status, search.</div>
        </div>
      </div>
    </AppLayout>
  );
}