import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import laporan from '@/routes/laporan';
import stok from '@/routes/stok';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

type TipeStok = 'produksi_minuman' | 'penjualan_retail';

interface CabangOption {
    id: number;
    nama: string;
}

interface ProdukInfo {
    id: number;
    nama: string;
    sku?: string;
    kategori?: { id: number; nama: string } | null;
}

interface BatchInfo {
    id: number;
    jumlah: number;
    tanggal_kadaluarsa?: string | null;
}

interface StokItem {
    id: number;
    cabang_id: number;
    tipe_stok: TipeStok;
    jumlah: number;
    stok_minimum: number;
    produk?: ProdukInfo | null;
    cabang?: { id: number; nama?: string } | null;
    batch?: BatchInfo[];
    nilai_total: number;
    is_stok_rendah: boolean;
    batch_kadaluarsa_count: number;
}

interface StatistikStok {
    total_item: number;
    item_stok_rendah: number;
    total_nilai_inventori: number;
    item_mendekati_kadaluarsa: number;
}

interface FilterAktif {
    cabang_id?: number | null;
    tipe_stok?: TipeStok | '';
    status?: 'semua' | 'rendah' | 'normal' | '';
}

interface Props {
    stoks: StokItem[];
    statistik: StatistikStok;
    filter_aktif: FilterAktif;
    cabang_list: any[];
}

type ViewMode = 'semua' | 'rendah' | 'normal' | 'kadaluarsa';

function formatRupiah(value: number | string | null | undefined) {
    const num = typeof value === 'string' ? Number(value) : value ?? 0;
    if (Number.isNaN(num)) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
}

function SkeletonCard() {
    return (
        <Card>
            <CardHeader>
                <Skeleton className="h-4 w-32 mb-2" />
                <Skeleton className="h-8 w-24" />
            </CardHeader>
        </Card>
    );
}

export default function StokIndex({
    stoks,
    statistik,
    filter_aktif,
    cabang_list,
}: Props) {
    const [view, setView] = useState<ViewMode>(() => {
        if (filter_aktif?.status === 'rendah') return 'rendah';
        if (filter_aktif?.status === 'normal') return 'normal';
        return 'semua';
    });

    const { data, setData, get, processing, errors } = useForm({
        cabang_id: filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '',
        tipe_stok: filter_aktif?.tipe_stok ?? '',
        status: filter_aktif?.status ?? '',
    });

    useEffect(() => {
        const interval = setInterval(() => {
            router.reload({
                only: ['stoks', 'statistik', 'filter_aktif'],
            });
        }, 30000);
        return () => clearInterval(interval);
    }, []);

    const submit = () => {
        get(stok.index().url, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const filteredStoks = useMemo(() => {
        if (view === 'kadaluarsa') {
            return (stoks ?? []).filter(
                (s) => (s.batch_kadaluarsa_count ?? 0) > 0,
            );
        }
        return stoks ?? [];
    }, [stoks, view]);

    return (
        <AppLayout breadcrumbs={[{ title: 'Stok', href: stok.index().url }]}>
            <Head title="Stok" />
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Ringkasan Stok</h1>
                        <p className="text-sm text-muted-foreground">
                            Monitoring persediaan, stok rendah, dan kadaluarsa secara terpadu.
                        </p>
                    </div>
                    <Button asChild variant="outline" size="sm">
                        <Link href={laporan.stok().url}>Laporan Stok</Link>
                    </Button>
                </div>

                {/* Statistik Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs">Total Item</CardDescription>
                            <CardTitle className="text-2xl">{statistik?.total_item ?? 0}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card className="border-amber-200 bg-amber-50/50">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs text-amber-700">Item Stok Rendah</CardDescription>
                            <CardTitle className="text-2xl text-amber-600">{statistik?.item_stok_rendah ?? 0}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs">Nilai Inventori</CardDescription>
                            <CardTitle className="text-2xl">{formatRupiah(statistik?.total_nilai_inventori ?? 0)}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card className="border-red-200 bg-red-50/50">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs text-red-700">Batch Mendekati Kadaluarsa</CardDescription>
                            <CardTitle className="text-2xl text-red-600">{statistik?.item_mendekati_kadaluarsa ?? 0}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Filter & Navigation Card */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Filter & Navigasi</CardTitle>
                        <CardDescription>
                            Filter dan navigasi cepat ke tampilan stok berbeda.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Quick Navigation Buttons */}
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant={view === 'semua' ? 'default' : 'outline'}
                                onClick={() => { setView('semua'); setData('status', ''); submit(); }}
                            >
                                Semua Stok
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={view === 'rendah' ? 'default' : 'outline'}
                                onClick={() => { setView('rendah'); setData('status', 'rendah'); submit(); }}
                            >
                                Stok Rendah
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={view === 'normal' ? 'default' : 'outline'}
                                onClick={() => { setView('normal'); setData('status', 'normal'); submit(); }}
                            >
                                Stok Normal
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant={view === 'kadaluarsa' ? 'default' : 'outline'}
                                onClick={() => setView('kadaluarsa')}
                            >
                                Kadaluarsa
                                <Badge variant="destructive" className="ml-2">
                                    {statistik?.item_mendekati_kadaluarsa ?? 0}
                                </Badge>
                            </Button>
                        </div>

                        {/* Filter Form */}
                        <form
                            className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5"
                            onSubmit={(e) => { e.preventDefault(); submit(); }}
                        >
                            <div className="space-y-1.5">
                                <div className="text-xs font-medium">Cabang</div>
                                <Select
                                    value={data.cabang_id || 'all'}
                                    onValueChange={(value) => setData('cabang_id', value === 'all' ? '' : value)}
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua cabang" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua cabang</SelectItem>
                                        {(cabang_list ?? []).map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.cabang_id as string} />
                            </div>

                            <div className="space-y-1.5">
                                <div className="text-xs font-medium">Tipe Stok</div>
                                <Select
                                    value={data.tipe_stok || 'all'}
                                    onValueChange={(value) => setData('tipe_stok', value === 'all' ? '' : value)}
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua tipe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua tipe</SelectItem>
                                        <SelectItem value="produksi_minuman">Produksi minuman</SelectItem>
                                        <SelectItem value="penjualan_retail">Penjualan retail</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.tipe_stok as string} />
                            </div>

                            <div className="space-y-1.5">
                                <div className="text-xs font-medium">Status</div>
                                <Select
                                    value={data.status || 'all'}
                                    onValueChange={(value) => {
                                        const next = value === 'all' ? '' : value;
                                        setData('status', next ?? '');
                                        if (next === 'rendah' || next === 'normal') {
                                            setView(next);
                                        } else {
                                            setView('semua');
                                        }
                                    }}
                                >
                                    <SelectTrigger className="h-9">
                                        <SelectValue placeholder="Semua status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua status</SelectItem>
                                        <SelectItem value="rendah">Stok rendah</SelectItem>
                                        <SelectItem value="normal">Stok normal</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status as string} />
                            </div>

                            <div className="flex items-end gap-2 lg:col-span-2">
                                <Button type="submit" disabled={processing} size="sm" className="flex-1">
                                    {processing ? 'Memuat...' : 'Terapkan'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        setData({ cabang_id: '', tipe_stok: '', status: '' });
                                        setView('semua');
                                        router.get(stok.index().url, {}, { preserveScroll: true, replace: true });
                                    }}
                                >
                                    Reset
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Data Table Card */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">
                            Daftar Stok
                            <span className="ml-2 text-sm font-normal text-muted-foreground">
                                ({filteredStoks.length} item)
                            </span>
                        </CardTitle>
                        <CardDescription>
                            Detail stok per produk dan cabang berdasarkan filter aktif.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50 text-left">
                                        <th className="px-3 py-2 font-medium">Produk</th>
                                        <th className="px-3 py-2 font-medium">Cabang</th>
                                        <th className="px-3 py-2 font-medium">Tipe</th>
                                        <th className="px-3 py-2 text-right font-medium">Jumlah</th>
                                        <th className="px-3 py-2 text-right font-medium">Minimum</th>
                                        <th className="px-3 py-2 text-right font-medium">Nilai Stok</th>
                                        <th className="px-3 py-2 text-center font-medium">Kadaluarsa</th>
                                        <th className="px-3 py-2 text-center font-medium">Status</th>
                                        <th className="px-3 py-2 text-center font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredStoks.map((s) => {
                                        const isLow = s.is_stok_rendah;
                                        const hasExpiring = (s.batch_kadaluarsa_count ?? 0) > 0;
                                        return (
                                            <tr key={s.id} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                                <td className="px-3 py-2">
                                                    <div className="font-medium">{s.produk?.nama ?? '-'}</div>
                                                    <div className="text-xs text-muted-foreground">{s.produk?.sku ?? ''}</div>
                                                </td>
                                                <td className="px-3 py-2">{s.cabang?.nama ?? '-'}</td>
                                                <td className="px-3 py-2 capitalize">
                                                    <Badge variant="outline" className="text-xs">
                                                        {s.tipe_stok === 'produksi_minuman' ? 'Produksi' : 'Retail'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-2 text-right font-medium">{s.jumlah}</td>
                                                <td className="px-3 py-2 text-right">{s.stok_minimum}</td>
                                                <td className="px-3 py-2 text-right">{formatRupiah(s.nilai_total)}</td>
                                                <td className="px-3 py-2 text-center">
                                                    {hasExpiring ? (
                                                        <Badge variant="destructive" className="text-xs">
                                                            {s.batch_kadaluarsa_count} batch
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">-</span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 text-center">
                                                    <Badge variant={isLow ? 'destructive' : 'secondary'} className="text-xs">
                                                        {isLow ? 'Rendah' : 'Normal'}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-2 text-center">
                                                    <Button asChild size="sm" variant="outline">
                                                        <Link href={stok.mutasi(s.id).url}>Riwayat</Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {filteredStoks.length === 0 && (
                                        <tr>
                                            <td colSpan={9} className="px-4 py-12 text-center">
                                                <div className="flex flex-col items-center gap-2">
                                                    <div className="text-muted-foreground text-sm">Belum ada data stok untuk filter saat ini.</div>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
