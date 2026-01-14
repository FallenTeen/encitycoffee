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
import { Loader2, Sparkles } from 'lucide-react';

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
  const { data, setData, post, processing, errors } = useForm({
    kategori_id: '',
    nama: '',
    kelompok_nama: '',
    varian: '',
    sku: '',
    tipe: '',
    base: '',
    deskripsi: '',
    satuan_dasar: '',
    harga_modal: '',
    harga_jual: '',
    image: null as File | null,
    perlu_kalibrasi: false,
    aktif: true,
  });

  const [skuStatus, setSkuStatus] = useState<'idle' | 'checking' | 'taken' | 'available'>('idle');
  const [isGeneratingSku, setIsGeneratingSku] = useState(false);
  const skuCheckTimeout = useRef<number | null>(null);
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({});
  const [kelompokList, setKelompokList] = useState<string[]>([]);
  const [showKelompokInput, setShowKelompokInput] = useState(false);

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

  // Auto-generate SKU when tipe, nama, or varian changes
  useEffect(() => {
    if (data.tipe && data.nama) {
      handleGenerateSku();
    }
  }, [data.tipe, data.nama, data.varian]);

  return (
    <AppLayout breadcrumbs={[{ title: 'Produk', href: '/produk' }, { title: 'Tambah Produk', href: '/produk/create' }]}>
      <Head title="Tambah Produk" />
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-xl font-semibold">Tambah Produk</h1>
            <p className="text-sm text-muted-foreground">Isi detail produk baru untuk sistem POS</p>
          </div>
          <Button asChild variant="outline">
            <Link href="/produk">Kembali</Link>
          </Button>
        </div>

        <form
          onSubmit={(e) => {
            e.preventDefault();

            const nextErrors: Record<string, string> = {};

            if (!data.kategori_id) nextErrors.kategori_id = 'Kategori wajib dipilih';
            if (!data.nama.trim()) nextErrors.nama = 'Nama produk wajib diisi';
            if (!data.sku.trim()) nextErrors.sku = 'SKU wajib diisi';
            if (!data.tipe) nextErrors.tipe = 'Tipe produk wajib dipilih';
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
            post('/produk');
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
                    <InputError message={clientErrors.kategori_id || (errors.kategori_id as string)} />
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
                    <InputError message={clientErrors.tipe || (errors.tipe as string)} />
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
                          <p>Generate SKU otomatis</p>
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

              <div className="flex gap-2">
                <Button type="submit" className="flex-1" disabled={processing}>
                  {processing ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Menyimpan...
                    </>
                  ) : (
                    'Simpan Produk'
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