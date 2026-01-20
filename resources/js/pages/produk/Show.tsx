import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';

interface Produk {
  id: number;
  sku: string;
  nama: string;
  varian?: string | null;
  deskripsi?: string | null;
  image_path?: string | null;
  tipe: string;
  satuan_dasar: string;
  harga_modal: number | string;
  harga_jual: number | string;
  perlu_kalibrasi?: boolean | number | null;
  aktif: boolean;
  kategori?: { id: number; nama: string };
}

interface Props {
  produk: Produk;
}

export default function ProdukShow({ produk }: Props) {
  const formatHarga = (value: number | string) => {
    const num = typeof value === 'string' ? Number(value) : value;
    if (Number.isNaN(num)) return '-';
    return new Intl.NumberFormat('id-ID', { 
      style: 'currency', 
      currency: 'IDR', 
      maximumFractionDigits: 0 
    }).format(num);
  };

  return (
    <AppLayout 
      breadcrumbs={[
        { title: 'Produk', href: '/produk' },
        { title: produk?.nama ?? '', href: `/produk/${produk?.id ?? ''}` }
      ]}
    >
      <Head title={`Produk: ${produk?.nama ?? ''}`} />
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">Detail Produk</h1>
            <div className="text-sm text-muted-foreground">SKU {produk?.sku}</div>
          </div>
          <div className="flex gap-2">
            <Button asChild variant="secondary">
              <Link href="/produk">Kembali</Link>
            </Button>
            {produk?.id && (
              <Button asChild>
                <Link href={`/produk/${produk.id}/edit`}>Edit</Link>
              </Button>
            )}
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          {/* Gambar Produk */}
          {produk?.image_path && (
            <Card>
              <CardHeader>
                <CardTitle className="text-sm font-semibold">Gambar Produk</CardTitle>
              </CardHeader>
              <CardContent>
                <img
                  src={`/storage/${produk.image_path}`}
                  alt={produk.nama}
                  className="w-full rounded-md border object-cover"
                />
              </CardContent>
            </Card>
          )}

          {/* Informasi Produk */}
          <Card className={produk?.image_path ? 'lg:col-span-2' : 'lg:col-span-3'}>
            <CardHeader>
              <CardTitle className="text-sm font-semibold">Informasi Produk</CardTitle>
            </CardHeader>
            <CardContent>
              <dl className="grid gap-3 text-sm md:grid-cols-2">
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">Nama</dt>
                  <dd className="font-medium">{produk?.nama}</dd>
                </div>
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">Varian</dt>
                  <dd>{produk?.varian ?? '-'}</dd>
                </div>
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">SKU</dt>
                  <dd className="font-mono text-xs">{produk?.sku}</dd>
                </div>
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">Kategori</dt>
                  <dd>{produk?.kategori?.nama ?? '-'}</dd>
                </div>
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">Tipe</dt>
                  <dd className="capitalize">{produk?.tipe}</dd>
                </div>
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">Satuan Dasar</dt>
                  <dd>{produk?.satuan_dasar}</dd>
                </div>
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">Status</dt>
                  <dd>
                    <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                      {produk?.aktif ? 'Aktif' : 'Nonaktif'}
                    </span>
                  </dd>
                </div>
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">Harga Modal</dt>
                  <dd className="font-medium">{formatHarga(produk?.harga_modal)}</dd>
                </div>
                <div className="flex flex-col gap-1">
                  <dt className="text-muted-foreground">Harga Jual</dt>
                  <dd className="font-medium">{formatHarga(produk?.harga_jual)}</dd>
                </div>
                <div className="flex flex-col gap-1 md:col-span-2">
                  <dt className="text-muted-foreground">Kalibrasi</dt>
                  <dd>
                    {produk?.perlu_kalibrasi ? (
                      <span className="inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                        Perlu kalibrasi
                      </span>
                    ) : (
                      <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                        Tidak perlu kalibrasi
                      </span>
                    )}
                  </dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          {/* Deskripsi - full width */}
          <Card className="lg:col-span-3">
            <CardHeader>
              <CardTitle className="text-sm font-semibold">Deskripsi</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="whitespace-pre-line text-sm text-muted-foreground">
                {produk?.deskripsi && produk.deskripsi.trim() !== '' 
                  ? produk.deskripsi 
                  : 'Belum ada deskripsi.'}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
