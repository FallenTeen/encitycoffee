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
import { useEffect, useRef, useState } from 'react';
import { Loader2, Sparkles, Trash2 } from 'lucide-react';

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
  base?: string | null;
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

export default function ProdukEdit({ produk, kategori, stok_tersedia }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    _method: 'PUT',
    kategori_id: produk?.kategori_id ? String(produk.kategori_id) : '',
    nama: produk?.nama ?? '',
    kelompok_nama: produk?.kelompok_nama ?? '',
    varian: produk?.varian ?? '',
    sku: produk?.sku ?? '',
    tipe: produk?.tipe ?? '',
    base: produk?.base ?? '',
    deskripsi: produk?.deskripsi ?? '',
    satuan_dasar: produk?.satuan_dasar ?? '',
    harga_modal: produk?.harga_modal !== undefined && produk?.harga_modal !== null ? String(produk.harga_modal) : '',
    harga_jual: produk?.harga_jual !== undefined && produk?.harga_jual !== null ? String(produk.harga_jual) : '',
    image: null as File | null,
    hapus_gambar: false,
    perlu_kalibrasi: Boolean(produk?.perlu_kalibrasi),
    aktif: Boolean(produk?.aktif),
  });

  const [skuStatus, setSkuStatus] = useState<'idle' | 'checking' | 'taken' | 'available'>('idle');
  const [isGeneratingSku, setIsGeneratingSku] = useState(false);
  const [previewImage, setPreviewImage] = useState<string | null>(null);
  const skuCheckTimeout = useRef<number | null>(null);
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({});
  const [kelompokList, setKelompokList] = useState<string[]>([]);
  const [showKelompokInput, setShowKelompokInput] = useState(false);

  const currentImageUrl = produk?.image_path ? `/storage/${produk.image_path}` : null;

  // Fetch existing kelompok nama
  useEffect(() => {
    fetch('/produk/kelompok-nama', {
      headers: { Accept: 'application/json' },
    })
      .then(async (res) => {
        if (res.ok) {
          const json = await res.json();
          setKelompokList(json.data || []);
        }
      })
      .catch(() => {
        // Silent fail
      });
  }, []);

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
      headers: { Accept: 'application/json' },
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
    if (data.tipe) params.set('tipe', String(data.tipe));
    if (data.kategori_id) params.set('kategori_id', String(data.kategori_id));
    if (data.nama) params.set('nama', String(data.nama));
    if (data.varian) params.set('varian', String(data.varian));

    setIsGeneratingSku(true);
    fetch(`/produk/sku-suggest?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    })
      .then(async (res) => {
        if (!res.ok) throw new Error('Failed to generate SKU');
        const json = (await res.json()) as { sku?: string };
        if (json.sku) {
          setData('sku', json.sku);
          checkSkuAvailability(json.sku);
        }
      })
      .catch(() => {
        // Silent fail
      })
      .finally(() => {
        setIsGeneratingSku(false);
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
            <p className="text-sm text-muted-foreground">{produk?.nama ?? ''}</p>
          </div>
          <Button asChild variant="outline">
            <Link href="/produk">Kembali</Link>
          </Button>
        </div>

        <form
          onSubmit={(e) => {
            e.preventDefault();
            if (!produk?.id) return;

            const nextErrors: Record<string, string> = {};

            if (!data.nama.trim()) nextErrors.nama = 'Nama produk wajib diisi';
            if (!data.satuan_dasar.trim()) nextErrors.satuan_dasar = 'Satuan dasar wajib diisi';

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
          <div className="grid gap-6 lg:grid-cols-[2fr,1fr]">
            {/* Main Form */}
            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Informasi Dasar</CardTitle>
                  <CardDescription>Detail utama produk</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {/* Kategori */}
                  <div className="space-y-2">
                    <Label htmlFor="kategori_id">Kategori Produk *</Label>
                    <Select value={data.kategori_id} onValueChange={(v) => setData('kategori_id', v)}>
                      <SelectTrigger id="kategori_id">
                        <SelectValue placeholder="Pilih kategori" />
                      </SelectTrigger>
                      <SelectContent>
                        {kategori.map((k) => (
                          <SelectItem key={k.id} value={String(k.id)}>
                            {k.nama}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <InputError message={errors.kategori_id as string} />
                  </div>

                  {/* Nama Produk */}
                  <div className="space-y-2">
                    <Label htmlFor="nama">Nama Produk *</Label>
                    <Input
                      id="nama"
                      value={data.nama}
                      onChange={(e) => {
                        const value = e.target.value;
                        setData('nama', value);
                        if (!data.kelompok_nama) {
                          setData('kelompok_nama', value);
                        }
                      }}
                      placeholder="Contoh: Caramel Latte"
                    />
                    <InputError message={clientErrors.nama || (errors.nama as string)} />
                  </div>

                  {/* Kelompok Nama */}
                  <div className="space-y-2">
                    <Label htmlFor="kelompok_nama">Kelompok Nama</Label>
                    {!showKelompokInput && kelompokList.length > 0 ? (
                      <div className="flex gap-2">
                        <Select
                          value={data.kelompok_nama}
                          onValueChange={(v) => {
                            if (v === '__new__') {
                              setShowKelompokInput(true);
                              setData('kelompok_nama', '');
                            } else {
                              setData('kelompok_nama', v);
                            }
                          }}
                        >
                          <SelectTrigger className="flex-1">
                            <SelectValue placeholder="Pilih kelompok atau buat baru" />
                          </SelectTrigger>
                          <SelectContent>
                            {kelompokList.map((k) => (
                              <SelectItem key={k} value={k}>
                                {k}
                              </SelectItem>
                            ))}
                            <SelectItem value="__new__">+ Buat Kelompok Baru</SelectItem>
                          </SelectContent>
                        </Select>
                        <Button
                          type="button"
                          variant="outline"
                          size="icon"
                          onClick={() => setShowKelompokInput(true)}
                        >
                          <Sparkles className="h-4 w-4" />
                        </Button>
                      </div>
                    ) : (
                      <div className="flex gap-2">
                        <Input
                          id="kelompok_nama"
                          value={data.kelompok_nama}
                          onChange={(e) => setData('kelompok_nama', e.target.value)}
                          placeholder="Contoh: Latte"
                        />
                        {kelompokList.length > 0 && (
                          <Button
                            type="button"
                            variant="outline"
                            onClick={() => setShowKelompokInput(false)}
                          >
                            Pilih Existing
                          </Button>
                        )}
                      </div>
                    )}
                    <p className="text-xs text-muted-foreground">
                      Kelompok untuk mengelompokkan produk serupa (opsional)
                    </p>
                    <InputError message={errors.kelompok_nama as string} />
                  </div>

                  {/* Varian */}
                  <div className="space-y-2">
                    <Label htmlFor="varian">Varian</Label>
                    <Select value={data.varian} onValueChange={(v) => setData('varian', v)}>
                      <SelectTrigger id="varian">
                        <SelectValue placeholder="Pilih varian (opsional)" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="">Tidak ada varian</SelectItem>
                        <SelectItem value="Hot">Hot</SelectItem>
                        <SelectItem value="Ice">Ice</SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError message={errors.varian as string} />
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Klasifikasi Produk</CardTitle>
                  <CardDescription>Tipe dan base produk</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  {/* Tipe */}
                  <div className="space-y-2">
                    <Label htmlFor="tipe">Tipe Produk *</Label>
                    <Select value={data.tipe} onValueChange={(v) => setData('tipe', v)}>
                      <SelectTrigger id="tipe">
                        <SelectValue placeholder="Pilih tipe" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="beans">Beans (Biji Kopi)</SelectItem>
                        <SelectItem value="minuman">Minuman (Beverage)</SelectItem>
                        <SelectItem value="snack">Snack (Makanan)</SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError message={errors.tipe as string} />
                  </div>

                  {/* Base */}
                  <div className="space-y-2">
                    <Label htmlFor="base">Base Produk</Label>
                    <Select value={data.base} onValueChange={(v) => setData('base', v)}>
                      <SelectTrigger id="base">
                        <SelectValue placeholder="Pilih base (opsional)" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="">Tidak ada base</SelectItem>
                        <SelectItem value="coffee">Coffee</SelectItem>
                        <SelectItem value="milk">Milk</SelectItem>
                        <SelectItem value="tea">Tea</SelectItem>
                        <SelectItem value="others">Others</SelectItem>
                      </SelectContent>
                    </Select>
                    <p className="text-xs text-muted-foreground">
                      Base ingredient utama produk (untuk minuman)
                    </p>
                    <InputError message={errors.base as string} />
                  </div>

                  {/* SKU */}
                  <div className="space-y-2">
                    <Label htmlFor="sku">SKU (Stock Keeping Unit) *</Label>
                    <div className="flex gap-2">
                      <Input
                        id="sku"
                        className="flex-1 font-mono"
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
                        placeholder="BEV-CLA-HOT-001"
                      />
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Button
                            type="button"
                            variant="outline"
                            onClick={handleGenerateSku}
                            disabled={isGeneratingSku || !data.tipe || !data.nama}
                          >
                            {isGeneratingSku ? (
                              <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                              <Sparkles className="h-4 w-4" />
                            )}
                          </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                          <p>Generate SKU baru</p>
                        </TooltipContent>
                      </Tooltip>
                    </div>
                    {skuStatus === 'taken' && (
                      <p className="text-xs text-destructive">SKU ini sudah dipakai</p>
                    )}
                    {skuStatus === 'available' && (
                      <p className="text-xs text-emerald-600">✓ SKU tersedia</p>
                    )}
                    {skuStatus === 'checking' && (
                      <p className="text-xs text-muted-foreground">Memeriksa ketersediaan...</p>
                    )}
                    <InputError message={clientErrors.sku || (errors.sku as string)} />
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Harga & Satuan</CardTitle>
                  <CardDescription>Informasi harga dan unit</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
                  {/* Satuan Dasar */}
                  <div className="space-y-2">
                    <Label htmlFor="satuan_dasar">Satuan Dasar *</Label>
                    <Select
                      value={data.satuan_dasar}
                      onValueChange={(v) => setData('satuan_dasar', v)}
                    >
                      <SelectTrigger id="satuan_dasar">
                        <SelectValue placeholder="Pilih satuan" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="gram">Gram</SelectItem>
                        <SelectItem value="liter">Liter</SelectItem>
                        <SelectItem value="pcs">Pcs</SelectItem>
                        <SelectItem value="botol">Botol</SelectItem>
                        <SelectItem value="cup">Cup</SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError message={clientErrors.satuan_dasar || (errors.satuan_dasar as string)} />
                  </div>

                  {/* Harga Modal */}
                  <div className="space-y-2">
                    <Label htmlFor="harga_modal">Harga Modal (Rp) *</Label>
                    <Input
                      id="harga_modal"
                      type="number"
                      min="0"
                      step="0.01"
                      value={data.harga_modal}
                      onChange={(e) => setData('harga_modal', e.target.value)}
                      placeholder="25000"
                    />
                    <InputError message={clientErrors.harga_modal || (errors.harga_modal as string)} />
                  </div>

                  {/* Harga Jual */}
                  <div className="space-y-2 md:col-span-2">
                    <Label htmlFor="harga_jual">Harga Jual (Rp) *</Label>
                    <Input
                      id="harga_jual"
                      type="number"
                      min="0"
                      step="0.01"
                      value={data.harga_jual}
                      onChange={(e) => setData('harga_jual', e.target.value)}
                      placeholder="35000"
                    />
                    <InputError message={clientErrors.harga_jual || (errors.harga_jual as string)} />
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Gambar Produk</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  {currentImageUrl && !data.hapus_gambar && !previewImage && (
                    <div className="space-y-2">
                      <img
                        src={currentImageUrl}
                        alt="Gambar produk"
                        className="h-40 w-full rounded-md border object-cover"
                      />
                      <div className="flex items-center gap-2">
                        <Checkbox
                          id="hapus_gambar"
                          checked={data.hapus_gambar}
                          onCheckedChange={(v) => setData('hapus_gambar', Boolean(v))}
                        />
                        <Label htmlFor="hapus_gambar" className="flex items-center gap-2 text-sm font-normal">
                          <Trash2 className="h-3 w-3" />
                          Hapus gambar
                        </Label>
                      </div>
                    </div>
                  )}

                  {previewImage && (
                    <div className="space-y-2">
                      <p className="text-xs font-medium text-muted-foreground">Preview baru:</p>
                      <img
                        src={previewImage}
                        alt="Preview"
                        className="h-40 w-full rounded-md border object-cover"
                      />
                    </div>
                  )}

                  <Input
                    id="image"
                    type="file"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    onChange={handleImageChange}
                  />
                  <p className="text-xs text-muted-foreground">
                    Format: JPG, PNG, WebP. Max 2MB
                  </p>
                  <InputError message={errors.image as string} />
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Deskripsi</CardTitle>
                </CardHeader>
                <CardContent>
                  <textarea
                    id="deskripsi"
                    className="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    value={data.deskripsi}
                    onChange={(e) => setData('deskripsi', e.target.value)}
                    placeholder="Deskripsi produk (opsional)"
                  />
                  <InputError message={errors.deskripsi as string} />
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Pengaturan</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center gap-3">
                    <Checkbox
                      id="perlu_kalibrasi"
                      checked={data.perlu_kalibrasi}
                      onCheckedChange={(v) => setData('perlu_kalibrasi', Boolean(v))}
                    />
                    <Label htmlFor="perlu_kalibrasi" className="font-normal">
                      Perlu kalibrasi (untuk beans)
                    </Label>
                  </div>

                  <div className="flex items-center gap-3">
                    <Checkbox
                      id="aktif"
                      checked={data.aktif}
                      onCheckedChange={(v) => setData('aktif', Boolean(v))}
                    />
                    <Label htmlFor="aktif" className="font-normal">
                      Status Aktif
                    </Label>
                  </div>
                </CardContent>
              </Card>

              {stok_tersedia && stok_tersedia.length > 0 && (
                <Card>
                  <CardHeader>
                    <CardTitle className="text-sm">Stok Tersedia</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-2 text-sm">
                      {stok_tersedia.map((s) => (
                        <div
                          key={s.id}
                          className="flex items-center justify-between rounded border px-3 py-2"
                        >
                          <span className="font-medium">{s.cabang?.nama ?? 'Tanpa cabang'}</span>
                          <span className="font-mono text-xs">{s.jumlah}</span>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              )}

              <div className="flex gap-2">
                <Button type="submit" className="flex-1" disabled={processing}>
                  {processing ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Menyimpan...
                    </>
                  ) : (
                    'Simpan Perubahan'
                  )}
                </Button>
                <Button type="button" variant="outline" asChild>
                  <Link href="/produk">Batal</Link>
                </Button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}