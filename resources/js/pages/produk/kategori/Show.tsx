import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

interface Kategori {
  id: number;
  nama: string;
  slug: string;
  deskripsi?: string | null;
  produk_count?: number;
}

interface ProdukItem {
  id: number;
  nama: string;
  sku?: string | null;
  tipe?: string | null;
}

interface ProdukPaginator {
  data: ProdukItem[];
  total: number;
}

interface Props {
  kategori: Kategori;
  produk_list: ProdukPaginator;
}

export default function KategoriShow({ kategori, produk_list }: Props) {
  const produk = produk_list?.data ?? [];

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Kategori Produk', href: '/produk/kategori' },
        { title: kategori?.nama ?? 'Detail Kategori', href: `/produk/kategori/${kategori?.id ?? ''}` },
      ]}
    >
      <Head title={`Kategori: ${kategori?.nama ?? ''}`} />
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">Detail Kategori</h1>
            <div className="text-sm text-muted-foreground">
              Pengelompokan produk: {kategori?.nama ?? ''}
            </div>
          </div>
          <div className="flex gap-2">
            <Button asChild variant="outline" size="sm">
              <Link href="/produk/kategori">Kembali</Link>
            </Button>
            <Button asChild size="sm">
              <Link href={`/produk/kategori/${kategori?.id ?? ''}/edit`}>Edit</Link>
            </Button>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-[minmax(0,2fr),minmax(0,1fr)]">
          <div className="rounded-md border p-4 space-y-3">
            <div className="flex items-center justify-between gap-3">
              <div>
                <div className="text-sm font-semibold">{kategori?.nama ?? '-'}</div>
                <div className="text-xs text-muted-foreground">
                  Slug: <span className="font-mono">{kategori?.slug ?? '-'}</span>
                </div>
              </div>
              <Badge variant="outline">
                {kategori?.produk_count ?? 0} produk
              </Badge>
            </div>
            <div className="text-sm">
              {kategori?.deskripsi ? (
                <p className="whitespace-pre-line">{kategori.deskripsi}</p>
              ) : (
                <span className="text-muted-foreground">Belum ada deskripsi.</span>
              )}
            </div>
          </div>

          <div className="rounded-md border p-4 space-y-2 text-sm">
            <div className="font-semibold">Ringkasan</div>
            <div className="flex items-center justify-between">
              <span>Total produk</span>
              <span className="font-medium">{produk_list?.total ?? produk.length ?? 0}</span>
            </div>
          </div>
        </div>

        <div className="rounded-md border">
          <div className="border-b px-4 py-3 text-sm font-semibold">
            Produk dalam kategori
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="px-4 py-2">Nama</th>
                  <th className="px-4 py-2">SKU</th>
                  <th className="px-4 py-2">Tipe</th>
                </tr>
              </thead>
              <tbody>
                {produk.map((p) => (
                  <tr key={p.id} className="border-b last:border-0">
                    <td className="px-4 py-2">
                      <div className="font-medium">{p.nama}</div>
                    </td>
                    <td className="px-4 py-2 font-mono text-xs">{p.sku ?? '-'}</td>
                    <td className="px-4 py-2 capitalize text-xs">{p.tipe ?? '-'}</td>
                  </tr>
                ))}
                {produk.length === 0 && (
                  <tr>
                    <td
                      colSpan={3}
                      className="px-4 py-8 text-center text-muted-foreground"
                    >
                      Belum ada produk dalam kategori ini.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
