import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';

interface Kategori {
  id: number;
  nama: string;
  slug: string;
  deskripsi?: string | null;
}

interface Props {
  kategori: Kategori;
}

export default function KategoriEdit({ kategori }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    nama: kategori?.nama ?? '',
    slug: kategori?.slug ?? '',
    deskripsi: kategori?.deskripsi ?? '',
  });

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Kategori Produk', href: '/produk/kategori' },
        { title: kategori?.nama ?? 'Edit Kategori', href: `/produk/kategori/${kategori?.id ?? ''}/edit` },
      ]}
    >
      <Head title="Produk - Edit Kategori" />
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">Edit Kategori Produk</h1>
            <div className="text-sm text-muted-foreground">{kategori?.nama ?? ''}</div>
          </div>
          <Button asChild variant="secondary">
            <Link href="/produk/kategori">Kembali</Link>
          </Button>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-2"
            onSubmit={(e) => {
              e.preventDefault();
              if (!kategori?.id) return;
              put(`/produk/kategori/${kategori.id}`);
            }}
          >
            <div className="space-y-1">
              <Label htmlFor="nama">Nama Kategori</Label>
              <Input
                id="nama"
                value={data.nama}
                onChange={(e) => setData('nama', e.target.value)}
                placeholder="Contoh: Minuman Kopi"
              />
              <InputError message={errors.nama as string} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="slug">Slug</Label>
              <Input
                id="slug"
                value={data.slug}
                onChange={(e) => setData('slug', e.target.value)}
                placeholder="contoh: minuman-kopi"
              />
              <InputError message={errors.slug as string} />
            </div>

            <div className="space-y-1 md:col-span-2">
              <Label htmlFor="deskripsi">Deskripsi</Label>
              <textarea
                id="deskripsi"
                className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                value={data.deskripsi}
                onChange={(e) => setData('deskripsi', e.target.value)}
                placeholder="Deskripsi kategori (opsional)"
              />
              <InputError message={errors.deskripsi as string} />
            </div>

            <div className="md:col-span-2 flex items-center gap-2 pt-2">
              <Button type="submit" disabled={processing}>
                Simpan Perubahan
              </Button>
              <Button type="button" variant="secondary" asChild>
                <Link href="/produk/kategori">Batal</Link>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
