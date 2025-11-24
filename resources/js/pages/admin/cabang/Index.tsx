import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

interface Props { cabangs: any }

export default function AdminCabangIndex({ cabangs }: Props) {
  return (
    <AppLayout title="Manajemen Cabang">
      <Head title="Admin - Cabang" />
      <div className="space-y-6">
        <h1 className="text-xl font-semibold">Cabang</h1>
        <div className="rounded-md border">
          <div className="p-4">
            <div className="flex justify-between items-center mb-4">
              <div className="text-sm text-muted-foreground">Daftar cabang</div>
              <Link href="/admin/cabang/create" className="inline-flex items-center px-3 py-1.5 text-sm rounded bg-primary text-primary-foreground">
                Tambah Cabang
              </Link>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="text-left border-b">
                    <th className="py-2 pr-4">Kode</th>
                    <th className="py-2 pr-4">Nama</th>
                    <th className="py-2 pr-4">Status</th>
                    <th className="py-2 pr-4">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {(cabangs?.data ?? []).map((cabang: any) => (
                    <tr key={cabang.id} className="border-b last:border-0">
                      <td className="py-2 pr-4 font-mono">{cabang.kode}</td>
                      <td className="py-2 pr-4">{cabang.nama}</td>
                      <td className="py-2 pr-4">
                        <span className="inline-flex items-center px-2 py-0.5 rounded bg-muted text-muted-foreground">
                          {cabang.status ? 'Aktif' : 'Nonaktif'}
                        </span>
                      </td>
                      <td className="py-2 pr-4">
                        <div className="flex gap-2">
                          <Link href={`/admin/cabang/${cabang.id}`} className="underline text-primary">Detail</Link>
                          <Link href={`/admin/cabang/${cabang.id}/edit`} className="underline text-muted-foreground">Edit</Link>
                        </div>
                      </td>
                    </tr>
                  ))}
                  {(cabangs?.data ?? []).length === 0 && (
                    <tr>
                      <td colSpan={4} className="py-6 text-center text-muted-foreground">Belum ada data cabang.</td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}