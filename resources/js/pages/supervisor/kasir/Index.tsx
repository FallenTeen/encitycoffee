import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import supervisor from '@/routes/supervisor';
import { Head, Link, router, useForm } from '@inertiajs/react';

interface Props {
  kasirs: {
    data: Array<{
      id: number;
      name: string;
      email: string;
      aktif: boolean;
      cabang?: Array<{ id: number; kode?: string; nama?: string }>;
    }>;
    total: number;
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  cabang_list: Array<{ id: number; nama: string }>;
  filter_aktif: { search: string; cabang_id: string | number };
}

export default function KasirIndex({ kasirs, cabang_list, filter_aktif }: Props) {
  const { data, setData, get, processing, errors } = useForm({
    search: filter_aktif?.search ?? '',
    cabang_id: filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '',
  });

  return (
    <AppLayout title="Daftar Kasir">
      <Head title="Supervisor - Kasir" />
      <div className="space-y-6">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Pengelolaan Kasir</h1>
            <div className="text-sm text-muted-foreground">Total: {kasirs?.total ?? 0}</div>
          </div>
          <Link
            href={supervisor.kasir.create()}
            className="inline-flex items-center justify-center rounded bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
          >
            Tambah Kasir
          </Link>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-3"
            onSubmit={(e) => {
              e.preventDefault();
              get(supervisor.kasir.index().url, { preserveState: true, preserveScroll: true, replace: true });
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
              <Label>Cabang</Label>
              <Select value={data.cabang_id} onValueChange={(v) => setData('cabang_id', v === '__all__' ? '' : v)}>
                <SelectTrigger>
                  <SelectValue placeholder="Semua cabang" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__all__">Semua cabang</SelectItem>
                  {(cabang_list ?? []).map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                      {c.nama}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={errors.cabang_id} />
            </div>
            <div className="flex items-end gap-2">
              <Button type="submit" disabled={processing}>
                Terapkan
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => {
                  setData({ search: '', cabang_id: '' });
                  router.get(supervisor.kasir.index().url, {}, { preserveScroll: true, replace: true });
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
                  <th className="py-2 px-4">Cabang</th>
                  <th className="py-2 px-4">Status</th>
                  <th className="py-2 px-4">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {(kasirs?.data ?? []).map((k) => (
                  <tr key={k.id} className="border-b last:border-0">
                    <td className="py-2 px-4 font-medium">{k.name}</td>
                    <td className="py-2 px-4">{k.email}</td>
                    <td className="py-2 px-4">
                      {(k.cabang ?? []).length > 0 ? (k.cabang ?? []).map((c) => c.nama ?? c.kode ?? String(c.id)).join(', ') : '-'}
                    </td>
                    <td className="py-2 px-4">
                      <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-muted-foreground">
                        {k.aktif ? 'Aktif' : 'Nonaktif'}
                      </span>
                    </td>
                    <td className="py-2 px-4">
                      <div className="flex gap-3">
                        <Link href={supervisor.kasir.show(k.id)} className="text-primary underline">
                          Detail
                        </Link>
                        <Link href={supervisor.kasir.edit(k.id)} className="text-muted-foreground underline">
                          Edit
                        </Link>
                        <button
                          type="button"
                          className="text-destructive underline"
                          onClick={() => {
                            const ok = window.confirm(`Hapus kasir ${k.name}?`);
                            if (!ok) return;
                            router.delete(supervisor.kasir.destroy(k.id).url, { preserveScroll: true });
                          }}
                        >
                          Hapus
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
                {(kasirs?.data ?? []).length === 0 && (
                  <tr>
                    <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                      Belum ada data kasir.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t p-4 text-sm">
            <div>
              Halaman {kasirs?.current_page ?? 1} / {kasirs?.last_page ?? 1}
            </div>
            <div className="flex gap-2">
              <Button asChild variant="secondary" disabled={!kasirs?.prev_page_url}>
                <Link href={kasirs?.prev_page_url ?? supervisor.kasir.index()}>Sebelumnya</Link>
              </Button>
              <Button asChild variant="secondary" disabled={!kasirs?.next_page_url}>
                <Link href={kasirs?.next_page_url ?? supervisor.kasir.index()}>Berikutnya</Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
