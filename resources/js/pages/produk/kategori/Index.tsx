import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

interface KategoriItem {
  id: number;
  nama: string;
  slug: string;
  deskripsi?: string | null;
  produk_count?: number;
}

interface KategoriPaginator {
  data: KategoriItem[];
  total: number;
  current_page: number;
  last_page: number;
  prev_page_url: string | null;
  next_page_url: string | null;
}

interface FilterAktif {
  search: string;
  sort_by: 'nama' | 'produk_count' | '';
  sort_dir: 'asc' | 'desc' | '';
}

interface Props {
  kategori: KategoriPaginator;
  filter_aktif?: FilterAktif;
}

export default function KategoriIndex({ kategori, filter_aktif }: Props) {
  const { data, setData, get, processing, errors } = useForm({
    search: filter_aktif?.search ?? '',
    sort_by: filter_aktif?.sort_by ?? 'nama',
    sort_dir: filter_aktif?.sort_dir ?? 'asc',
  });

  const sortedData = useMemo(() => {
    const list = [...(kategori?.data ?? [])];
    const sortBy = data.sort_by || 'nama';
    const dir = data.sort_dir === 'desc' ? -1 : 1;

    return list
      .filter((item) => {
        if (!data.search) return true;
        const q = data.search.toLowerCase();
        return (
          item.nama.toLowerCase().includes(q) ||
          item.slug.toLowerCase().includes(q) ||
          (item.deskripsi ?? '').toLowerCase().includes(q)
        );
      })
      .sort((a, b) => {
        if (sortBy === 'produk_count') {
          const av = a.produk_count ?? 0;
          const bv = b.produk_count ?? 0;
          if (av === bv) return 0;
          return av > bv ? dir : -dir;
        }
        const av = a.nama.toLowerCase();
        const bv = b.nama.toLowerCase();
        if (av === bv) return 0;
        return av > bv ? dir : -dir;
      });
  }, [kategori?.data, data.search, data.sort_by, data.sort_dir]);

  const submit = () => {
    get('/produk/kategori', {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      only: ['kategori', 'filter_aktif'],
    });
  };

  return (
    <AppLayout breadcrumbs={[{ title: 'Kategori Produk', href: '/produk/kategori' }]}>
      <Head title="Produk - Kategori" />
      <div className="space-y-6">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-xl font-semibold">Kategori Produk</h1>
            <div className="text-sm text-muted-foreground">
              Total: {kategori?.total ?? kategori?.data?.length ?? 0}
            </div>
          </div>
          <Button asChild>
            <Link href="/produk/kategori/create">Tambah Kategori</Link>
          </Button>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-4"
            onSubmit={(e) => {
              e.preventDefault();
              submit();
            }}
          >
            <div className="space-y-1">
              <div className="text-sm font-medium">Cari</div>
              <Input
                value={data.search}
                onChange={(e) => setData('search', e.target.value)}
                placeholder="Nama, slug, atau deskripsi"
              />
              <InputError message={errors.search as string} />
            </div>

            <div className="space-y-1">
              <div className="text-sm font-medium">Urutkan Berdasarkan</div>
              <Select
                value={data.sort_by}
                onValueChange={(value) => setData('sort_by', value as FilterAktif['sort_by'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Kolom" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="nama">Nama</SelectItem>
                  <SelectItem value="produk_count">Jumlah Produk</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={errors.sort_by as string} />
            </div>

            <div className="space-y-1">
              <div className="text-sm font-medium">Arah</div>
              <Select
                value={data.sort_dir}
                onValueChange={(value) => setData('sort_dir', value as FilterAktif['sort_dir'])}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Arah" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="asc">A-Z / Kecil ke Besar</SelectItem>
                  <SelectItem value="desc">Z-A / Besar ke Kecil</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={errors.sort_dir as string} />
            </div>

            <div className="flex items-end gap-2">
              <Button type="submit" disabled={processing}>
                Terapkan
              </Button>
              <Button
                type="button"
                variant="secondary"
                onClick={() => {
                  setData({
                    search: '',
                    sort_by: 'nama',
                    sort_dir: 'asc',
                  });
                  router.get('/produk/kategori', {}, { preserveScroll: true, replace: true });
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
                  <th className="px-4 py-2">Nama</th>
                  <th className="px-4 py-2">Slug</th>
                  <th className="px-4 py-2">Deskripsi</th>
                  <th className="px-4 py-2 text-right">Jumlah Produk</th>
                  <th className="px-4 py-2 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody>
                {sortedData.map((k) => (
                  <tr key={k.id} className="border-b last:border-0">
                    <td className="px-4 py-2 font-medium">{k.nama}</td>
                    <td className="px-4 py-2 font-mono text-xs">{k.slug}</td>
                    <td className="px-4 py-2">
                      {k.deskripsi ? (
                        <span className="line-clamp-2 text-xs text-muted-foreground">
                          {k.deskripsi}
                        </span>
                      ) : (
                        <span className="text-xs text-muted-foreground">-</span>
                      )}
                    </td>
                    <td className="px-4 py-2 text-right">{k.produk_count ?? 0}</td>
                    <td className="px-4 py-2 text-center">
                      <div className="flex items-center justify-center gap-3">
                        <Link
                          href={`/produk/kategori/${k.id}`}
                          className="text-primary underline"
                        >
                          Detail
                        </Link>
                        <Link
                          href={`/produk/kategori/${k.id}/edit`}
                          className="text-muted-foreground underline"
                        >
                          Edit
                        </Link>
                        <button
                          type="button"
                          className="text-destructive underline"
                          onClick={() => {
                            const ok = window.confirm(`Hapus kategori ${k.nama}?`);
                            if (!ok) return;
                            router.delete(`/produk/kategori/${k.id}`, {
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
                {sortedData.length === 0 && (
                  <tr>
                    <td
                      colSpan={5}
                      className="px-4 py-8 text-center text-muted-foreground"
                    >
                      Belum ada data kategori.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <div className="flex items-center justify-between border-t p-4 text-sm">
            <div>
              Halaman {kategori?.current_page ?? 1} / {kategori?.last_page ?? 1}
            </div>
            <div className="flex gap-2">
              <Button
                asChild
                variant="secondary"
                disabled={!kategori?.prev_page_url}
              >
                <Link href={kategori?.prev_page_url ?? '/produk/kategori'}>
                  Sebelumnya
                </Link>
              </Button>
              <Button
                asChild
                variant="secondary"
                disabled={!kategori?.next_page_url}
              >
                <Link href={kategori?.next_page_url ?? '/produk/kategori'}>
                  Berikutnya
                </Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
