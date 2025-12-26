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
  image_path?: string | null;
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
  const { data, setData, post, processing, errors } = useForm({
    _method: 'PUT',
    kategori_id: produk?.kategori_id ? String(produk.kategori_id) : '',
    sku: produk?.sku ?? '',
    nama: produk?.nama ?? '',
    deskripsi: produk?.deskripsi ?? '',
    image: null as File | null,
    hapus_gambar: false,
    tipe: produk?.tipe ?? '',
    satuan_dasar: produk?.satuan_dasar ?? '',
    harga_modal: produk?.harga_modal !== undefined && produk?.harga_modal !== null ? String(produk.harga_modal) : '',
    harga_jual: produk?.harga_jual !== undefined && produk?.harga_jual !== null ? String(produk.harga_jual) : '',
    perlu_kalibrasi: Boolean(produk?.perlu_kalibrasi),
    aktif: Boolean(produk?.aktif),
  });

  const [skuStatus, setSkuStatus] = useState<'idle' | 'checking' | 'taken' | 'available'>('idle');
  const [skuLabel, setSkuLabel] = useState(produk?.nama ?? '');
  const [previewImage, setPreviewImage] = useState<string | null>(null);
  const skuCheckTimeout = useRef<number | null>(null);

  const currentImageUrl = produk?.image_path ? `/storage/${produk.image_path}` : null;

  const checkSkuAvailability = (value: string) => {
    if (!value) {
      setSkuStatus('idle');
      return;
    }
    setSkuStatus('checking');
    const params = new URLSearchParams();
    params.set('sku', value);
    if (produk?.id) {
      params.set('exclude_id', String(produk.id));
    }
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
    } else if (produk?.kategori_id) {
      params.set('kategori_id', String(produk.kategori_id));
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

  const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0] ?? null;
    setData('image', file);
    if (file) {
      setData('hapus_gambar', false);
      const reader = new FileReader();
      reader.onloadend = () => {
        setPreviewImage(reader.result as string);
      };
      reader.readAsDataURL(file);
    } else {
      setPreviewImage(null);
    }
  };

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Produk', href: '/produk' },
        { title: `Edit ${produk?.nama ?? ''}`, href: `/produk/${produk?.id ?? ''}/edit` },
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
                post(`/produk/${produk.id}`);
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
                  }}
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

              <div className="space-y-1 md:col-span-2">
                <Label htmlFor="image">Gambar Produk</Label>
                <Input
                  id="image"
                  type="file"
                  accept="image/png,image/jpeg,image/jpg,image/webp"
                  onChange={handleImageChange}
                />
                <p className="text-xs text-muted-foreground">
                  Format: JPG, PNG, WebP. Maksimal 2MB.
                </p>
                <InputError message={errors.image as string} />
                
                {/* Preview gambar */}
                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                  {currentImageUrl && !data.hapus_gambar && !previewImage && (
                    <div className="space-y-2">
                      <p className="text-xs font-medium text-muted-foreground">Gambar saat ini:</p>
                      <img
                        src={currentImageUrl}
                        alt="Gambar produk saat ini"
                        className="h-40 w-full rounded-md border object-cover"
                      />
                      <div className="flex items-center gap-2">
                        <Checkbox
                          id="hapus_gambar"
                          checked={data.hapus_gambar}
                          onCheckedChange={(value) => setData('hapus_gambar', Boolean(value))}
                        />
                        <Label htmlFor="hapus_gambar" className="text-sm font-normal">
                          Hapus gambar saat ini
                        </Label>
                      </div>
                    </div>
                  )}
                  
                  {previewImage && (
                    <div className="space-y-2">
                      <p className="text-xs font-medium text-muted-foreground">Preview gambar baru:</p>
                      <img
                        src={previewImage}
                        alt="Preview gambar baru"
                        className="h-40 w-full rounded-md border object-cover"
                      />
                    </div>
                  )}
                </div>
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
                  step="0.01"
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
                  step="0.01"
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
                  <Checkbox 
                    checked={data.aktif} 
                    onCheckedChange={(value) => setData('aktif', Boolean(value))} 
                  />
                  <span className="text-sm">{data.aktif ? 'Aktif' : 'Nonaktif'}</span>
                </div>
                <InputError message={errors.aktif as string} />
              </div>

              <div className="flex items-center gap-2 pt-2 md:col-span-2">
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
                <div className="mt-2 text-sm text-muted-foreground">
                  Belum ada stok tercatat untuk produk ini.
                </div>
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