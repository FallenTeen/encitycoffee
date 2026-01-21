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
import {
  CheckCircle2,
  Flame,
  Loader2,
  Snowflake,
  Sparkles,
} from 'lucide-react';

interface Kategori {
  id: number;
  nama: string;
  slug: string;
}

interface Props {
  kategori: Kategori[];
  tipe_options: string[];
  satuan_options: string[];
  snack_varian_options: string[];
}

export default function ProdukCreate({
  kategori,
  tipe_options,
  satuan_options,
  snack_varian_options,
}: Props) {
  const { data, setData, post, processing, errors } = useForm({
    tipe: '',
    kategori_id: '',
    nama: '',
    kelompok_nama: '',
    varian: 'none',
    sku: '',
    base: 'none',
    deskripsi: '',
    satuan_dasar: '',
    harga_modal_hot: '',
    harga_jual_hot: '',
    harga_modal_ice: '',
    harga_jual_ice: '',
    harga_modal: '',
    harga_jual: '',
    image: null as File | null,
    perlu_kalibrasi: false,
    aktif: true,
    buat_dua_varian: false,
    varian_hot: false,
    varian_ice: false,
  });

  const [autoTipe, setAutoTipe] = useState<string>('');
  const [showBaseField, setShowBaseField] = useState(false);
  const [skuStatus, setSkuStatus] = useState<'idle' | 'checking' | 'taken' | 'available'>('idle');
  const [isGeneratingSku, setIsGeneratingSku] = useState(false);
  const skuCheckTimeout = useRef<number | null>(null);
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({});
  const buatDuaVarian = data.buat_dua_varian;
  const [snackVarianMode, setSnackVarianMode] = useState<'none' | 'existing' | 'custom'>('none');
  const [snackVarianCustom, setSnackVarianCustom] = useState('');

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
      })
      .finally(() => {
        setIsGeneratingSku(false);
      });
  };

  const handleKategoriChange = (kategoriId: string) => {
    setData('kategori_id', kategoriId);
  };

  useEffect(() => {
    const kelompok = data.kelompok_nama.trim();
    const varian = data.varian;

    if (!kelompok) {
      setData('nama', '');
      return;
    }

    if (varian === 'none' || !varian) {
      setData('nama', kelompok);
    } else {
      setData('nama', `${kelompok} ${varian}`);
    }
  }, [data.kelompok_nama, data.varian]);

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

            if (!data.tipe) nextErrors.tipe = 'Tipe produk wajib dipilih';
            if (!data.kelompok_nama.trim()) nextErrors.kelompok_nama = 'Kelompok nama wajib diisi';
            if (!data.sku.trim()) nextErrors.sku = 'SKU wajib diisi';
            if (!data.satuan_dasar.trim()) nextErrors.satuan_dasar = 'Satuan dasar wajib diisi';
            if (showBaseField && (!data.base || data.base === 'none')) {
              nextErrors.base = 'Base produk wajib dipilih';
            }

            if (buatDuaVarian) {
              if (!data.harga_modal_hot || Number(data.harga_modal_hot) < 0) {
                nextErrors.harga_modal_hot = 'Harga modal Hot wajib diisi';
              }
              if (!data.harga_jual_hot || Number(data.harga_jual_hot) < 0) {
                nextErrors.harga_jual_hot = 'Harga jual Hot wajib diisi';
              }
              if (!data.harga_modal_ice || Number(data.harga_modal_ice) < 0) {
                nextErrors.harga_modal_ice = 'Harga modal Ice wajib diisi';
              }
              if (!data.harga_jual_ice || Number(data.harga_jual_ice) < 0) {
                nextErrors.harga_jual_ice = 'Harga jual Ice wajib diisi';
              }
            } else {
              if (!data.harga_modal || Number(data.harga_modal) < 0) {
                nextErrors.harga_modal = 'Harga modal wajib diisi';
              }
              if (!data.harga_jual || Number(data.harga_jual) < 0) {
                nextErrors.harga_jual = 'Harga jual wajib diisi';
              }
            }

            if (autoTipe === 'snack' && snackVarianMode === 'custom') {
              const value = snackVarianCustom.trim();
              if (!value) {
                nextErrors.varian =
                  'Isi varian snack baru atau pilih dari daftar yang tersedia';
              } else if (value.length > 50) {
                nextErrors.varian = 'Varian snack maksimal 50 karakter';
              }
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
            <div className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Informasi Dasar</CardTitle>
                  <CardDescription>Detail utama produk</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="tipe">Tipe Produk *</Label>
                      <Select
                        value={data.tipe}
                        onValueChange={(value) => {
                          setData('tipe', value);
                          const isMinuman = value === 'minuman';
                          setShowBaseField(isMinuman);
                          if (!isMinuman) {
                            setData('base', 'none');
                          }
                          setSnackVarianMode('none');
                          setSnackVarianCustom('');
                          setData('varian_hot', false);
                          setData('varian_ice', false);
                          setData('buat_dua_varian', false);
                          setData('varian', value === 'snack' ? '' : 'none');
                          setAutoTipe(value);
                        }}
                      >
                        <SelectTrigger id="tipe">
                          <SelectValue placeholder="Pilih tipe produk" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="snack">Snack</SelectItem>
                          <SelectItem value="beans">Beans</SelectItem>
                          <SelectItem value="minuman">Minuman</SelectItem>
                        </SelectContent>
                      </Select>
                      <InputError message={clientErrors.tipe || (errors.tipe as string)} />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="kategori_id">Kategori Produk (opsional)</Label>
                      <Select value={data.kategori_id} onValueChange={handleKategoriChange}>
                        <SelectTrigger id="kategori_id">
                          <SelectValue placeholder="Pilih kategori (jika ada)" />
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
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="kelompok_nama">Nama Produk *</Label>
                    <Input
                      id="kelompok_nama"
                      value={data.kelompok_nama}
                      onChange={(e) => setData('kelompok_nama', e.target.value)}
                      placeholder="Contoh: Caramel Latte"
                    />
                    <p className="text-xs text-muted-foreground">
                      Nama base produk, varian akan ditambahkan di belakang jika ada
                    </p>
                    <InputError
                      message={clientErrors.kelompok_nama || (errors.kelompok_nama as string)}
                    />
                  </div>

                  {data.kelompok_nama && data.tipe === 'minuman' && (
                    <div className="space-y-2">
                      <Label>Varian Produk</Label>
                      <div className="flex flex-wrap gap-2">
                        <Button
                          type="button"
                          variant={data.varian_hot ? 'default' : 'outline'}
                          size="sm"
                          onClick={() => {
                            const next = !data.varian_hot;
                            const nextIce = data.varian_ice;
                            const bothActive = next && nextIce;
                            setData('varian_hot', next);
                            setData('buat_dua_varian', bothActive);
                            if (next && !nextIce) {
                              setData('varian', 'Hot');
                            } else if (!next && nextIce) {
                              setData('varian', 'Ice');
                            } else {
                              setData('varian', 'none');
                            }
                          }}
                          className="inline-flex items-center gap-1.5"
                        >
                          <Flame className="h-4 w-4" />
                          <span>Hot</span>
                        </Button>
                        <Button
                          type="button"
                          variant={data.varian_ice ? 'default' : 'outline'}
                          size="sm"
                          onClick={() => {
                            const next = !data.varian_ice;
                            const nextHot = data.varian_hot;
                            const bothActive = nextHot && next;
                            setData('varian_ice', next);
                            setData('buat_dua_varian', bothActive);
                            if (nextHot && !next) {
                              setData('varian', 'Hot');
                            } else if (!nextHot && next) {
                              setData('varian', 'Ice');
                            } else {
                              setData('varian', 'none');
                            }
                          }}
                          className="inline-flex items-center gap-1.5"
                        >
                          <Snowflake className="h-4 w-4" />
                          <span>Ice</span>
                        </Button>
                      </div>
                      <p className="text-xs text-muted-foreground">
                        Pilih satu atau kedua varian. Jika keduanya aktif, akan membuat 2 produk sekaligus
                      </p>
                    </div>
                  )}

                  {data.kelompok_nama && data.tipe === 'snack' && (
                    <div className="space-y-2">
                      <Label>Varian Snack (opsional)</Label>
                      <Select
                        value={
                          snackVarianMode === 'existing'
                            ? data.varian || '__none'
                            : snackVarianMode === 'custom'
                            ? '__custom'
                            : '__none'
                        }
                        onValueChange={(value) => {
                          if (value === '__none') {
                            setSnackVarianMode('none');
                            setSnackVarianCustom('');
                            setData('varian', '');
                            return;
                          }
                          if (value === '__custom') {
                            setSnackVarianMode('custom');
                            setSnackVarianCustom('');
                            setData('varian', '');
                            return;
                          }
                          setSnackVarianMode('existing');
                          setSnackVarianCustom('');
                          setData('varian', value);
                        }}
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Pilih varian snack atau kosongkan" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="__none">Tanpa varian</SelectItem>
                          {snack_varian_options.map((v) => (
                            <SelectItem key={v} value={v}>
                              {v}
                            </SelectItem>
                          ))}
                          <SelectItem value="__custom">Varian baru...</SelectItem>
                        </SelectContent>
                      </Select>
                      {snackVarianMode === 'custom' && (
                        <div className="space-y-2">
                          <Label htmlFor="snack_varian_custom">Varian Snack Baru</Label>
                          <Input
                            id="snack_varian_custom"
                            value={snackVarianCustom}
                            onChange={(e) => {
                              const value = e.target.value;
                              setSnackVarianCustom(value);
                              setData('varian', value);
                            }}
                            placeholder="Contoh: Large, Small, Spicy"
                          />
                          <p className="text-xs text-muted-foreground">
                            Masukkan nama varian snack (maksimal 50 karakter)
                          </p>
                        </div>
                      )}
                      <InputError message={clientErrors.varian || (errors.varian as string)} />
                    </div>
                  )}
                </CardContent>
              </Card>

              <Card>
                  <CardHeader>
                    <CardTitle>Klasifikasi Produk</CardTitle>
                    <CardDescription>Base dan SKU produk</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                  {showBaseField && (
                    <div className="space-y-2">
                      <Label htmlFor="base">Base Produk *</Label>
                      <Select value={data.base} onValueChange={(v) => setData('base', v)}>
                        <SelectTrigger id="base">
                          <SelectValue placeholder="Pilih base" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="coffee">Coffee</SelectItem>
                          <SelectItem value="milk">Milk</SelectItem>
                          <SelectItem value="tea">Tea</SelectItem>
                          <SelectItem value="others">Others</SelectItem>
                        </SelectContent>
                      </Select>
                      <p className="text-xs text-muted-foreground">
                        Base ingredient utama minuman
                      </p>
                      <InputError message={clientErrors.base || (errors.base as string)} />
                    </div>
                  )}

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
                      <p className="inline-flex items-center gap-1 text-xs text-emerald-600">
                        <CheckCircle2 className="h-3 w-3" />
                        <span>SKU tersedia</span>
                      </p>
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
                <CardContent className="space-y-4">
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

                  {buatDuaVarian ? (
                    <>
                      <div className="space-y-4 rounded-lg border-2 border-orange-200 bg-orange-50 p-4">
                        <h4 className="flex items-center gap-2 font-semibold text-orange-900">
                          <Flame className="h-4 w-4" />
                          <span>Harga Varian HOT</span>
                        </h4>
                        <div className="grid gap-4 md:grid-cols-2">
                          <div className="space-y-2">
                            <Label htmlFor="harga_modal_hot">Harga Modal (Rp) *</Label>
                            <Input
                              id="harga_modal_hot"
                              type="number"
                              min="0"
                              step="0.01"
                              value={data.harga_modal_hot}
                              onChange={(e) => setData('harga_modal_hot', e.target.value)}
                              placeholder="20000"
                            />
                            <InputError
                              message={
                                clientErrors.harga_modal_hot || (errors.harga_modal_hot as string)
                              }
                            />
                          </div>
                          <div className="space-y-2">
                            <Label htmlFor="harga_jual_hot">Harga Jual (Rp) *</Label>
                            <Input
                              id="harga_jual_hot"
                              type="number"
                              min="0"
                              step="0.01"
                              value={data.harga_jual_hot}
                              onChange={(e) => setData('harga_jual_hot', e.target.value)}
                              placeholder="22000"
                            />
                            <InputError
                              message={
                                clientErrors.harga_jual_hot || (errors.harga_jual_hot as string)
                              }
                            />
                          </div>
                        </div>
                      </div>

                      <div className="space-y-4 rounded-lg border-2 border-blue-200 bg-blue-50 p-4">
                        <h4 className="flex items-center gap-2 font-semibold text-blue-900">
                          <Snowflake className="h-4 w-4" />
                          <span>Harga Varian ICE</span>
                        </h4>
                        <div className="grid gap-4 md:grid-cols-2">
                          <div className="space-y-2">
                            <Label htmlFor="harga_modal_ice">Harga Modal (Rp) *</Label>
                            <Input
                              id="harga_modal_ice"
                              type="number"
                              min="0"
                              step="0.01"
                              value={data.harga_modal_ice}
                              onChange={(e) => setData('harga_modal_ice', e.target.value)}
                              placeholder="22000"
                            />
                            <InputError
                              message={
                                clientErrors.harga_modal_ice || (errors.harga_modal_ice as string)
                              }
                            />
                          </div>
                          <div className="space-y-2">
                            <Label htmlFor="harga_jual_ice">Harga Jual (Rp) *</Label>
                            <Input
                              id="harga_jual_ice"
                              type="number"
                              min="0"
                              step="0.01"
                              value={data.harga_jual_ice}
                              onChange={(e) => setData('harga_jual_ice', e.target.value)}
                              placeholder="25000"
                            />
                            <InputError
                              message={
                                clientErrors.harga_jual_ice || (errors.harga_jual_ice as string)
                              }
                            />
                          </div>
                        </div>
                      </div>
                    </>
                  ) : (
                    <div className="grid gap-4 md:grid-cols-2">
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
                        <InputError
                          message={clientErrors.harga_modal || (errors.harga_modal as string)}
                        />
                      </div>
                      <div className="space-y-2">
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
                        <InputError
                          message={clientErrors.harga_jual || (errors.harga_jual as string)}
                        />
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>

              {data.kelompok_nama && (data.varian_hot || data.varian_ice) && (
                <Card>
                  <CardHeader>
                    <CardTitle>Preview Produk yang Akan Dibuat</CardTitle>
                    <CardDescription>
                      Ringkasan produk berdasarkan varian yang dipilih
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {buatDuaVarian ? (
                      <>
                        <div className="flex items-start justify-between rounded-lg border border-orange-200 bg-orange-50/50 p-3">
                          <div className="flex items-center gap-2">
                            <Flame className="h-4 w-4 text-orange-600" />
                            <span className="font-medium text-sm">
                              {`${data.kelompok_nama.trim()} Hot`}
                            </span>
                          </div>
                          <div className="text-right text-xs text-muted-foreground">
                            <div>Modal: Rp {data.harga_modal_hot || '0'}</div>
                            <div>Jual: Rp {data.harga_jual_hot || '0'}</div>
                          </div>
                        </div>
                        <div className="flex items-start justify-between rounded-lg border border-blue-200 bg-blue-50/50 p-3">
                          <div className="flex items-center gap-2">
                            <Snowflake className="h-4 w-4 text-blue-600" />
                            <span className="font-medium text-sm">
                              {`${data.kelompok_nama.trim()} Ice`}
                            </span>
                          </div>
                          <div className="text-right text-xs text-muted-foreground">
                            <div>Modal: Rp {data.harga_modal_ice || '0'}</div>
                            <div>Jual: Rp {data.harga_jual_ice || '0'}</div>
                          </div>
                        </div>
                      </>
                    ) : data.varian === 'Hot' ? (
                      <div className="flex items-start justify-between rounded-lg border border-orange-200 bg-orange-50/50 p-3">
                        <div className="flex items-center gap-2">
                          <Flame className="h-4 w-4 text-orange-600" />
                          <span className="font-medium text-sm">
                            {`${data.kelompok_nama.trim()} Hot`}
                          </span>
                        </div>
                        <div className="text-right text-xs text-muted-foreground">
                          <div>Modal: Rp {data.harga_modal || '0'}</div>
                          <div>Jual: Rp {data.harga_jual || '0'}</div>
                        </div>
                      </div>
                    ) : data.varian === 'Ice' ? (
                      <div className="flex items-start justify-between rounded-lg border border-blue-200 bg-blue-50/50 p-3">
                        <div className="flex items-center gap-2">
                          <Snowflake className="h-4 w-4 text-blue-600" />
                          <span className="font-medium text-sm">
                            {`${data.kelompok_nama.trim()} Ice`}
                          </span>
                        </div>
                        <div className="text-right text-xs text-muted-foreground">
                          <div>Modal: Rp {data.harga_modal || '0'}</div>
                          <div>Jual: Rp {data.harga_jual || '0'}</div>
                        </div>
                      </div>
                    ) : null}
                  </CardContent>
                </Card>
              )}
            </div>

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
