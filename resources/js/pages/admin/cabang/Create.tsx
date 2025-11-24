import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function AdminCabangCreate() {
  return (
    <AppLayout title="Tambah Cabang">
      <Head title="Admin - Tambah Cabang" />
      <div className="rounded-md border p-4">Form placeholder untuk kode, nama, alamat, telepon, status.</div>
    </AppLayout>
  );
}