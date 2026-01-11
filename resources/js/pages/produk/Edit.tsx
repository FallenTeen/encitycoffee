import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
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
  kelompok_nama?: string | null;
  varian?: string | null;
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
    kelompok_nama: produk?.kelompok_nama ?? '',
    varian: produk?.varian ?? '',
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
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({});
  const [satuanMode, setSatuanMode] = useState<'gram' | 'liter' | 'custom' | ''>(
    produk?.satuan_dasar === 'gram'
      ? 'gram'
      : produk?.satuan_dasar === 'liter'
      ? 'liter'
      : produk?.satuan_dasar
      ? 'custom'
      : ''
  );

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
          <Card>
            <CardHeader>
              <CardTitle>Informasi Produk</CardTitle>
              <CardDescription>
                Perbarui detail produk agar konsisten di semua cabang.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form
                className="grid grid-cols-1 gap-4 md:grid-cols-2"
                onSubmit={(e) => {
                  e.preventDefault();
                  if (!produk?.id) return;

                  const nextErrors: Record<string, string> = {};

                  if (!data.nama.trim()) {
                    nextErrors.nama = 'Nama produk wajib diisi';
                  }

                  if (!data.satuan_dasar.trim()) {
                    nextErrors.satuan_dasar = 'Satuan dasar wajib diisi';
                  }

                  const hargaModal = Number(data.harga_modal);
                  if (!data.harga_modal || Number.isNaN(hargaModal) || hargaModal < 0) {
                    nextErrors.harga_modal = 'Harga modal harus berupa angka dan tidak boleh negatif';
                  }

                  const hargaJual = Number(data.harga_jual);
                  if (!data.harga_jual || Number.isNaN(hargaJual) || hargaJual < 0) {
                    nextErrors.harga_jual = 'Harga jual harus berupa angka dan tidak boleh negatif';
                  }

                  if (skuStatus === 'taken') {
                    nextErrors.sku = 'SKU ini sudah dipakai';
                  }

                  if (Object.keys(nextErrors).length > 0) {
                    setClientErrors(nextErrors);
                    return;
                  }

                  setClientErrors({});
                  post(`/produk/${produk.id}`);
                }}
              >
                <div className="space-y-1">
                  <Label htmlFor="kategori_id">Kategori</Label>
                  <Select
                    value={data.kategori_id}
                    onValueChange={(value) => setData('kategori_id', value)}
                  >
                    <SelectTrigger id="kategori_id">
                      <SelectValue placeholder="Pilih kategori" />
                    </SelectTrigger>
                    <SelectContent>
                      {(kategori ?? []).map((k) => (
                        <SelectItem key={k.id} value={String(k.id)}>
                          {k.nama}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
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
                      placeholder="SKU unik produk"
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
                  <InputError message={clientErrors.sku || (errors.sku as string)} />
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
                  <InputError message={clientErrors.nama || (errors.nama as string)} />
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
                  <Select
                    value={data.tipe}
                    onValueChange={(value) => setData('tipe', value)}
                  >
                    <SelectTrigger id="tipe">
                      <SelectValue placeholder="Pilih tipe" />
                    </SelectTrigger>
                    <SelectContent>
                      {(tipe_options ?? []).map((t) => (
                        <SelectItem key={t} value={t}>
                          {t.charAt(0).toUpperCase() + t.slice(1)}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
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
                  <Select
                    value={
                      satuanMode ||
                      (data.satuan_dasar === 'gram' || data.satuan_dasar === 'liter'
                        ? (data.satuan_dasar as 'gram' | 'liter')
                        : data.satuan_dasar
                        ? 'custom'
                        : '')
                    }
                    onValueChange={(value) => {
                      if (value === 'gram' || value === 'liter') {
                        setSatuanMode(value);
                        setData('satuan_dasar', value);
                      } else if (value === 'custom') {
                        setSatuanMode('custom');
                        setData('satuan_dasar', data.satuan_dasar || '');
                      } else {
                        setSatuanMode('');
                        setData('satuan_dasar', '');
                      }
                    }}
                  >
                    <SelectTrigger id="satuan_dasar">
                      <SelectValue placeholder="Pilih satuan" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="gram">Gram</SelectItem>
                      <SelectItem value="liter">Liter</SelectItem>
                      <SelectItem value="custom">Input manual</SelectItem>
                    </SelectContent>
                  </Select>
                  {satuanMode === 'custom' && (
                    <div className="pt-2">
                      <Input
                        value={data.satuan_dasar}
                        onChange={(e) => setData('satuan_dasar', e.target.value)}
                        placeholder="Contoh: pcs, botol"
                      />
                    </div>
                  )}
                  <InputError
                    message={clientErrors.satuan_dasar || (errors.satuan_dasar as string)}
                  />
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
                    placeholder="Contoh: 25000"
                  />
                  <InputError
                    message={clientErrors.harga_modal || (errors.harga_modal as string)}
                  />
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
                    placeholder="Contoh: 35000"
                  />
                  <InputError
                    message={clientErrors.harga_jual || (errors.harga_jual as string)}
                  />
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
            </CardContent>
          </Card>

          <div className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle className="text-sm font-semibold">Ringkasan Stok Tersedia</CardTitle>
              </CardHeader>
              <CardContent>
                {(stok_tersedia ?? []).length === 0 && (
                  <div className="text-sm text-muted-foreground">
                    Belum ada stok tercatat untuk produk ini.
                  </div>
                )}
                {(stok_tersedia ?? []).length > 0 && (
                  <div className="space-y-2 text-sm">
                    {(stok_tersedia ?? []).map((s) => (
                      <div
                        key={s.id}
                        className="flex items-center justify-between rounded border px-3 py-2 text-xs"
                      >
                        <div>
                          <div className="font-medium">{s.cabang?.nama ?? 'Tanpa cabang'}</div>
                        </div>
                        <div className="font-mono">{s.jumlah}</div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
