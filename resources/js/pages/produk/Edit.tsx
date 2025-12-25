import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';

interface Kategori {
  id: number;
  nama: string;
}

interface SatuanStok {
  id: number;
  cabang?: { id: number; nama?: string | null };
  jumlah: number | string;
}

interface Produk {
  id: number;
  kategori_id: number;
  sku: string;
  nama: string;
  deskripsi?: string | null;
  tipe: string;
  satuan_dasar: string;
  harga_modal: number | string;
  harga_jual: number | string;
  perlu_kalibrasi?: boolean | number | null;
  aktif: boolean;
}

interface Props {
  produk: Produk;
  kategori: Kategori[];
  tipe_options: string[];
  satuan_options: string[];
  stok_tersedia: SatuanStok[];
}

export default function ProdukEdit({ produk, kategori, tipe_options, satuan_options, stok_tersedia }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    kategori_id: produk?.kategori_id ? String(produk.kategori_id) : '',
    sku: produk?.sku ?? '',
    nama: produk?.nama ?? '',
    deskripsi: produk?.deskripsi ?? '',
    tipe: produk?.tipe ?? '',
    satuan_dasar: produk?.satuan_dasar ?? '',
    harga_modal: produk?.harga_modal !== undefined && produk?.harga_modal !== null ? String(produk.harga_modal) : '',
    harga_jual: produk?.harga_jual !== undefined && produk?.harga_jual !== null ? String(produk.harga_jual) : '',
    perlu_kalibrasi: Boolean(produk?.perlu_kalibrasi),
    aktif: Boolean(produk?.aktif),
  });

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Produk', href: '/produk' },
        { title: `Edit #${produk?.id ?? ''}`, href: `/produk/${produk?.id ?? ''}/edit` },
      ]}
    >
      <Head title="Edit Produk" />
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">Edit Produk</h1>
            <div className="text-sm text-muted-foreground">{produk?.nama ?? ''}</div>
          </div>
          <Button asChild variant="secondary">
            <Link href="/produk">Kembali</Link>
          </Button>
        </div>

        <div className="grid gap-6 lg:grid-cols-[minmax(0,2fr),minmax(0,1fr)]">
          <div className="rounded-md border p-4">
            <form
              className="grid grid-cols-1 gap-4 md:grid-cols-2"
              onSubmit={(e) => {
                e.preventDefault();
                if (!produk?.id) return;
                put(`/produk/${produk.id}`);
              }}
            >
              <div className="space-y-1">
                <Label htmlFor="kategori_id">Kategori</Label>
                <select
                  id="kategori_id"
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                  value={data.kategori_id}
                  onChange={(e) => setData('kategori_id', e.target.value)}
                >
                  <option value="">Pilih kategori</option>
                  {(kategori ?? []).map((k) => (
                    <option key={k.id} value={k.id}>
                      {k.nama}
                    </option>
                  ))}
                </select>
                <InputError message={errors.kategori_id as string} />
              </div>

              <div className="space-y-1">
                <Label htmlFor="sku">SKU</Label>
                <Input
                  id="sku"
                  value={data.sku}
                  onChange={(e) => setData('sku', e.target.value)}
                />
                <InputError message={errors.sku as string} />
              </div>

              <div className="space-y-1">
                <Label htmlFor="nama">Nama Produk</Label>
                <Input
                  id="nama"
                  value={data.nama}
                  onChange={(e) => setData('nama', e.target.value)}
                />
                <InputError message={errors.nama as string} />
              </div>

              <div className="space-y-1">
                <Label htmlFor="tipe">Tipe</Label>
                <select
                  id="tipe"
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                  value={data.tipe}
                  onChange={(e) => setData('tipe', e.target.value)}
                >
                  <option value="">Pilih tipe</option>
                  {(tipe_options ?? []).map((t) => (
                    <option key={t} value={t}>
                      {t.charAt(0).toUpperCase() + t.slice(1)}
                    </option>
                  ))}
                </select>
                <InputError message={errors.tipe as string} />
              </div>

              <div className="space-y-1 md:col-span-2">
                <Label htmlFor="deskripsi">Deskripsi</Label>
                <textarea
                  id="deskripsi"
                  className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                  value={data.deskripsi}
                  onChange={(e) => setData('deskripsi', e.target.value)}
                />
                <InputError message={errors.deskripsi as string} />
              </div>

              <div className="space-y-1">
                <Label htmlFor="satuan_dasar">Satuan Dasar</Label>
                <select
                  id="satuan_dasar"
                  className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                  value={data.satuan_dasar}
                  onChange={(e) => setData('satuan_dasar', e.target.value)}
                >
                  <option value="">Pilih satuan</option>
                  {(satuan_options ?? []).map((s) => (
                    <option key={s} value={s}>
                      {s}
                    </option>
                  ))}
                </select>
                <InputError message={errors.satuan_dasar as string} />
              </div>

              <div className="space-y-1">
                <Label htmlFor="harga_modal">Harga Modal</Label>
                <Input
                  id="harga_modal"
                  type="number"
                  min="0"
                  value={data.harga_modal}
                  onChange={(e) => setData('harga_modal', e.target.value)}
                />
                <InputError message={errors.harga_modal as string} />
              </div>

              <div className="space-y-1">
                <Label htmlFor="harga_jual">Harga Jual</Label>
                <Input
                  id="harga_jual"
                  type="number"
                  min="0"
                  value={data.harga_jual}
                  onChange={(e) => setData('harga_jual', e.target.value)}
                />
                <InputError message={errors.harga_jual as string} />
              </div>

              <div className="space-y-1">
                <Label>Pengaturan Kalibrasi</Label>
                <div className="flex items-center gap-2 pt-2">
                  <Checkbox
                    checked={data.perlu_kalibrasi}
                    onCheckedChange={(value) => setData('perlu_kalibrasi', Boolean(value))}
                  />
                  <span className="text-sm">Perlu kalibrasi (khusus tipe beans)</span>
                </div>
                <InputError message={errors.perlu_kalibrasi as string} />
              </div>

              <div className="space-y-1">
                <Label>Status</Label>
                <div className="flex items-center gap-2 pt-2">
                  <Checkbox checked={data.aktif} onCheckedChange={(value) => setData('aktif', Boolean(value))} />
                  <span className="text-sm">{data.aktif ? 'Aktif' : 'Nonaktif'}</span>
                </div>
                <InputError message={errors.aktif as string} />
              </div>

              <div className="md:col-span-2 flex items-center gap-2 pt-2">
                <Button type="submit" disabled={processing}>
                  Simpan Perubahan
                </Button>
                <Button type="button" variant="secondary" asChild>
                  <Link href="/produk">Batal</Link>
                </Button>
              </div>
            </form>
          </div>

          <div className="space-y-4">
            <div className="rounded-md border p-4">
              <h2 className="text-sm font-semibold">Ringkasan Stok Tersedia</h2>
              {(stok_tersedia ?? []).length === 0 && (
                <div className="mt-2 text-sm text-muted-foreground">Belum ada stok tercatat untuk produk ini.</div>
              )}
              {(stok_tersedia ?? []).length > 0 && (
                <div className="mt-3 space-y-2 text-sm">
                  {(stok_tersedia ?? []).map((s) => (
                    <div key={s.id} className="flex items-center justify-between rounded border px-3 py-2 text-xs">
                      <div>
                        <div className="font-medium">{s.cabang?.nama ?? 'Tanpa cabang'}</div>
                      </div>
                      <div className="font-mono">{s.jumlah}</div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
