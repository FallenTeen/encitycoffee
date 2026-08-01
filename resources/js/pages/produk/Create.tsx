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
import { useEffect, useMemo, useRef, useState } from 'react';
import {
  AlertCircle,
  Building2,
  CheckCircle2,
  Circle,
  DollarSign,
  Flame,
  Image as ImageIcon,
  Info,
  Loader2,
  Package,
  Settings2,
  Snowflake,
  Sparkles,
  Tag,
} from 'lucide-react';
import { ImageUploader } from '@/components/ImageUploader';

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
  selectedCabang?: { id: number; nama: string; kode: string } | null;
  cabangList?: Array<{ id: number; nama: string; kode: string }>;
  isMultiBranchManager?: boolean;
  userCabangIds?: number[];
}

/* ---------------------------------------------------------------------- */
/*  Helper UI kecil: indikator status (merah/kuning/hijau) & hint bantuan  */
/*  Murni presentasional, tidak mengubah logic form/validasi apapun.       */
/* ---------------------------------------------------------------------- */

type SectionStatus = 'empty' | 'warning' | 'complete';

const STATUS_STYLE: Record<SectionStatus, { badge: string; ring: string; label: string }> = {
  empty: { badge: 'bg-red-50 text-red-600 border-red-200', ring: 'border-red-300', label: 'Belum lengkap' },
  warning: { badge: 'bg-amber-50 text-amber-700 border-amber-200', ring: 'border-amber-300', label: 'Perlu dicek' },
  complete: { badge: 'bg-green-50 text-green-700 border-green-200', ring: 'border-green-300', label: 'Lengkap' },
};

function StatusIcon({ status, className }: { status: SectionStatus; className?: string }) {
  if (status === 'complete') return <CheckCircle2 className={className} />;
  if (status === 'warning') return <AlertCircle className={className} />;
  return <Circle className={className} />;
}

function SectionStatusBadge({ status, label }: { status: SectionStatus; label?: string }) {
  const style = STATUS_STYLE[status];
  return (
    <span
      className={`inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium ${style.badge}`}
    >
      <StatusIcon status={status} className="h-3 w-3" />
      {label ?? style.label}
    </span>
  );
}

function ProgressSteps({
  steps,
}: {
  steps: { key: string; label: string; status: SectionStatus; onClick: () => void }[];
}) {
  return (
    <div className="flex flex-wrap items-center gap-1.5 rounded-lg border bg-muted/30 p-1.5">
      {steps.map((step, idx) => {
        const style = STATUS_STYLE[step.status];
        return (
          <button
            key={step.key}
            type="button"
            onClick={step.onClick}
            className="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted"
          >
            <span
              className={`flex h-5 w-5 items-center justify-center rounded-full border text-[10px] ${style.badge}`}
            >
              {step.status === 'complete' ? <CheckCircle2 className="h-3 w-3" /> : idx + 1}
            </span>
            <span className={step.status !== 'empty' ? 'text-foreground' : ''}>{step.label}</span>
          </button>
        );
      })}
    </div>
  );
}

function HintBox({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50/60 px-3 py-2 text-xs text-amber-800">
      <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
      <span>{children}</span>
    </div>
  );
}

export default function ProdukCreate({
  kategori,
  tipe_options,
  satuan_options,
  snack_varian_options,
  selectedCabang,
  cabangList,
  isMultiBranchManager = false,
  userCabangIds = [],
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
    cabang_id: isMultiBranchManager ? '' : (selectedCabang?.id?.toString() || ''),
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
  const [branchError, setBranchError] = useState('');

  // Refs untuk navigasi cepat lewat progress steps (murni UI, tidak memengaruhi data form)
  const cabangRef = useRef<HTMLDivElement>(null);
  const infoRef = useRef<HTMLDivElement>(null);
  const klasifikasiRef = useRef<HTMLDivElement>(null);
  const hargaRef = useRef<HTMLDivElement>(null);

  const scrollTo = (ref: React.RefObject<HTMLDivElement | null>) => {
    ref.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

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

  /* ------------------------- Status per-bagian (UI only) ------------------------- */

  const infoStatus: SectionStatus = useMemo(() => {
    if (!data.tipe || !data.kelompok_nama.trim()) return 'empty';
    return 'complete';
  }, [data.tipe, data.kelompok_nama]);

  const klasifikasiStatus: SectionStatus = useMemo(() => {
    if (showBaseField && (!data.base || data.base === 'none')) return 'empty';
    if (!data.sku.trim()) return 'empty';
    if (skuStatus === 'taken') return 'empty';
    if (skuStatus === 'checking') return 'warning';
    return 'complete';
  }, [showBaseField, data.base, data.sku, skuStatus]);

  const hargaStatus: SectionStatus = useMemo(() => {
    if (!data.satuan_dasar.trim()) return 'empty';
    if (buatDuaVarian) {
      const fields = [data.harga_modal_hot, data.harga_jual_hot, data.harga_modal_ice, data.harga_jual_ice];
      if (fields.some((f) => f === '' || Number(f) < 0)) return 'empty';
      if (
        Number(data.harga_jual_hot) <= Number(data.harga_modal_hot) ||
        Number(data.harga_jual_ice) <= Number(data.harga_modal_ice)
      ) {
        return 'warning';
      }
      return 'complete';
    }
    if (data.harga_modal === '' || Number(data.harga_modal) < 0 || data.harga_jual === '' || Number(data.harga_jual) < 0) {
      return 'empty';
    }
    if (Number(data.harga_jual) <= Number(data.harga_modal)) return 'warning';
    return 'complete';
  }, [
    data.satuan_dasar,
    buatDuaVarian,
    data.harga_modal_hot,
    data.harga_jual_hot,
    data.harga_modal_ice,
    data.harga_jual_ice,
    data.harga_modal,
    data.harga_jual,
  ]);

  const cabangStatus: SectionStatus = useMemo(() => {
    if (!isMultiBranchManager) return 'complete';
    return data.cabang_id ? 'complete' : 'empty';
  }, [isMultiBranchManager, data.cabang_id]);

  const steps = [
    ...(isMultiBranchManager
      ? [{ key: 'cabang', label: 'Cabang', status: cabangStatus, onClick: () => scrollTo(cabangRef) }]
      : []),
    { key: 'info', label: 'Info Dasar', status: infoStatus, onClick: () => scrollTo(infoRef) },
    { key: 'klasifikasi', label: 'Klasifikasi & SKU', status: klasifikasiStatus, onClick: () => scrollTo(klasifikasiRef) },
    { key: 'harga', label: 'Harga', status: hargaStatus, onClick: () => scrollTo(hargaRef) },
  ];

  const skuPrasyaratBelumLengkap = !data.tipe || !data.kelompok_nama.trim();

  return (
    <AppLayout breadcrumbs={[{ title: 'Produk', href: '/produk' }, { title: 'Tambah Produk', href: '/produk/create' }]}>
      <Head title="Tambah Produk" />
      <div className="space-y-5">
        <div className="space-y-3">
          <div className="flex items-center justify-between gap-3">
            <div>
              <h1 className="flex items-center gap-2 text-xl font-semibold">
                <Package className="h-5 w-5 text-muted-foreground" />
                Tambah Produk
              </h1>
              <p className="text-sm text-muted-foreground">Isi detail produk baru untuk sistem POS</p>
            </div>
            <Button asChild variant="outline" size="sm">
              <Link href="/produk">Kembali</Link>
            </Button>
          </div>
          <ProgressSteps steps={steps} />
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

            // Validasi cabang_id untuk multi-cabang manager
            if (isMultiBranchManager && !data.cabang_id) {
              nextErrors.cabang_id = 'Cabang wajib dipilih untuk manager multi-cabang';
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
          <div className="grid gap-5 lg:grid-cols-[2fr,1fr]">
            <div className="space-y-5">
              {/* Branch Selection for Multi-Branch Manager */}
              {isMultiBranchManager && cabangList && cabangList.length > 0 && (
                <div ref={cabangRef} className="scroll-mt-4">
                  <Card className="border-blue-200 bg-blue-50/50">
                    <CardHeader className="pb-3">
                      <div className="flex items-start justify-between gap-2">
                        <div>
                          <CardTitle className="flex items-center gap-2 text-base">
                            <Building2 className="h-4 w-4 text-blue-700" />
                            Penugasan Cabang *
                          </CardTitle>
                          <CardDescription className="text-xs">
                            Pilih cabang untuk produk ini. Produk hanya bisa dikelola oleh manager cabang yang ditugaskan.
                          </CardDescription>
                        </div>
                        <SectionStatusBadge status={cabangStatus} />
                      </div>
                    </CardHeader>
                    <CardContent>
                      <Select
                        value={data.cabang_id}
                        onValueChange={(value) => {
                          setData('cabang_id', value);
                          setBranchError('');
                        }}
                      >
                        <SelectTrigger id="cabang_id" className="w-full">
                          <SelectValue placeholder="Pilih cabang..." />
                        </SelectTrigger>
                        <SelectContent>
                          {cabangList.map((cabang) => (
                            <SelectItem key={cabang.id} value={String(cabang.id)}>
                              {cabang.nama} ({cabang.kode})
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      <InputError message={clientErrors.cabang_id || branchError || (errors.cabang_id as string)} />
                    </CardContent>
                  </Card>
                </div>
              )}

              {/* Single Branch Info (Hidden for multi-branch) */}
              {!isMultiBranchManager && selectedCabang && (
                <Card className="border-green-200 bg-green-50/50">
                  <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-base">
                      <Building2 className="h-4 w-4 text-green-700" />
                      Cabang Otomatis
                    </CardTitle>
                    <CardDescription className="text-xs">
                      Produk akan otomatis ditugaskan ke cabang Anda: <strong>{selectedCabang.nama}</strong>
                    </CardDescription>
                  </CardHeader>
                </Card>
              )}

              <div ref={infoRef} className="scroll-mt-4">
                <Card>
                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <CardTitle className="flex items-center gap-2 text-base">
                          <Info className="h-4 w-4 text-muted-foreground" />
                          Informasi Dasar
                        </CardTitle>
                        <CardDescription>Detail utama produk</CardDescription>
                      </div>
                      <SectionStatusBadge status={infoStatus} />
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    <div className="grid gap-3 md:grid-cols-2">
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
                            <SelectItem value="makanan">Makanan</SelectItem>
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
              </div>

              <div ref={klasifikasiRef} className="scroll-mt-4">
                <Card>
                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <CardTitle className="flex items-center gap-2 text-base">
                          <Tag className="h-4 w-4 text-muted-foreground" />
                          Klasifikasi Produk
                        </CardTitle>
                        <CardDescription>Base dan SKU produk</CardDescription>
                      </div>
                      <SectionStatusBadge status={klasifikasiStatus} />
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    {skuPrasyaratBelumLengkap && (
                      <HintBox>
                        Lengkapi <strong>Tipe Produk</strong> dan <strong>Nama Produk</strong> di bagian
                        Informasi Dasar terlebih dahulu, agar SKU dapat dibuat otomatis.
                      </HintBox>
                    )}

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
                        <p className="flex items-center gap-1 text-xs text-red-600">
                          <AlertCircle className="h-3 w-3" />
                          SKU ini sudah dipakai
                        </p>
                      )}
                      {skuStatus === 'available' && (
                        <p className="inline-flex items-center gap-1 text-xs text-green-600">
                          <CheckCircle2 className="h-3 w-3" />
                          <span>SKU tersedia</span>
                        </p>
                      )}
                      {skuStatus === 'checking' && (
                        <p className="flex items-center gap-1 text-xs text-amber-600">
                          <Loader2 className="h-3 w-3 animate-spin" />
                          Memeriksa ketersediaan...
                        </p>
                      )}
                      <InputError message={clientErrors.sku || (errors.sku as string)} />
                    </div>
                  </CardContent>
                </Card>
              </div>

              <div ref={hargaRef} className="scroll-mt-4">
                <Card>
                  <CardHeader className="pb-3">
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <CardTitle className="flex items-center gap-2 text-base">
                          <DollarSign className="h-4 w-4 text-muted-foreground" />
                          Harga & Satuan
                        </CardTitle>
                        <CardDescription>Informasi harga dan unit</CardDescription>
                      </div>
                      <SectionStatusBadge status={hargaStatus} />
                    </div>
                  </CardHeader>
                  <CardContent className="space-y-3">
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
                          <SelectItem value="porsi">Porsi</SelectItem>
                          <SelectItem value="mili liter">Mili liter</SelectItem>
                        </SelectContent>
                      </Select>
                      <InputError message={clientErrors.satuan_dasar || (errors.satuan_dasar as string)} />
                    </div>

                    {buatDuaVarian ? (
                      <>
                        <div className="space-y-3 rounded-lg border-2 border-orange-200 bg-orange-50 p-3">
                          <h4 className="flex items-center gap-2 text-sm font-semibold text-orange-900">
                            <Flame className="h-4 w-4" />
                            <span>Harga Varian HOT</span>
                          </h4>
                          <div className="grid gap-3 md:grid-cols-2">
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
                          {data.harga_modal_hot !== '' &&
                            data.harga_jual_hot !== '' &&
                            Number(data.harga_jual_hot) <= Number(data.harga_modal_hot) && (
                              <p className="flex items-center gap-1 text-xs text-amber-700">
                                <AlertCircle className="h-3 w-3" />
                                Harga jual Hot sebaiknya lebih besar dari harga modal
                              </p>
                            )}
                        </div>

                        <div className="space-y-3 rounded-lg border-2 border-blue-200 bg-blue-50 p-3">
                          <h4 className="flex items-center gap-2 text-sm font-semibold text-blue-900">
                            <Snowflake className="h-4 w-4" />
                            <span>Harga Varian ICE</span>
                          </h4>
                          <div className="grid gap-3 md:grid-cols-2">
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
                          {data.harga_modal_ice !== '' &&
                            data.harga_jual_ice !== '' &&
                            Number(data.harga_jual_ice) <= Number(data.harga_modal_ice) && (
                              <p className="flex items-center gap-1 text-xs text-amber-700">
                                <AlertCircle className="h-3 w-3" />
                                Harga jual Ice sebaiknya lebih besar dari harga modal
                              </p>
                            )}
                        </div>
                      </>
                    ) : (
                      <div className="grid gap-3 md:grid-cols-2">
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
                        {data.harga_modal !== '' &&
                          data.harga_jual !== '' &&
                          Number(data.harga_jual) <= Number(data.harga_modal) && (
                            <p className="flex items-center gap-1 text-xs text-amber-700 md:col-span-2">
                              <AlertCircle className="h-3 w-3" />
                              Harga jual sebaiknya lebih besar dari harga modal
                            </p>
                          )}
                      </div>
                    )}
                  </CardContent>
                </Card>
              </div>

              {data.kelompok_nama && (data.varian_hot || data.varian_ice) && (
                <Card>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-base">Preview Produk yang Akan Dibuat</CardTitle>
                    <CardDescription>
                      Ringkasan produk berdasarkan varian yang dipilih
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-2">
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

            <div className="space-y-5">
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="flex items-center gap-2 text-base">
                    <ImageIcon className="h-4 w-4 text-muted-foreground" />
                    Gambar Produk
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <ImageUploader
                    value={data.image}
                    onChange={(file) => setData('image', file)}
                    error={errors.image as string}
                    label=""
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    maxSizeMB={2}
                  />
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="flex items-center gap-2 text-base">
                    <Settings2 className="h-4 w-4 text-muted-foreground" />
                    Detail Tambahan
                  </CardTitle>
                  <CardDescription>Deskripsi & pengaturan produk</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="deskripsi">Deskripsi (opsional)</Label>
                    <textarea
                      id="deskripsi"
                      className="flex min-h-[90px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                      value={data.deskripsi}
                      onChange={(e) => setData('deskripsi', e.target.value)}
                      placeholder="Deskripsi produk (opsional)"
                    />
                    <InputError message={errors.deskripsi as string} />
                  </div>

                  <div className="space-y-3 border-t pt-3">
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