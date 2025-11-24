import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Props { cabang: any }

export default function AdminCabangEdit({ cabang }: Props) {
  return (
    <AppLayout title={`Edit Cabang - ${cabang?.nama ?? ''}`}>
      <Head title="Admin - Edit Cabang" />
      <div className="rounded-md border p-4">Form placeholder untuk edit data cabang.</div>
    </AppLayout>
  );
}