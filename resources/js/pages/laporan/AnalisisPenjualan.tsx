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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Head, usePage, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { FileSpreadsheet, FileText } from 'lucide-react';

interface TopProdukRow {
    produk_id?: number;
    nama: string;
    total_terjual: number;
    pendapatan: number;
    rata_rata_per_transaksi?: number;
    kontribusi_persen?: number;
}

interface RingkasanTipe {
    tipe: string | null;
    total_terjual: number;
    pendapatan: number;
    jumlah_transaksi: number;
}

interface KategoriRow {
    kategori_id: number;
    kategori: string;
    pendapatan_kotor: number;
    total_modal: number;
    margin: number;
    margin_persen: number;
    pendapatan_bersih?: number;
}

interface RingkasanKategori {
    total_pendapatan_kotor: number;
    total_modal: number;
    total_margin: number;
    rata_rata_margin_per_kategori: number;
    kategori_margin_tertinggi?: KategoriRow | null;
    kategori_margin_terendah?: KategoriRow | null;
}

interface FilterAktif {
    tanggal_mulai?: string | null;
    tanggal_selesai?: string | null;
    kategori_id?: number | null;
    harga_min?: number | null;
    harga_max?: number | null;
}

interface Props {
    penjualan?: {
        total_pendapatan: number;
        top_produk: TopProdukRow[];
        ringkasan_tipe: RingkasanTipe[];
    };
    kategori?: {
        per_kategori: KategoriRow[];
        ringkasan: RingkasanKategori;
    };
    filter_aktif: FilterAktif;
    cabang_options: any[];
    kategori_options: any[];
    tipe: string;
}

function formatRupiah(value: number | string | null | undefined) {
    const num = typeof value === 'string' ? Number(value) : value ?? 0;
    if (!Number.isFinite(num)) return '-';
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

function SkeletonTable({ rows = 5 }: { rows?: number }) {
    return (
        <div className="space-y-2">
            {Array.from({ length: rows }).map((_, i) => (
                <Skeleton key={i} className="h-10 w-full" />
            ))}
        </div>
    );
}

export default function AnalisisPenjualan({
    penjualan,
    kategori,
    filter_aktif,
    cabang_options,
    kategori_options,
    tipe,
}: Props) {
    const { url } = usePage();
    const [activeTab, setActiveTab] = useState(tipe === 'kategori' ? 'kategori' : 'penjualan');

    const { data, setData, get, processing } = useForm({
        tanggal_mulai: filter_aktif?.tanggal_mulai ?? '',
        tanggal_selesai: filter_aktif?.tanggal_selesai ?? '',
        kategori_id: filter_aktif?.kategori_id ? String(filter_aktif.kategori_id) : 'all',
        harga_min: filter_aktif?.harga_min !== undefined && filter_aktif?.harga_min !== null
            ? String(filter_aktif.harga_min) : '',
        harga_max: filter_aktif?.harga_max !== undefined && filter_aktif?.harga_max !== null
            ? String(filter_aktif.harga_max) : '',
    });

    const submit = () => {
        const params = new URLSearchParams();
        if (data.tanggal_mulai) params.set('tanggal_mulai', data.tanggal_mulai);
        if (data.tanggal_selesai) params.set('tanggal_selesai', data.tanggal_selesai);
        if (data.kategori_id && data.kategori_id !== 'all') params.set('kategori_id', data.kategori_id);
        if (data.harga_min) params.set('harga_min', data.harga_min);
        if (data.harga_max) params.set('harga_max', data.harga_max);
        params.set('tipe', activeTab === 'penjualan' ? 'penjualan' : 'kategori');

        window.location.href = `/laporan/analisis-penjualan?${params.toString()}`;
    };

    const handleReset = () => {
        setData({
            tanggal_mulai: '',
            tanggal_selesai: '',
            kategori_id: 'all',
            harga_min: '',
            harga_max: '',
        });
        window.location.href = '/laporan/analisis-penjualan?tipe=' + activeTab;
    };

    const buildExportUrl = (format: 'excel' | 'pdf') => {
        const params = new URLSearchParams();
        if (data.tanggal_mulai) params.set('tanggal_mulai', data.tanggal_mulai);
        if (data.tanggal_selesai) params.set('tanggal_selesai', data.tanggal_selesai);
        if (data.kategori_id && data.kategori_id !== 'all') params.set('kategori_id', data.kategori_id);
        if (data.harga_min) params.set('harga_min', data.harga_min);
        if (data.harga_max) params.set('harga_max', data.harga_max);
        params.set('tipe', activeTab === 'penjualan' ? 'penjualan' : 'kategori');
        return `/laporan/export-analisis-penjualan/${format}?${params.toString()}`;
    };

    const rows = useMemo(() => {
        return (kategori?.per_kategori ?? []).map((row) => {
            const pendapatanKotor = Number(row.pendapatan_kotor ?? 0);
            const totalModal = Number(row.total_modal ?? 0);
            const margin = Number(row.margin ?? 0);
            const pendapatanBersih = pendapatanKotor - totalModal;
            return {
                ...row,
                pendapatan_kotor: pendapatanKotor,
                total_modal: totalModal,
                margin,
                pendapatan_bersih: pendapatanBersih,
            };
        });
    }, [kategori]);

    const maxValue = useMemo(() => {
        if (rows.length === 0) return 0;
        return rows.reduce((max, row) => {
            const localMax = Math.max(
                row.pendapatan_kotor,
                row.total_modal,
                row.margin,
                row.pendapatan_bersih ?? 0
            );
            return localMax > max ? localMax : max;
        }, 0);
    }, [rows]);

    // isLoading only checks for processing state, not data presence
    // because data might be undefined when tipe is specific (penjualan OR kategori)
    const isLoading = processing;

    return (
        <AppLayout breadcrumbs={[{ title: 'Analisis Penjualan', href: '/laporan/analisis-penjualan' }]}>
            <Head title="Analisis Penjualan" />
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Analisis Penjualan</h1>
                        <p className="text-sm text-muted-foreground">
                            Laporan penjualan produk dan pendapatan per kategori.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={buildExportUrl('excel')} download>
                                <FileSpreadsheet className="mr-2 h-4 w-4" />
                                Export Excel
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a href={buildExportUrl('pdf')} target="_blank">
                                <FileText className="mr-2 h-4 w-4" />
                                Export PDF
                            </a>
                        </Button>
                    </div>
                </div>

                {/* Filter Card */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Filter Laporan</CardTitle>
                        <CardDescription>
                            Filter data berdasarkan tanggal, kategori, dan rentang harga.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                            <div className="space-y-1.5">
                                <Label htmlFor="tanggal_mulai" className="text-xs font-medium">Tanggal Mulai</Label>
                                <Input
                                    id="tanggal_mulai"
                                    type="date"
                                    value={data.tanggal_mulai}
                                    onChange={(e) => setData('tanggal_mulai', e.target.value)}
                                    className="h-9"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="tanggal_selesai" className="text-xs font-medium">Tanggal Selesai</Label>
                                <Input
                                    id="tanggal_selesai"
                                    type="date"
                                    value={data.tanggal_selesai}
                                    onChange={(e) => setData('tanggal_selesai', e.target.value)}
                                    className="h-9"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="kategori_id" className="text-xs font-medium">Kategori</Label>
                                <Select
                                    value={data.kategori_id}
                                    onValueChange={(value) => setData('kategori_id', value)}
                                >
                                    <SelectTrigger id="kategori_id" className="h-9">
                                        <SelectValue placeholder="Semua kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua kategori</SelectItem>
                                        {(kategori_options ?? []).map((k: any) => (
                                            <SelectItem key={k.id} value={String(k.id)}>
                                                {k.nama}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="harga_min" className="text-xs font-medium">Harga Min</Label>
                                <Input
                                    id="harga_min"
                                    type="number"
                                    min={0}
                                    value={data.harga_min}
                                    onChange={(e) => setData('harga_min', e.target.value)}
                                    placeholder="0"
                                    className="h-9"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="harga_max" className="text-xs font-medium">Harga Max</Label>
                                <Input
                                    id="harga_max"
                                    type="number"
                                    min={0}
                                    value={data.harga_max}
                                    onChange={(e) => setData('harga_max', e.target.value)}
                                    placeholder="999999"
                                    className="h-9"
                                />
                            </div>
                            <div className="flex items-end gap-2 lg:col-span-6 xl:col-span-1">
                                <Button onClick={submit} disabled={processing} size="sm" className="flex-1 sm:flex-none">
                                    {processing ? 'Memuat...' : 'Terapkan'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleReset}
                                    className="flex-1 sm:flex-none"
                                >
                                    Reset
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabs */}
                <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
                    <div className="flex items-center justify-between">
                        <TabsList>
                            <TabsTrigger value="penjualan">
                                Penjualan Produk
                            </TabsTrigger>
                            <TabsTrigger value="kategori">
                                Pendapatan Kategori
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    {/* Penjualan Tab */}
                    <TabsContent value="penjualan" className="space-y-4">
                        {isLoading ? (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                <SkeletonCard />
                                <SkeletonCard />
                                <SkeletonCard />
                                <SkeletonCard />
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardDescription>Total Pendapatan</CardDescription>
                                        <CardTitle className="text-2xl">
                                            {formatRupiah(penjualan?.total_pendapatan ?? 0)}
                                        </CardTitle>
                                    </CardHeader>
                                </Card>
                            </div>
                        )}

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">Top 10 Produk</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {isLoading ? (
                                    <SkeletonTable rows={5} />
                                ) : !penjualan?.top_produk || penjualan.top_produk.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <div className="text-muted-foreground text-sm">Belum ada data penjualan untuk periode ini.</div>
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b bg-muted/50">
                                                    <th className="py-2 px-3 text-left font-medium">Produk</th>
                                                    <th className="py-2 px-3 text-right font-medium">Terjual</th>
                                                    <th className="py-2 px-3 text-right font-medium">Pendapatan</th>
                                                    <th className="py-2 px-3 text-right font-medium">Rata-rata/Trx</th>
                                                    <th className="py-2 px-3 text-right font-medium">Kontribusi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {penjualan.top_produk.map((row, idx) => (
                                                    <tr key={idx} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                                        <td className="py-2 px-3">
                                                            <div className="font-medium">{row.nama}</div>
                                                        </td>
                                                        <td className="py-2 px-3 text-right">{row.total_terjual}</td>
                                                        <td className="py-2 px-3 text-right">{formatRupiah(row.pendapatan)}</td>
                                                        <td className="py-2 px-3 text-right">{formatRupiah(row.rata_rata_per_transaksi ?? 0)}</td>
                                                        <td className="py-2 px-3 text-right">
                                                            <Badge variant="secondary">{row.kontribusi_persen ?? 0}%</Badge>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">Ringkasan per Tipe</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {isLoading ? (
                                    <SkeletonTable rows={3} />
                                ) : !penjualan?.ringkasan_tipe || penjualan.ringkasan_tipe.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <div className="text-muted-foreground text-sm">Belum ada data ringkasan tipe.</div>
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b bg-muted/50">
                                                    <th className="py-2 px-3 text-left font-medium">Tipe</th>
                                                    <th className="py-2 px-3 text-right font-medium">Terjual</th>
                                                    <th className="py-2 px-3 text-right font-medium">Transaksi</th>
                                                    <th className="py-2 px-3 text-right font-medium">Pendapatan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {penjualan.ringkasan_tipe.map((row, idx) => (
                                                    <tr key={idx} className="border-b last:border-0 hover:bg-muted/30 transition-colors">
                                                        <td className="py-2 px-3">
                                                            <Badge variant="outline">{row.tipe ?? '-'}</Badge>
                                                        </td>
                                                        <td className="py-2 px-3 text-right">{row.total_terjual}</td>
                                                        <td className="py-2 px-3 text-right">{row.jumlah_transaksi}</td>
                                                        <td className="py-2 px-3 text-right font-medium">{formatRupiah(row.pendapatan)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Kategori Tab */}
                    <TabsContent value="kategori" className="space-y-4">
                        {isLoading ? (
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                <SkeletonCard />
                                <SkeletonCard />
                                <SkeletonCard />
                                <SkeletonCard />
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardDescription className="text-xs">Total Pendapatan Kotor</CardDescription>
                                        <CardTitle className="text-lg md:text-xl">
                                            {formatRupiah(kategori?.ringkasan?.total_pendapatan_kotor ?? 0)}
                                        </CardTitle>
                                    </CardHeader>
                                </Card>
                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardDescription className="text-xs">Total Modal</CardDescription>
                                        <CardTitle className="text-lg md:text-xl">
                                            {formatRupiah(kategori?.ringkasan?.total_modal ?? 0)}
                                        </CardTitle>
                                    </CardHeader>
                                </Card>
                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardDescription className="text-xs">Total Margin</CardDescription>
                                        <CardTitle className="text-lg md:text-xl text-green-600">
                                            {formatRupiah(kategori?.ringkasan?.total_margin ?? 0)}
                                        </CardTitle>
                                    </CardHeader>
                                </Card>
                                <Card>
                                    <CardHeader className="pb-2">
                                        <CardDescription className="text-xs">Rata-rata Margin</CardDescription>
                                        <CardTitle className="text-lg md:text-xl">
                                            {formatRupiah(kategori?.ringkasan?.rata_rata_margin_per_kategori ?? 0)}
                                        </CardTitle>
                                    </CardHeader>
                                </Card>
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <span className="w-2 h-2 rounded-full bg-green-500"></span>
                                        Margin Tertinggi
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {isLoading ? (
                                        <Skeleton className="h-16 w-full" />
                                    ) : kategori?.ringkasan?.kategori_margin_tertinggi ? (
                                        <div className="space-y-1">
                                            <div className="font-semibold">
                                                {kategori.ringkasan.kategori_margin_tertinggi.kategori}
                                            </div>
                                            <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                                <span>Margin: <span className="font-medium text-foreground">{formatRupiah(kategori.ringkasan.kategori_margin_tertinggi.margin)}</span></span>
                                                <Badge variant="success">{kategori.ringkasan.kategori_margin_tertinggi.margin_persen}%</Badge>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="text-sm text-muted-foreground">Belum ada data.</div>
                                    )}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <span className="w-2 h-2 rounded-full bg-amber-500"></span>
                                        Margin Terendah
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {isLoading ? (
                                        <Skeleton className="h-16 w-full" />
                                    ) : kategori?.ringkasan?.kategori_margin_terendah ? (
                                        <div className="space-y-1">
                                            <div className="font-semibold">
                                                {kategori.ringkasan.kategori_margin_terendah.kategori}
                                            </div>
                                            <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                                <span>Margin: <span className="font-medium text-foreground">{formatRupiah(kategori.ringkasan.kategori_margin_terendah.margin)}</span></span>
                                                <Badge variant="warning">{kategori.ringkasan.kategori_margin_terendah.margin_persen}%</Badge>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="text-sm text-muted-foreground">Belum ada data.</div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">Perbandingan Kotor vs Modal per Kategori</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {isLoading ? (
                                    <SkeletonTable rows={5} />
                                ) : rows.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <div className="text-muted-foreground text-sm">Belum ada data untuk filter yang dipilih.</div>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {rows.map((row) => {
                                            const kotorWidth = maxValue > 0 ? (row.pendapatan_kotor / maxValue) * 100 : 0;
                                            const modalWidth = maxValue > 0 ? (row.total_modal / maxValue) * 100 : 0;
                                            return (
                                                <div key={row.kategori_id} className="space-y-2">
                                                    <div className="flex items-center justify-between text-sm">
                                                        <div className="font-medium">{row.kategori}</div>
                                                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                                            <span>Kotor: <span className="font-medium">{formatRupiah(row.pendapatan_kotor)}</span></span>
                                                            <span>Modal: <span className="font-medium">{formatRupiah(row.total_modal)}</span></span>
                                                        </div>
                                                    </div>
                                                    <div className="flex h-3 items-center gap-0.5 rounded-sm bg-muted overflow-hidden">
                                                        <Tooltip>
                                                            <TooltipTrigger className="h-full rounded-sm bg-sky-500 transition-all" style={{ width: `${Math.max(kotorWidth, 2)}%` }} />
                                                            <TooltipContent side="top">
                                                                <div className="text-xs">
                                                                    <div className="font-semibold">{row.kategori}</div>
                                                                    <div>Kotor: {formatRupiah(row.pendapatan_kotor)}</div>
                                                                </div>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger className="h-full rounded-sm bg-slate-400 transition-all" style={{ width: `${Math.max(modalWidth, 2)}%` }} />
                                                            <TooltipContent side="top">
                                                                <div className="text-xs">
                                                                    <div className="font-semibold">{row.kategori}</div>
                                                                    <div>Modal: {formatRupiah(row.total_modal)}</div>
                                                                </div>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </div>
                                                    <div className="flex gap-3 text-xs text-muted-foreground">
                                                        <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-sm bg-sky-500"></span> Kotor</span>
                                                        <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-sm bg-slate-400"></span> Modal</span>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-medium">Pendapatan Kotor vs Bersih</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {isLoading ? (
                                    <SkeletonTable rows={5} />
                                ) : rows.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <div className="text-muted-foreground text-sm">Belum ada data untuk filter yang dipilih.</div>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {rows.map((row) => {
                                            const kotorWidth = maxValue > 0 ? (row.pendapatan_kotor / maxValue) * 100 : 0;
                                            const bersihWidth = maxValue > 0 ? ((row.pendapatan_bersih ?? 0) / maxValue) * 100 : 0;
                                            return (
                                                <div key={row.kategori_id} className="space-y-2">
                                                    <div className="flex items-center justify-between text-sm">
                                                        <div className="font-medium">{row.kategori}</div>
                                                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                                            <span>Kotor: <span className="font-medium">{formatRupiah(row.pendapatan_kotor)}</span></span>
                                                            <span>Bersih: <span className="font-medium text-green-600">{formatRupiah(row.pendapatan_bersih ?? 0)}</span></span>
                                                        </div>
                                                    </div>
                                                    <div className="flex h-3 items-center gap-0.5 rounded-sm bg-muted overflow-hidden">
                                                        <Tooltip>
                                                            <TooltipTrigger className="h-full rounded-sm bg-sky-500 transition-all" style={{ width: `${Math.max(kotorWidth, 2)}%` }} />
                                                            <TooltipContent side="top">
                                                                <div className="text-xs">
                                                                    <div className="font-semibold">{row.kategori}</div>
                                                                    <div>Kotor: {formatRupiah(row.pendapatan_kotor)}</div>
                                                                </div>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger className="h-full rounded-sm bg-emerald-500 transition-all" style={{ width: `${Math.max(bersihWidth, 2)}%` }} />
                                                            <TooltipContent side="top">
                                                                <div className="text-xs">
                                                                    <div className="font-semibold">{row.kategori}</div>
                                                                    <div>Bersih: {formatRupiah(row.pendapatan_bersih ?? 0)}</div>
                                                                </div>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </div>
                                                    <div className="flex gap-3 text-xs text-muted-foreground">
                                                        <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-sm bg-sky-500"></span> Kotor</span>
                                                        <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-sm bg-emerald-500"></span> Bersih</span>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
