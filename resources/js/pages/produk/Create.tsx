import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Head, Link, useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

interface Kategori {
  id: number;
  nama: string;
}

interface Props {
  kategori: Kategori[];
  tipe_options: string[];
  satuan_options: string[];
}

export default function ProdukCreate({ kategori, tipe_options, satuan_options }: Props) {
  const { data, setData, post, processing, errors, progress } = useForm({
    kategori_id: '',
    sku: '',
    nama: '',
    kelompok_nama: '',
    varian: '',
    deskripsi: '',
    image: null as File | null,
    tipe: '',
    satuan_dasar: '',
    harga_modal: '',
    harga_jual: '',
    perlu_kalibrasi: false,
    aktif: true,
  });

  const [skuStatus, setSkuStatus] = useState<'idle' | 'checking' | 'taken' | 'available'>('idle');
  const [skuLabel, setSkuLabel] = useState('');
  const skuCheckTimeout = useRef<number | null>(null);

  const checkSkuAvailability = (value: string) => {
    if (!value) {
      setSkuStatus('idle');
      return;
    }
    setSkuStatus('checking');
    const params = new URLSearchParams();
    params.set('sku', value);
    fetch(`/produk/check-sku?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
      },
    })
      .then(async (res) => {
        if (!res.ok) return;
        const json = (await res.json()) as { exists?: boolean };
        setSkuStatus(json.exists ? 'taken' : 'available');
      })
      .catch(() => {
        setSkuStatus('idle');
      });
  };

  const handleGenerateSku = () => {
    const params = new URLSearchParams();
    if (data.tipe) {
      params.set('tipe', String(data.tipe));
    }
    if (data.kategori_id) {
      params.set('kategori_id', String(data.kategori_id));
    }
    if (skuLabel) {
      params.set('nama', skuLabel);
    }

    fetch(`/produk/sku-suggest?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
      },
    })
      .then(async (res) => {
        if (!res.ok) return;
        const json = (await res.json()) as { sku?: string };
        if (json.sku) {
          setData('sku', json.sku);
          checkSkuAvailability(json.sku);
        }
      })
      .catch(() => {
        return;
      });
  };

  return (
    <AppLayout breadcrumbs={[{ title: 'Produk', href: '/produk' }, { title: 'Tambah Produk', href: '/produk/create' }]}>
      <Head title="Tambah Produk" />
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">Tambah Produk</h1>
            <div className="text-sm text-muted-foreground">Isi detail produk baru.</div>
          </div>
          <Button asChild variant="secondary">
            <Link href="/produk">Kembali</Link>
          </Button>
        </div>

        <div className="rounded-md border p-4">
          <form
            className="grid grid-cols-1 gap-4 md:grid-cols-2"
            onSubmit={(e) => {
              e.preventDefault();
              post('/produk');
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
              <div className="flex flex-col gap-2 sm:flex-row">
                <Input
                  id="sku"
                  className="sm:flex-1"
                  value={data.sku}
                  onChange={(e) => {
                    const value = e.target.value;
                    setData('sku', value);
                    if (skuCheckTimeout.current) {
                      window.clearTimeout(skuCheckTimeout.current);
                    }
                    skuCheckTimeout.current = window.setTimeout(() => {
                      checkSkuAvailability(value);
                    }, 400);
                  }}
                  placeholder="Contoh: PROD-001"
                />
                <Tooltip>
                  <TooltipTrigger asChild>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      className="whitespace-nowrap"
                      onClick={handleGenerateSku}
                    >
                      Generate SKU
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Generate SKU otomatis sesuai standar dan cek keunikan.</p>
                  </TooltipContent>
                </Tooltip>
              </div>
              <InputError message={errors.sku as string} />
              <div className="mt-2 space-y-1 text-xs">
                <div className="flex flex-col gap-1 md:flex-row md:items-center md:gap-2">
                  <span>Label untuk generator:</span>
                  <div className="flex flex-1 items-center gap-2">
                    <Input
                      value={skuLabel}
                      onChange={(e) => setSkuLabel(e.target.value)}
                      placeholder="Label, misal nama singkat"
                    />
                  </div>
                </div>
                <div className="mt-1">
                  {skuStatus === 'taken' && (
                    <span className="text-destructive">SKU ini sudah dipakai</span>
                  )}
                  {skuStatus === 'available' && (
                    <span className="text-emerald-600">SKU tersedia</span>
                  )}
                </div>
              </div>
            </div>

            <div className="space-y-1">
              <Label htmlFor="nama">Nama Produk</Label>
              <Input
                id="nama"
                value={data.nama}
                onChange={(e) => {
                  const value = e.target.value;
                  setData('nama', value);
                  if (!skuLabel) {
                    setSkuLabel(value);
                  }
                  if (!data.kelompok_nama) {
                    setData('kelompok_nama', value);
                  }
                }}
                placeholder="Nama produk"
              />
              <InputError message={errors.nama as string} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="kelompok_nama">Kelompok Nama</Label>
              <Input
                id="kelompok_nama"
                value={data.kelompok_nama}
                onChange={(e) => setData('kelompok_nama', e.target.value)}
                placeholder="Contoh: Americano"
              />
              <InputError message={errors.kelompok_nama as string} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="varian">Varian</Label>
              <Input
                id="varian"
                value={data.varian}
                onChange={(e) => setData('varian', e.target.value)}
                placeholder="Contoh: Hot, Ice"
              />
              <InputError message={errors.varian as string} />
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
                placeholder="Deskripsi produk (opsional)"
              />
              <InputError message={errors.deskripsi as string} />
            </div>

            <div className="space-y-1">
              <Label htmlFor="image">Gambar Produk</Label>
              <Input
                id="image"
                type="file"
                accept="image/png,image/jpeg,image/jpg,image/webp"
                onChange={(e) => {
                  const file = e.target.files?.[0] ?? null;
                  setData('image', file);
                }}
              />
              <p className="text-xs text-muted-foreground">
                Format: JPG, PNG, WebP. Maksimal 2MB.
              </p>
              <InputError message={errors.image as string} />
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
                Simpan
              </Button>
              <Button type="button" variant="secondary" asChild>
                <Link href="/produk">Batal</Link>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
