import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

interface Produk {
  id: number;
  sku: string;
  nama: string;
  deskripsi?: string | null;
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
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
  };

  return (
    <AppLayout title={`Produk: ${produk?.nama ?? ''}`}>
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

        <div className="grid gap-6 md:grid-cols-2">
          <div className="rounded-md border p-4">
            <dl className="space-y-3 text-sm">
              <div className="flex justify-between gap-4">
                <dt className="w-32 text-muted-foreground">Nama</dt>
                <dd className="flex-1 text-right md:text-left">{produk?.nama}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="w-32 text-muted-foreground">Kategori</dt>
                <dd className="flex-1 text-right md:text-left">{produk?.kategori?.nama ?? '-'}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="w-32 text-muted-foreground">Tipe</dt>
                <dd className="flex-1 text-right md:text-left capitalize">{produk?.tipe}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="w-32 text-muted-foreground">Satuan dasar</dt>
                <dd className="flex-1 text-right md:text-left">{produk?.satuan_dasar}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="w-32 text-muted-foreground">Harga modal</dt>
                <dd className="flex-1 text-right md:text-left">{formatHarga(produk?.harga_modal)}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="w-32 text-muted-foreground">Harga jual</dt>
                <dd className="flex-1 text-right md:text-left">{formatHarga(produk?.harga_jual)}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="w-32 text-muted-foreground">Kalibrasi</dt>
                <dd className="flex-1 text-right md:text-left">
                  {produk?.perlu_kalibrasi ? 'Perlu kalibrasi' : 'Tidak perlu kalibrasi'}
                </dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="w-32 text-muted-foreground">Status</dt>
                <dd className="flex-1 text-right md:text-left">
                  <span className="inline-flex items-center rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                    {produk?.aktif ? 'Aktif' : 'Nonaktif'}
                  </span>
                </dd>
              </div>
            </dl>
          </div>

          <div className="rounded-md border p-4">
            <h2 className="text-sm font-semibold">Deskripsi</h2>
            <div className="mt-2 text-sm text-muted-foreground whitespace-pre-line">
              {produk?.deskripsi && produk.deskripsi.trim() !== '' ? produk.deskripsi : 'Belum ada deskripsi.'}
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
