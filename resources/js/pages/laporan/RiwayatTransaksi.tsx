import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    Banknote,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock,
    FileSpreadsheet,
    FileText,
    Minus,
    Receipt,
    RotateCcw,
    Store,
    TrendingDown,
    TrendingUp,
    Users,
    Wallet,
} from 'lucide-react';

type TipeLaporan = 'shift' | 'harian';

interface ShiftRow {
    id: number;
    user?: { id: number; name: string } | null;
    cabang?: { id: number; nama: string; kode?: string | null } | null;
    waktu_buka: string;
    waktu_tutup?: string | null;
    status: string;
    nama_kasir?: string | null;
    nama_kasir_list?: string[];
    total_transaksi: number;
    total_penjualan: number;
    akurasi_kas: number;
    durasi_shift_menit?: number | null;
}

interface ShiftStatistik {
    total_shift: number;
    total_penjualan: number;
    rata_rata_per_shift: number;
    shift_dengan_selisih: number;
    total_selisih: number;
}

interface HarianStatistik {
    tanggal?: string;
    total_shift: number;
    shift_buka: number;
    shift_tutup: number;
    total_kasir: number;
    total_transaksi: number;
    total_penjualan: number;
    total_tunai: number;
    total_qris: number;
    rata_rata_per_shift: number;
    rata_rata_per_transaksi: number;
}

interface PerbandinganKemarin {
    tanggal: string;
    total_penjualan: number;
    total_transaksi: number;
    perubahan_penjualan_persen: number | null;
    perubahan_transaksi_persen: number | null;
}

interface GrafikPerJamRow {
    jam: number;
    total_penjualan: number;
    jumlah_transaksi: number;
}

interface ProdukTerlarisRow {
    produk_id: number;
    nama: string;
    total_terjual: number;
    pendapatan: number;
}

interface PerformaCabangRow {
    cabang_id: number;
    cabang_nama?: string | null;
    total_shift: number;
    total_transaksi: number;
    total_penjualan: number;
}

interface PerformaKasirRow {
    user_id: number;
    user_nama?: string | null;
    total_shift: number;
    total_transaksi: number;
    total_penjualan: number;
}

interface FilterAktif {
    tanggal_mulai?: string | null;
    tanggal_selesai?: string | null;
    cabang_id?: number | null;
    tipe?: string;
}

interface CabangOption {
    id: number;
    nama: string;
    kode?: string | null;
}

interface Pagination<T> {
    data: T[];
    total: number;
    current_page?: number;
    last_page?: number;
    per_page?: number;
    from?: number | null;
    to?: number | null;
}

interface Props {
    shift?: Pagination<ShiftRow>;
    shift_statistik?: ShiftStatistik;
    harian_statistik?: HarianStatistik;
    grafik_per_jam?: GrafikPerJamRow[];
    produk_terlaris?: ProdukTerlarisRow[];
    performa_per_cabang?: PerformaCabangRow[];
    performa_per_kasir?: PerformaKasirRow[];
    perbandingan_kemarin?: PerbandinganKemarin | null;
    filter_aktif: FilterAktif;
    cabang_options: CabreraOption[];
    tipe: string;
    user?: {
        role: string;
        is_it_support: boolean;
        authorized_branch_count: number;
    };
}

const RUPIAH_FORMATTER = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

function formatRupiah(value: number | string | null | undefined) {
    const num = typeof value === 'string' ? Number(value) : value ?? 0;
    if (!Number.isFinite(num)) return '-';
    return RUPIAH_FORMATTER.format(num);
}

function formatDateTime(value: string | null | undefined) {
    if (!value) return '-';
    try {
        return new Date(value).toLocaleString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return value;
    }
}

function formatTanggalPanjang(value: string | null | undefined) {
    if (!value) return '-';
    try {
        return new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return value;
    }
}

function formatDurasi(menit: number | null | undefined) {
    if (menit === null || menit === undefined) return '-';
    const jam = Math.floor(menit / 60);
    const sisaMenit = Math.round(menit % 60);
    if (jam === 0) return `${sisaMenit} mnt`;
    return `${jam} jam ${sisaMenit} mnt`;
}

function toISODate(date: Date) {
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function todayISO() {
    return toISODate(new Date());
}

function daysAgoISO(days: number) {
    const d = new Date();
    d.setDate(d.getDate() - days);
    return toISODate(d);
}

function startOfMonthISO() {
    const d = new Date();
    d.setDate(1);
    return toISODate(d);
}

function StatusShiftBadge({ status }: { status: string }) {
    if (status === 'buka') {
        return (
            <Badge className="border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-50 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-400">
                Sedang Buka
            </Badge>
        );
    }
    return (
        <Badge className="border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-400">
            Tutup
        </Badge>
    );
}

function SelisihKas({ value }: { value: number }) {
    if (value === 0) {
        return <span className="text-muted-foreground">Rp0</span>;
    }
    const positif = value > 0;
    return (
        <span className={positif ? 'font-medium text-emerald-600 dark:text-emerald-400' : 'font-medium text-red-600 dark:text-red-500'}>
            {positif ? '+' : ''}
            {formatRupiah(value)}
        </span>
    );
}

function PerubahanBadge({ persen }: { persen: number | null | undefined }) {
    if (persen === null || persen === undefined) {
        return (
            <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                <Minus className="h-3 w-3" /> Data kemarin kosong
            </span>
        );
    }
    if (persen === 0) {
        return (
            <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                <Minus className="h-3 w-3" /> Sama dengan kemarin
            </span>
        );
    }
    const naik = persen > 0;
    return (
        <span
            className={
                naik
                    ? 'inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400'
                    : 'inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-500'
            }
        >
            {naik ? <TrendingUp className="h-3 w-3" /> : <TrendingDown className="h-3 w-3" />}
            {naik ? '+' : ''}
            {persen}% vs kemarin
        </span>
    );
}

function GrafikPerJam({ data }: { data: GrafikPerJamRow[] }) {
    const byJam = useMemo(() => {
        const map = new Map<number, GrafikPerJamRow>();
        data.forEach((row) => map.set(Number(row.jam), row));
        return Array.from({ length: 24 }, (_, jam) => ({
            jam,
            total_penjualan: Number(map.get(jam)?.total_penjualan ?? 0),
            jumlah_transaksi: Number(map.get(jam)?.jumlah_transaksi ?? 0),
        }));
    }, [data]);

    const maxValue = useMemo(
        () => byJam.reduce((max, row) => (row.total_penjualan > max ? row.total_penjualan : max), 0),
        [byJam],
    );

    const width = 720;
    const height = 200;
    const paddingBottom = 24;
    const barGap = 3;
    const barWidth = (width - barGap * 23) / 24;

    return (
        <svg
            viewBox={`0 0 ${width} ${height + paddingBottom}`}
            className="w-full"
            role="img"
            aria-label="Grafik penjualan per jam"
        >
            {/* garis bantu horizontal */}
            {[0.25, 0.5, 0.75, 1].map((f) => (
                <line
                    key={f}
                    x1={0}
                    x2={width}
                    y1={height - height * f}
                    y2={height - height * f}
                    className="stroke-border"
                    strokeWidth={1}
                    strokeDasharray="4 4"
                />
            ))}
            {byJam.map((row, i) => {
                const barHeight = maxValue > 0 ? (row.total_penjualan / maxValue) * (height - 4) : 0;
                const x = i * (barWidth + barGap);
                const y = height - barHeight;
                return (
                    <g key={row.jam}>
                        <rect
                            x={x}
                            y={y}
                            width={barWidth}
                            height={barHeight}
                            rx={2}
                            className="fill-primary/80 transition-colors hover:fill-primary"
                        >
                            <title>
                                {String(row.jam).padStart(2, '0')}:00 — {row.jumlah_transaksi} transaksi, {formatRupiah(row.total_penjualan)}
                            </title>
                        </rect>
                        {row.jam % 3 === 0 && (
                            <text
                                x={x + barWidth / 2}
                                y={height + 16}
                                textAnchor="middle"
                                className="fill-muted-foreground text-[9px]"
                            >
                                {String(row.jam).padStart(2, '0')}
                            </text>
                        )}
                    </g>
                );
            })}
        </svg>
    );
}

export default function RiwayatTransaksi({
    shift,
    shift_statistik,
    harian_statistik,
    grafik_per_jam,
    produk_terlaris,
    performa_per_cabang,
    performa_per_kasir,
    perbandingan_kemarin,
    filter_aktif,
    cabang_options,
    tipe,
    user,
}: Props) {
    const [activeTab, setActiveTab] = useState<TipeLaporan>(tipe === 'harian' ? 'harian' : 'shift');
    const [rangeMulai, setRangeMulai] = useState(
        filter_aktif?.tipe !== 'harian' ? filter_aktif?.tanggal_mulai ?? '' : '',
    );
    const [rangeSelesai, setRangeSelesai] = useState(
        filter_aktif?.tipe !== 'harian' ? filter_aktif?.tanggal_selesai ?? '' : '',
    );
    const [tanggalHarian, setTanggalHarian] = useState(
        filter_aktif?.tipe === 'harian' ? filter_aktif?.tanggal_mulai ?? todayISO() : todayISO(),
    );
    const [cabangId, setCabangId] = useState(filter_aktif?.cabang_id ? String(filter_aktif.cabang_id) : '');
    const [processing, setProcessing] = useState(false);

    const kirim = (params: Record<string, string>) => {
        router.get('/laporan/riwayat-transaksi', params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    const applyFilter = (nextTipe: TipeLaporan = activeTab, page?: number) => {
        const params: Record<string, string> = { tipe: nextTipe };
        if (nextTipe === 'harian') {
            if (tanggalHarian) params.tanggal_mulai = tanggalHarian;
        } else {
            if (rangeMulai) params.tanggal_mulai = rangeMulai;
            if (rangeSelesai) params.tanggal_selesai = rangeSelesai;
        }
        if (cabangId) params.cabang_id = cabangId;
        if (page && page > 1) params.page = String(page);
        kirim(params);
    };

    const handleTabChange = (value: string) => {
        const nextTipe: TipeLaporan = value === 'harian' ? 'harian' : 'shift';
        setActiveTab(nextTipe);
        applyFilter(nextTipe);
    };

    const applyQuickDay = (label: 'hari_ini' | 'kemarin') => {
        const date = label === 'hari_ini' ? todayISO() : daysAgoISO(1);
        setTanggalHarian(date);
        setActiveTab('harian');
        const params: Record<string, string> = { tipe: 'harian', tanggal_mulai: date };
        if (cabangId) params.cabang_id = cabangId;
        kirim(params);
    };

    const applyQuickRange = (days: number, label?: 'bulan_ini') => {
        const mulai = label === 'bulan_ini' ? startOfMonthISO() : daysAgoISO(days - 1);
        const selesai = todayISO();
        setRangeMulai(mulai);
        setRangeSelesai(selesai);
        setActiveTab('shift');
        const params: Record<string, string> = { tipe: 'shift', tanggal_mulai: mulai, tanggal_selesai: selesai };
        if (cabangId) params.cabang_id = cabangId;
        kirim(params);
    };

    const handleReset = () => {
        setCabangId('');
        if (activeTab === 'harian') {
            setTanggalHarian(todayISO());
            kirim({ tipe: 'harian', tanggal_mulai: todayISO() });
        } else {
            setRangeMulai('');
            setRangeSelesai('');
            kirim({ tipe: 'shift' });
        }
    };

    const buildExportUrl = (format: 'excel' | 'pdf') => {
        const params = new URLSearchParams();
        if (activeTab === 'harian') {
            if (tanggalHarian) params.set('tanggal_mulai', tanggalHarian);
        } else {
            if (rangeMulai) params.set('tanggal_mulai', rangeMulai);
            if (rangeSelesai) params.set('tanggal_selesai', rangeSelesai);
        }
        if (cabangId) params.set('cabang_id', cabangId);
        params.set('tipe', activeTab);
        return `/laporan/export-riwayat-transaksi/${format}?${params.toString()}`;
    };

    const jamTersibuk = useMemo(() => {
        if (!grafik_per_jam || grafik_per_jam.length === 0) return [];
        return [...grafik_per_jam]
            .sort((a, b) => Number(b.total_penjualan) - Number(a.total_penjualan))
            .slice(0, 5);
    }, [grafik_per_jam]);

    const currentPage = shift?.current_page ?? 1;
    const lastPage = shift?.last_page ?? 1;

    return (
        <AppLayout breadcrumbs={[{ title: 'Riwayat Transaksi', href: '/laporan/riwayat-transaksi' }]}>
            <Head title="Riwayat Transaksi" />
            <div className="space-y-6">
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Riwayat Transaksi</h1>
                        <p className="text-sm text-muted-foreground">
                            Laporan shift kasir dan ringkasan harian berdasarkan periode.
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

                <Tabs value={activeTab} onValueChange={handleTabChange} className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="shift">Laporan per Shift</TabsTrigger>
                        <TabsTrigger value="harian">Laporan per Hari</TabsTrigger>
                    </TabsList>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Filter Laporan</CardTitle>
                            <CardDescription>
                                {activeTab === 'harian'
                                    ? 'Pilih 1 tanggal untuk melihat ringkasan operasional hari itu.'
                                    : 'Pilih rentang tanggal untuk melihat daftar shift kasir.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <form
                                className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    applyFilter(activeTab);
                                }}
                            >
                                {activeTab === 'harian' ? (
                                    <div className="space-y-1.5 lg:col-span-2">
                                        <Label htmlFor="tanggal_harian" className="text-xs font-medium">
                                            Tanggal
                                        </Label>
                                        <Input
                                            id="tanggal_harian"
                                            type="date"
                                            value={tanggalHarian}
                                            max={todayISO()}
                                            onChange={(e) => setTanggalHarian(e.target.value)}
                                            className="h-9"
                                        />
                                    </div>
                                ) : (
                                    <>
                                        <div className="space-y-1.5">
                                            <Label htmlFor="tanggal_mulai" className="text-xs font-medium">
                                                Tanggal Mulai
                                            </Label>
                                            <Input
                                                id="tanggal_mulai"
                                                type="date"
                                                value={rangeMulai}
                                                onChange={(e) => setRangeMulai(e.target.value)}
                                                className="h-9"
                                            />
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor="tanggal_selesai" className="text-xs font-medium">
                                                Tanggal Selesai
                                            </Label>
                                            <Input
                                                id="tanggal_selesai"
                                                type="date"
                                                value={rangeSelesai}
                                                onChange={(e) => setRangeSelesai(e.target.value)}
                                                className="h-9"
                                            />
                                        </div>
                                    </>
                                )}
                                <div className="space-y-1.5">
                                    <Label htmlFor="cabang_id" className="text-xs font-medium">
                                        Cabang
                                    </Label>
                                    <Select
                                        value={cabangId || 'all'}
                                        onValueChange={(value) => setCabangId(value === 'all' ? '' : value)}
                                    >
                                        <SelectTrigger id="cabang_id" className="h-9">
                                            <SelectValue placeholder="Semua cabang" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {!(user?.authorized_branch_count === 1 && !user?.is_it_support) && (
                                                <SelectItem value="all">Semua cabang</SelectItem>
                                            )}
                                            {(cabang_options ?? []).map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>
                                                    {c.nama} {c.kode ? `(${c.kode})` : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex items-end gap-2 lg:col-span-2">
                                    <Button type="submit" size="sm" disabled={processing}>
                                        Terapkan
                                    </Button>
                                    <Button type="button" variant="outline" size="sm" onClick={handleReset}>
                                        <RotateCcw className="mr-2 h-3.5 w-3.5" />
                                        Reset
                                    </Button>
                                </div>
                            </form>

                            <div className="flex flex-wrap items-center gap-2 border-t pt-3">
                                <span className="text-xs text-muted-foreground">Pintasan cepat:</span>
                                {activeTab === 'harian' ? (
                                    <>
                                        <Button variant="secondary" size="sm" className="h-7 text-xs" onClick={() => applyQuickDay('hari_ini')}>
                                            Hari Ini
                                        </Button>
                                        <Button variant="secondary" size="sm" className="h-7 text-xs" onClick={() => applyQuickDay('kemarin')}>
                                            Kemarin
                                        </Button>
                                    </>
                                ) : (
                                    <>
                                        <Button variant="secondary" size="sm" className="h-7 text-xs" onClick={() => applyQuickRange(7)}>
                                            7 Hari Terakhir
                                        </Button>
                                        <Button variant="secondary" size="sm" className="h-7 text-xs" onClick={() => applyQuickRange(30)}>
                                            30 Hari Terakhir
                                        </Button>
                                        <Button variant="secondary" size="sm" className="h-7 text-xs" onClick={() => applyQuickRange(0, 'bulan_ini')}>
                                            Bulan Ini
                                        </Button>
                                    </>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <TabsContent value="shift" className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription className="flex items-center gap-1.5">
                                        <Clock className="h-3.5 w-3.5" /> Total Shift
                                    </CardDescription>
                                    <CardTitle className="text-2xl">{shift_statistik?.total_shift ?? 0}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription className="flex items-center gap-1.5">
                                        <Wallet className="h-3.5 w-3.5" /> Total Penjualan
                                    </CardDescription>
                                    <CardTitle className="text-2xl">
                                        {formatRupiah(shift_statistik?.total_penjualan ?? 0)}
                                    </CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription className="flex items-center gap-1.5">
                                        <Receipt className="h-3.5 w-3.5" /> Rata-rata / Shift
                                    </CardDescription>
                                    <CardTitle className="text-2xl">
                                        {formatRupiah(shift_statistik?.rata_rata_per_shift ?? 0)}
                                    </CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription className="flex items-center gap-1.5">
                                        <Banknote className="h-3.5 w-3.5" /> Shift dengan Selisih Kas
                                    </CardDescription>
                                    <CardTitle
                                        className={
                                            (shift_statistik?.shift_dengan_selisih ?? 0) > 0
                                                ? 'text-2xl text-amber-600 dark:text-amber-400'
                                                : 'text-2xl'
                                        }
                                    >
                                        {shift_statistik?.shift_dengan_selisih ?? 0}
                                    </CardTitle>
                                    <div className="text-xs text-muted-foreground">
                                        Total selisih: <SelisihKas value={shift_statistik?.total_selisih ?? 0} />
                                    </div>
                                </CardHeader>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium">Daftar Shift</CardTitle>
                                <CardDescription>
                                    {filter_aktif?.tanggal_mulai && filter_aktif?.tanggal_selesai
                                        ? `Periode ${filter_aktif.tanggal_mulai} s/d ${filter_aktif.tanggal_selesai}`
                                        : 'Semua periode'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-xs text-muted-foreground">
                                                <th className="py-2 pr-4 text-left">Shift</th>
                                                <th className="py-2 pr-4 text-left">Cabang</th>
                                                <th className="py-2 pr-4 text-left">Kasir</th>
                                                <th className="py-2 pr-4 text-right">Durasi</th>
                                                <th className="py-2 pr-4 text-right">Transaksi</th>
                                                <th className="py-2 pr-4 text-right">Total Penjualan</th>
                                                <th className="py-2 pr-4 text-right">Selisih Kas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(shift?.data ?? []).map((row) => (
                                                <tr key={row.id} className="border-b last:border-0">
                                                    <td className="py-2.5 pr-4 align-top">
                                                        <div className="flex items-center gap-2 font-medium">
                                                            #{row.id}
                                                            <StatusShiftBadge status={row.status} />
                                                        </div>
                                                        <div className="mt-1 text-xs text-muted-foreground">
                                                            {formatDateTime(row.waktu_buka)}
                                                            {row.waktu_tutup ? ` – ${formatDateTime(row.waktu_tutup)}` : ' – masih berjalan'}
                                                        </div>
                                                    </td>
                                                    <td className="py-2.5 pr-4 align-top">
                                                        <div className="flex items-center gap-1.5 font-medium">
                                                            <Store className="h-3.5 w-3.5 text-muted-foreground" />
                                                            {row.cabang?.nama ?? '-'}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">{row.cabang?.kode ?? ''}</div>
                                                    </td>
                                                    <td className="py-2.5 pr-4 align-top">
                                                        <ul className="list-inside list-disc space-y-0.5">
                                                            {(row.nama_kasir_list ?? []).length === 0 && row.nama_kasir && (
                                                                <li>{row.nama_kasir}</li>
                                                            )}
                                                            {(row.nama_kasir_list ?? []).map((n, idx) => (
                                                                <li key={idx}>{n}</li>
                                                            ))}
                                                            {(row.nama_kasir_list ?? []).length === 0 && !row.nama_kasir && (
                                                                <li className="list-none text-muted-foreground">-</li>
                                                            )}
                                                        </ul>
                                                    </td>
                                                    <td className="py-2.5 pr-4 text-right align-top">
                                                        {formatDurasi(row.durasi_shift_menit)}
                                                    </td>
                                                    <td className="py-2.5 pr-4 text-right align-top">{row.total_transaksi}</td>
                                                    <td className="py-2.5 pr-4 text-right align-top font-medium">
                                                        {formatRupiah(row.total_penjualan)}
                                                    </td>
                                                    <td className="py-2.5 pr-4 text-right align-top">
                                                        <SelisihKas value={row.akurasi_kas} />
                                                    </td>
                                                </tr>
                                            ))}
                                            {(!shift?.data || shift.data.length === 0) && (
                                                <tr>
                                                    <td colSpan={7} className="py-8 text-center text-sm text-muted-foreground">
                                                        Tidak ada data shift untuk filter ini.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                {shift && shift.total > 0 && (
                                    <div className="mt-4 flex flex-col items-center justify-between gap-2 border-t pt-4 sm:flex-row">
                                        <div className="text-xs text-muted-foreground">
                                            Menampilkan {shift.from ?? 0}–{shift.to ?? 0} dari {shift.total} shift
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={currentPage <= 1 || processing}
                                                onClick={() => applyFilter('shift', currentPage - 1)}
                                            >
                                                <ChevronLeft className="mr-1 h-3.5 w-3.5" />
                                                Sebelumnya
                                            </Button>
                                            <span className="text-xs text-muted-foreground">
                                                Halaman {currentPage} dari {lastPage}
                                            </span>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={currentPage >= lastPage || processing}
                                                onClick={() => applyFilter('shift', currentPage + 1)}
                                            >
                                                Berikutnya
                                                <ChevronRight className="ml-1 h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="harian" className="space-y-4">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <CalendarDays className="h-4 w-4" />
                            {formatTanggalPanjang(harian_statistik?.tanggal ?? tanggalHarian)}
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription className="flex items-center gap-1.5">
                                        <Wallet className="h-3.5 w-3.5" /> Total Penjualan
                                    </CardDescription>
                                    <CardTitle className="text-2xl">
                                        {formatRupiah(harian_statistik?.total_penjualan ?? 0)}
                                    </CardTitle>
                                    {perbandingan_kemarin && (
                                        <PerubahanBadge persen={perbandingan_kemarin.perubahan_penjualan_persen} />
                                    )}
                                    <div className="text-xs text-muted-foreground">
                                        Tunai {formatRupiah(harian_statistik?.total_tunai ?? 0)} · QRIS{' '}
                                        {formatRupiah(harian_statistik?.total_qris ?? 0)}
                                    </div>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription className="flex items-center gap-1.5">
                                        <Receipt className="h-3.5 w-3.5" /> Total Transaksi
                                    </CardDescription>
                                    <CardTitle className="text-2xl">{harian_statistik?.total_transaksi ?? 0}</CardTitle>
                                    {perbandingan_kemarin && (
                                        <PerubahanBadge persen={perbandingan_kemarin.perubahan_transaksi_persen} />
                                    )}
                                    <div className="text-xs text-muted-foreground">
                                        Rata-rata {formatRupiah(harian_statistik?.rata_rata_per_transaksi ?? 0)}/trx
                                    </div>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription className="flex items-center gap-1.5">
                                        <Clock className="h-3.5 w-3.5" /> Shift Berjalan
                                    </CardDescription>
                                    <CardTitle className="text-2xl">{harian_statistik?.total_shift ?? 0}</CardTitle>
                                    <div className="text-xs text-muted-foreground">
                                        Buka {harian_statistik?.shift_buka ?? 0} · Tutup {harian_statistik?.shift_tutup ?? 0}
                                    </div>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription className="flex items-center gap-1.5">
                                        <Users className="h-3.5 w-3.5" /> Kasir Aktif
                                    </CardDescription>
                                    <CardTitle className="text-2xl">{harian_statistik?.total_kasir ?? 0}</CardTitle>
                                    <div className="text-xs text-muted-foreground">
                                        Rata-rata {formatRupiah(harian_statistik?.rata_rata_per_shift ?? 0)}/shift
                                    </div>
                                </CardHeader>
                            </Card>
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <Card className="lg:col-span-2">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium">Grafik Penjualan per Jam</CardTitle>
                                    <CardDescription>Distribusi penjualan sepanjang hari (00:00–23:00)</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {(!grafik_per_jam || grafik_per_jam.length === 0) ? (
                                        <div className="py-8 text-center text-sm text-muted-foreground">
                                            Belum ada transaksi pada tanggal ini.
                                        </div>
                                    ) : (
                                        <GrafikPerJam data={grafik_per_jam} />
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium">Jam Tersibuk</CardTitle>
                                    <CardDescription>5 jam dengan penjualan tertinggi</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    {jamTersibuk.length === 0 ? (
                                        <div className="py-4 text-center text-sm text-muted-foreground">Belum ada data.</div>
                                    ) : (
                                        <div className="space-y-2 text-xs">
                                            {jamTersibuk.map((row, idx) => (
                                                <div key={row.jam} className="flex items-center justify-between border-b py-1.5 last:border-0">
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="secondary" className="h-5 w-5 justify-center p-0 text-[10px]">
                                                            {idx + 1}
                                                        </Badge>
                                                        <span className="font-medium">{String(row.jam).padStart(2, '0')}:00</span>
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="font-medium">{formatRupiah(row.total_penjualan)}</div>
                                                        <div className="text-muted-foreground">{row.jumlah_transaksi} trx</div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium">10 Produk Terlaris</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {(!produk_terlaris || produk_terlaris.length === 0) ? (
                                        <div className="py-4 text-center text-sm text-muted-foreground">Belum ada produk terjual.</div>
                                    ) : (
                                        <div className="space-y-2 text-xs">
                                            {produk_terlaris.map((p, idx) => (
                                                <div key={p.produk_id} className="flex items-center justify-between border-b py-1.5 last:border-0">
                                                    <div className="flex items-center gap-2">
                                                        <Badge variant="secondary" className="h-5 w-5 justify-center p-0 text-[10px]">
                                                            {idx + 1}
                                                        </Badge>
                                                        <div>
                                                            <div className="font-medium">{p.nama}</div>
                                                            <div className="text-muted-foreground">Terjual {p.total_terjual}</div>
                                                        </div>
                                                    </div>
                                                    <div className="font-medium">{formatRupiah(p.pendapatan)}</div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium">Performa per Kasir</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {(!performa_per_kasir || performa_per_kasir.length === 0) ? (
                                        <div className="py-4 text-center text-sm text-muted-foreground">Belum ada data kasir.</div>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full text-xs">
                                                <thead>
                                                    <tr className="border-b text-muted-foreground">
                                                        <th className="py-2 pr-4 text-left">Kasir</th>
                                                        <th className="py-2 pr-4 text-right">Shift</th>
                                                        <th className="py-2 pr-4 text-right">Transaksi</th>
                                                        <th className="py-2 pr-4 text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {performa_per_kasir.map((k) => (
                                                        <tr key={k.user_id} className="border-b last:border-0">
                                                            <td className="py-2 pr-4 align-top font-medium">
                                                                {k.user_nama ?? `Kasir #${k.user_id}`}
                                                            </td>
                                                            <td className="py-2 pr-4 text-right align-top">{k.total_shift}</td>
                                                            <td className="py-2 pr-4 text-right align-top">{k.total_transaksi}</td>
                                                            <td className="py-2 pr-4 text-right align-top font-medium">
                                                                {formatRupiah(k.total_penjualan)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {performa_per_cabang && performa_per_cabang.length > 1 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium">Performa per Cabang</CardTitle>
                                    <CardDescription>Perbandingan cabang pada tanggal ini</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2 text-xs">
                                        {performa_per_cabang.map((c) => (
                                            <div key={c.cabang_id} className="flex items-center justify-between border-b py-1.5 last:border-0">
                                                <div className="flex items-center gap-1.5 font-medium">
                                                    <Store className="h-3.5 w-3.5 text-muted-foreground" />
                                                    {c.cabang_nama ?? `Cabang #${c.cabang_id}`}
                                                </div>
                                                <div className="flex items-center gap-4">
                                                    <span className="text-muted-foreground">
                                                        Shift {c.total_shift} · Trx {c.total_transaksi}
                                                    </span>
                                                    <span className="font-semibold">{formatRupiah(c.total_penjualan)}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}