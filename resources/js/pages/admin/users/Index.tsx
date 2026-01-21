import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import admin from '@/routes/admin';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo } from 'react';
import Swal from 'sweetalert2';

interface Props {
  users: {
    data: Array<{
      id: number;
      name: string;
      email: string;
      role: string;
      aktif: boolean;
      cabang?: Array<{ id: number; kode?: string; nama?: string }>;
      created_at?: string;
    }>;
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  cabangs: Array<{ id: number; kode: string; nama: string | null }>;
  filter_aktif: {
    search: string;
    role: string;
    aktif: string;
    cabang_id: string | number;
    per_page: number;
  };
}

export default function AdminUsersIndex({ users, cabangs, filter_aktif }: Props) {
  const { data, setData, get, processing, errors } = useForm({
    search: filter_aktif?.search ?? '',
    role: filter_aktif?.role ?? '',
    aktif: filter_aktif?.aktif ?? '',
    cabang_id: filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '',
    per_page: String(filter_aktif?.per_page ?? 15),
  });

  const cabangOptions = useMemo(
    () =>
      [{ id: 0, kode: '-', nama: 'Semua cabang' }].concat(
        (cabangs ?? []).map((c) => ({ id: c.id, kode: c.kode, nama: c.nama ?? '-' })),
      ),
    [cabangs],
  );

  const submit = () => {
    get(admin.users.index().url, {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    });
  };

  return (
    <AppLayout title="Manajemen User">
      <Head title="Admin - Users" />
      <div className="space-y-6">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Pengelolaan Pengguna</h1>
            <div className="text-sm text-muted-foreground">Total: {users?.total ?? 0}</div>
          </div>
          <Link
            href={admin.users.create()}
            className="inline-flex items-center justify-center rounded bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
          >
            Tambah Pengguna
          </Link>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-5"
            onSubmit={(e) => {
              e.preventDefault();
              submit();
            }}
          >
            <div className="space-y-1">
              <Label htmlFor="search">Cari</Label>
              <Input
                id="search"
                value={data.search}
                onChange={(e) => setData('search', e.target.value)}
                placeholder="Nama atau email"
              />
              <InputError message={errors.search} />
            </div>
            <div className="space-y-1">
              <Label>Role</Label>
              <Select value={data.role} onValueChange={(v) => setData('role', v === '__all__' ? '' : v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Semua role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__all__">Semua role</SelectItem>
                  <SelectItem value="it_support">IT Support</SelectItem>
                  <SelectItem value="manager">Manager</SelectItem>
                  <SelectItem value="supervisor">Supervisor</SelectItem>
                  <SelectItem value="kasir">Kasir</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={errors.role} />
            </div>
            <div className="space-y-1">
              <Label>Status</Label>
              <Select value={data.aktif} onValueChange={(v) => setData('aktif', v === '__all__' ? '' : v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Semua status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__all__">Semua status</SelectItem>
                  <SelectItem value="1">Aktif</SelectItem>
                  <SelectItem value="0">Nonaktif</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={errors.aktif} />
            </div>
            <div className="space-y-1">
              <Label>Cabang</Label>
              <Select value={data.cabang_id} onValueChange={(v) => setData('cabang_id', v === '0' ? '' : v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Semua cabang" />
                </SelectTrigger>
                <SelectContent>
                  {cabangOptions.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {c.kode} - {c.nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={errors.cabang_id} />
            </div>

            <div className="space-y-1">
              <Label>Tampil</Label>
              <Select value={data.per_page} onValueChange={(v) => setData('per_page', v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Per halaman" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="15">15</SelectItem>
                  <SelectItem value="25">25</SelectItem>
                  <SelectItem value="50">50</SelectItem>
                  <SelectItem value="100">100</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={errors.per_page as unknown as string} />
            </div>

            <div className="md:col-span-5 flex items-center gap-2">
              <Button type="submit" disabled={processing}>
                Terapkan Filter
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => {
                  setData({ search: '', role: '', aktif: '', cabang_id: '', per_page: '15' });
                  router.get(admin.users.index().url, {}, { preserveScroll: true, replace: true });
                }}
              >
                Reset
              </Button>
            </div>
          </form>
        </div>

        <div className="rounded-md border">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="py-2 px-4">Nama</th>
                  <th className="py-2 px-4">Email</th>
                  <th className="py-2 px-4">Role</th>
                  <th className="py-2 px-4">Cabang</th>
                  <th className="py-2 px-4">Status</th>
                  <th className="py-2 px-4">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {(users?.data ?? []).map((u) => (
                  <tr key={u.id} className="border-b last:border-0">
                    <td className="py-2 px-4 font-medium">{u.name}</td>
                    <td className="py-2 px-4">{u.email}</td>
                    <td className="py-2 px-4">{u.role}</td>
                    <td className="py-2 px-4">
                      {(u.cabang ?? []).length > 0 ? (u.cabang ?? []).map((c) => c.nama ?? c.kode ?? String(c.id)).join(', ') : '-'}
                    </td>
                    <td className="py-2 px-4">
                      <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-muted-foreground">
                        {u.aktif ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </td>
                    <td className="py-2 px-4">
                      <div className="flex items-center gap-3">
                        <Link href={admin.users.edit(u.id)} className="text-primary underline">
                          Edit
                        </Link>
                        <button
                          type="button"
                          className="text-destructive underline"
                          onClick={async () => {
                            const result = await Swal.fire({
                              title: 'Hapus Pengguna',
                              text: `Apakah Anda yakin ingin menghapus "${u.name}"?`,
                              icon: 'warning',
                              showCancelButton: true,
                              confirmButtonText: 'Hapus',
                              cancelButtonText: 'Batal',
                              reverseButtons: true,
                              focusCancel: true,
                            });

                            if (!result.isConfirmed) {
                              return;
                            }

                            router.delete(admin.users.destroy(u.id).url, {
                              preserveScroll: true,
                            });
                          }}
                        >
                          Hapus
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
                {(users?.data ?? []).length === 0 && (
                  <tr>
                    <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                      Belum ada data pengguna.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t p-4 text-sm">
            <div>
              Halaman {users?.current_page ?? 1} / {users?.last_page ?? 1}
            </div>
            <div className="flex gap-2">
              <Button asChild variant="secondary" disabled={!users?.prev_page_url}>
                <Link href={users?.prev_page_url ?? admin.users.index()}>Sebelumnya</Link>
              </Button>
              <Button asChild variant="secondary" disabled={!users?.next_page_url}>
                <Link href={users?.next_page_url ?? admin.users.index()}>Berikutnya</Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
