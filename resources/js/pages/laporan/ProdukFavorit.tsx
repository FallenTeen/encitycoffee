import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import {
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Cell,
    PieChart, Pie, Legend,
} from 'recharts';
import { TrendingUp, ShoppingBag, Banknote, CalendarDays, Trophy, BarChart2, PieChart as PieIcon } from 'lucide-react';

// ─────────────────────────────────────────────
// Types
// ─────────────────────────────────────────────

interface DailyBreakdown {
    tanggal: string;
    jumlah: number;
}

interface PeakDay {
    tanggal: string;
    jumlah: number;
}

interface ProdukFavoritRow {
    nomor: number;
    produk_id: number;
    nama: string;
    total_terjual: number;
    harga: number;
    total_hasil: number;
    daily_breakdown: DailyBreakdown[];
    peak_day: PeakDay | null;
}

interface RingkasanProps {
    interval: string;
    tanggal_mulai: string;
    tanggal_selesai: string;
    total_jumlah_terjual: number;
    total_hasil: number;
}

interface CabangOption {
    id: number;
    nama: string;
}

interface PageProps {
    data: {
        items: ProdukFavoritRow[];
        ringkasan: RingkasanProps;
    };
    filters: {
        interval: string;
        tanggal_ref: string;
        tanggal_mulai: string;
        tanggal_selesai: string;
        cabang_id?: number | null;
    };
    cabangOptions: CabreraOption[];
    user?: {
        role: string;
        is_it_support: boolean;
        authorized_branch_count: number;
    };
}

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

function formatRupiah(value: number | string | null | undefined) {
    const num = typeof value === 'string' ? Number(value) : value ?? 0;
    if (!Number.isFinite(num)) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(num);
}

function formatRupiahShort(value: number): string {
    if (value >= 1_000_000_000) return `${(value / 1_000_000_000).toFixed(1)}M`;
    if (value >= 1_000_000)     return `${(value / 1_000_000).toFixed(1)}jt`;
    if (value >= 1_000)         return `${(value / 1_000).toFixed(0)}rb`;
    return String(value);
}

function formatTanggal(iso: string): string {
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatHari(iso: string): string {
    const d = new Date(iso);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short' });
}

// 10-step warm-to-cool gradient for ranking
const RANK_COLORS = [
    '#f59e0b', '#f97316', '#ef4444', '#ec4899', '#a855f7',
    '#8b5cf6', '#6366f1', '#3b82f6', '#06b6d4', '#14b8a6',
];
function rankColor(index: number): string {
    return RANK_COLORS[Math.min(index, RANK_COLORS.length - 1)];
}

const TOP_N_OPTIONS = [5, 10, 15, 20] as const;
type TopN = typeof TOP_N_OPTIONS[number] | 'semua';

// ─────────────────────────────────────────────
// Sub-components
// ─────────────────────────────────────────────

/** Custom tooltip for bar chart */
function BarTooltip({ active, payload }: any) {
    if (!active || !payload?.length) return null;
    const d = payload[0].payload as ProdukFavoritRow;
    return (
        <div className="rounded-md border bg-popover px-3 py-2 text-xs shadow-md">
            <p className="mb-1 font-semibold text-foreground">{d.nama}</p>
            <p className="text-muted-foreground">Terjual: <span className="font-medium text-foreground">{d.total_terjual} pcs</span></p>
            <p className="text-muted-foreground">Hasil: <span className="font-medium text-foreground">{formatRupiah(d.total_hasil)}</span></p>
        </div>
    );
}

/** Custom tooltip for donut */
function DonutTooltip({ active, payload }: any) {
    if (!active || !payload?.length) return null;
    const d = payload[0];
    return (
        <div className="rounded-md border bg-popover px-3 py-2 text-xs shadow-md">
            <p className="font-semibold text-foreground">{d.name}</p>
            <p className="text-muted-foreground">{d.value} pcs ({d.payload.persen}%)</p>
        </div>
    );
}

/** Progress bar for a single product */
function RankBar({ row, max, totalQty }: { row: ProdukFavoritRow; max: number; totalQty: number }) {
    const pct = max > 0 ? (row.total_terjual / max) * 100 : 0;
    const share = totalQty > 0 ? ((row.total_terjual / totalQty) * 100).toFixed(1) : '0.0';
    const color = rankColor(row.nomor - 1);
    return (
        <div className="group flex items-center gap-3 py-2">
            {/* Rank badge */}
            <div
                className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                style={{ background: color }}
            >
                {row.nomor}
            </div>
            {/* Name + bar */}
            <div className="min-w-0 flex-1">
                <div className="mb-1 flex items-center justify-between gap-2">
                    <span className="truncate text-xs font-medium">{row.nama}</span>
                    <span className="shrink-0 text-[11px] text-muted-foreground">
                        {row.total_terjual} pcs · {share}%
                    </span>
                </div>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full rounded-full transition-all duration-700"
                        style={{ width: `${pct}%`, background: color }}
                    />
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────
// Main Page
// ─────────────────────────────────────────────

export default function LaporanProdukFavorit(props: PageProps) {
    const { data, filters, cabangOptions, user } = props;
    const { url } = usePage();
    const queryIndex = String(url).indexOf('?');
    const rawQuery   = queryIndex >= 0 ? String(url).substring(queryIndex + 1) : '';
    const querySuffix = rawQuery ? `&${rawQuery}` : '';

    const exportPdfHref   = `/laporan/export-pdf?jenis=produk_favorit${querySuffix}`;
    const exportExcelHref = `/laporan/export-excel?jenis=produk_favorit${querySuffix}`;

    const [selectedInterval, setSelectedInterval] = useState(filters.interval || 'hari');
    const [topN, setTopN]         = useState<TopN>(10);
    const [chartTab, setChartTab] = useState<'bar' | 'donut'>('bar');

    const items    = data?.items    ?? [];
    const ringkasan = data?.ringkasan;

    const totalQty = ringkasan?.total_jumlah_terjual ?? 0;

    // Slice to top-N
    const displayedItems = useMemo(() => {
        if (topN === 'semua') return items;
        return items.slice(0, topN);
    }, [items, topN]);

    // Max for progress bar scaling
    const maxQty = displayedItems[0]?.total_terjual ?? 1;

    // Donut data: top-N + "Lainnya"
    const donutData = useMemo(() => {
        const sliced = topN === 'semua' ? items : items.slice(0, topN);
        const rest   = topN === 'semua' ? [] : items.slice(topN as number);
        const restQty = rest.reduce((s, r) => s + r.total_terjual, 0);
        const result = sliced.map((r, i) => ({
            name:   r.nama,
            value:  r.total_terjual,
            persen: totalQty > 0 ? ((r.total_terjual / totalQty) * 100).toFixed(1) : '0.0',
            fill:   rankColor(i),
        }));
        if (restQty > 0) {
            result.push({
                name:   'Lainnya',
                value:  restQty,
                persen: totalQty > 0 ? ((restQty / totalQty) * 100).toFixed(1) : '0.0',
                fill:   '#94a3b8',
            });
        }
        return result;
    }, [items, topN, totalQty]);

    const isEmptyPeriod = ringkasan?.interval === 'hari';

    return (
        <AppLayout breadcrumbs={[{ title: 'Produk Favorit', href: '/laporan/produk-favorit' }]}>
            <Head title="Produk Favorit" />
            <div className="space-y-6">

                {/* ── Header ── */}
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Produk Favorit</h1>
                        <p className="text-sm text-muted-foreground">
                            Daftar produk dengan jumlah penjualan terbanyak pada periode tertentu.
                        </p>
                        {ringkasan && (
                            <p className="mt-1 text-xs text-muted-foreground">
                                Periode: {formatTanggal(ringkasan.tanggal_mulai)}
                                {ringkasan.tanggal_mulai !== ringkasan.tanggal_selesai
                                    ? ` — ${formatTanggal(ringkasan.tanggal_selesai)}`
                                    : ''}
                                {' '}·{' '}
                                <span className="capitalize">{ringkasan.interval}</span>
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button asChild size="sm" variant="outline">
                            <a href={exportPdfHref} target="_blank" rel="noreferrer">Export PDF</a>
                        </Button>
                        <Button asChild size="sm">
                            <a href={exportExcelHref}>Export Excel</a>
                        </Button>
                    </div>
                </div>

                {/* ── Filter ── */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Filter</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form method="get" className="flex flex-wrap items-end gap-3 text-sm">
                            <div className="flex flex-col">
                                <label htmlFor="interval" className="mb-1">Interval</label>
                                <select
                                    id="interval" name="interval"
                                    value={selectedInterval}
                                    onChange={(e) => setSelectedInterval(e.target.value)}
                                    className="h-9 rounded border border-input bg-background px-2"
                                >
                                    <option value="hari">Harian</option>
                                    <option value="minggu">Mingguan</option>
                                    <option value="bulan">Bulanan</option>
                                    <option value="range">Range Tanggal</option>
                                </select>
                            </div>
                            {selectedInterval !== 'range' && (
                                <div className="flex flex-col">
                                    <label htmlFor="tanggal_ref" className="mb-1">Tanggal Referensi</label>
                                    <input id="tanggal_ref" name="tanggal_ref" type="date"
                                        defaultValue={filters.tanggal_ref}
                                        className="h-9 rounded border border-input bg-background px-2" />
                                </div>
                            )}
                            {selectedInterval === 'range' && (
                                <>
                                    <div className="flex flex-col">
                                        <label htmlFor="tanggal_mulai" className="mb-1">Mulai</label>
                                        <input id="tanggal_mulai" name="tanggal_mulai" type="date"
                                            defaultValue={filters.tanggal_mulai}
                                            className="h-9 rounded border border-input bg-background px-2" />
                                    </div>
                                    <div className="flex flex-col">
                                        <label htmlFor="tanggal_selesai" className="mb-1">Selesai</label>
                                        <input id="tanggal_selesai" name="tanggal_selesai" type="date"
                                            defaultValue={filters.tanggal_selesai}
                                            className="h-9 rounded border border-input bg-background px-2" />
                                    </div>
                                </>
                            )}
                            <div className="flex flex-col">
                                <label htmlFor="cabang_id" className="mb-1">Cabang</label>
                                <select id="cabang_id" name="cabang_id"
                                    defaultValue={filters.cabang_id ?? ''}
                                    className="h-9 rounded border border-input bg-background px-2"
                                >
                                    {!(user?.authorized_branch_count === 1 && !user?.is_it_support) && (
                                        <option value="">Semua Cabang</option>
                                    )}
                                    {cabangOptions.map((c) => (
                                        <option key={c.id} value={c.id}>{c.nama}</option>
                                    ))}
                                </select>
                            </div>
                            <Button type="submit" size="sm" className="mt-5">Terapkan</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* ── Ringkasan stat cards ── */}
                {ringkasan && (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardContent className="flex items-center gap-3 pt-5">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-950">
                                    <ShoppingBag className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Total Terjual</p>
                                    <p className="text-lg font-bold">{totalQty.toLocaleString('id-ID')}</p>
                                    <p className="text-[10px] text-muted-foreground">pcs</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="flex items-center gap-3 pt-5">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-950">
                                    <Banknote className="h-4 w-4 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Total Hasil</p>
                                    <p className="text-lg font-bold">{formatRupiah(ringkasan.total_hasil)}</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="flex items-center gap-3 pt-5">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-950">
                                    <Trophy className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Produk #1</p>
                                    <p className="text-sm font-bold leading-tight">{items[0]?.nama ?? '—'}</p>
                                    <p className="text-[10px] text-muted-foreground">{items[0]?.total_terjual ?? 0} pcs terjual</p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="flex items-center gap-3 pt-5">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                    <TrendingUp className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Jumlah Produk</p>
                                    <p className="text-lg font-bold">{items.length}</p>
                                    <p className="text-[10px] text-muted-foreground">produk aktif terjual</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* ── Chart section ── */}
                {items.length > 0 && (
                    <Card>
                        <CardHeader>
                            {/* Title + controls row */}
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <CardTitle className="text-sm font-medium">Visualisasi Penjualan</CardTitle>
                                <div className="flex flex-wrap items-center gap-2">
                                    {/* Top-N selector */}
                                    <div className="flex items-center gap-1 rounded-md border bg-muted/40 p-0.5 text-xs">
                                        {(TOP_N_OPTIONS.filter(n => n <= items.length) as (TopN)[])
                                            .concat(items.length > Math.max(...TOP_N_OPTIONS) ? ['semua'] : [])
                                            .map((n) => (
                                                <button
                                                    key={n}
                                                    onClick={() => setTopN(n)}
                                                    className={`rounded px-2.5 py-1 font-medium transition-colors ${
                                                        topN === n
                                                            ? 'bg-background text-foreground shadow-sm'
                                                            : 'text-muted-foreground hover:text-foreground'
                                                    }`}
                                                >
                                                    {n === 'semua' ? 'Semua' : `Top ${n}`}
                                                </button>
                                            ))}
                                    </div>
                                    {/* Chart type toggle */}
                                    <div className="flex items-center gap-1 rounded-md border bg-muted/40 p-0.5">
                                        <button
                                            onClick={() => setChartTab('bar')}
                                            className={`flex items-center gap-1 rounded px-2.5 py-1 text-xs font-medium transition-colors ${
                                                chartTab === 'bar'
                                                    ? 'bg-background text-foreground shadow-sm'
                                                    : 'text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            <BarChart2 className="h-3 w-3" /> Bar
                                        </button>
                                        <button
                                            onClick={() => setChartTab('donut')}
                                            className={`flex items-center gap-1 rounded px-2.5 py-1 text-xs font-medium transition-colors ${
                                                chartTab === 'donut'
                                                    ? 'bg-background text-foreground shadow-sm'
                                                    : 'text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            <PieIcon className="h-3 w-3" /> Donut
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {chartTab === 'bar' && (
                                <ResponsiveContainer width="100%" height={Math.max(260, displayedItems.length * 36)}>
                                    <BarChart
                                        data={displayedItems}
                                        layout="vertical"
                                        margin={{ top: 4, right: 40, left: 4, bottom: 4 }}
                                        barCategoryGap="30%"
                                    >
                                        <CartesianGrid horizontal={false} strokeDasharray="3 3" stroke="hsl(var(--border))" />
                                        <XAxis
                                            type="number"
                                            tickLine={false}
                                            axisLine={false}
                                            tick={{ fontSize: 10, fill: 'hsl(var(--muted-foreground))' }}
                                        />
                                        <YAxis
                                            type="category"
                                            dataKey="nama"
                                            width={120}
                                            tickLine={false}
                                            axisLine={false}
                                            tick={{ fontSize: 10, fill: 'hsl(var(--muted-foreground))' }}
                                            tickFormatter={(v: string) =>
                                                v.length > 18 ? v.substring(0, 17) + '…' : v
                                            }
                                        />
                                        <Tooltip content={<BarTooltip />} cursor={{ fill: 'hsl(var(--muted))', radius: 4 }} />
                                        <Bar dataKey="total_terjual" radius={[0, 4, 4, 0]} maxBarSize={22}>
                                            {displayedItems.map((_, i) => (
                                                <Cell key={i} fill={rankColor(i)} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            )}

                            {chartTab === 'donut' && (
                                <ResponsiveContainer width="100%" height={320}>
                                    <PieChart>
                                        <Pie
                                            data={donutData}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius="52%"
                                            outerRadius="72%"
                                            paddingAngle={2}
                                            dataKey="value"
                                        >
                                            {donutData.map((entry, i) => (
                                                <Cell key={i} fill={entry.fill} />
                                            ))}
                                        </Pie>
                                        <Tooltip content={<DonutTooltip />} />
                                        <Legend
                                            iconType="circle"
                                            iconSize={8}
                                            wrapperStyle={{ fontSize: '11px' }}
                                            formatter={(value) =>
                                                value.length > 22 ? value.substring(0, 21) + '…' : value
                                            }
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* ── Progress bar ranking ── */}
                {items.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Ranking Kontribusi</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="divide-y">
                                {displayedItems.map((row) => (
                                    <RankBar
                                        key={row.produk_id}
                                        row={row}
                                        max={maxQty}
                                        totalQty={totalQty}
                                    />
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* ── Tabel detail + peak day ── */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Detail Produk</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {items.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Belum ada data penjualan untuk periode ini.
                            </p>
                        )}
                        {items.length > 0 && (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-xs">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 pr-3 text-left font-medium text-muted-foreground">No</th>
                                            <th className="py-2 pr-3 text-left font-medium text-muted-foreground">Produk</th>
                                            <th className="py-2 pr-3 text-right font-medium text-muted-foreground">Terjual</th>
                                            <th className="py-2 pr-3 text-right font-medium text-muted-foreground">Share</th>
                                            <th className="py-2 pr-3 text-right font-medium text-muted-foreground">Harga</th>
                                            <th className="py-2 pr-3 text-right font-medium text-muted-foreground">Total Hasil</th>
                                            <th className="py-2 pr-3 text-left font-medium text-muted-foreground">
                                                <span className="flex items-center gap-1">
                                                    <CalendarDays className="h-3 w-3" /> Hari Terfavorit
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((row) => {
                                            const share = totalQty > 0
                                                ? ((row.total_terjual / totalQty) * 100).toFixed(1)
                                                : '0.0';
                                            const color = rankColor(row.nomor - 1);
                                            return (
                                                <tr
                                                    key={row.nomor}
                                                    className="border-b last:border-0 transition-colors hover:bg-muted/30"
                                                >
                                                    <td className="py-2.5 pr-3 align-middle">
                                                        <span
                                                            className="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                                            style={{ background: color }}
                                                        >
                                                            {row.nomor}
                                                        </span>
                                                    </td>
                                                    <td className="py-2.5 pr-3 align-middle">
                                                        <div className="flex items-center gap-1.5">
                                                            <span className="font-medium">{row.nama}</span>
                                                            {row.nomor <= 3 && (
                                                                <span className="text-[10px]">
                                                                    {row.nomor === 1 ? '🥇' : row.nomor === 2 ? '🥈' : '🥉'}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="py-2.5 pr-3 text-right align-middle font-medium">
                                                        {row.total_terjual.toLocaleString('id-ID')}
                                                    </td>
                                                    <td className="py-2.5 pr-3 text-right align-middle">
                                                        <span
                                                            className="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold text-white"
                                                            style={{ background: color }}
                                                        >
                                                            {share}%
                                                        </span>
                                                    </td>
                                                    <td className="py-2.5 pr-3 text-right align-middle text-muted-foreground">
                                                        {formatRupiah(row.harga)}
                                                    </td>
                                                    <td className="py-2.5 pr-3 text-right align-middle font-medium">
                                                        {formatRupiah(row.total_hasil)}
                                                    </td>
                                                    <td className="py-2.5 pr-3 align-middle">
                                                        {row.peak_day ? (
                                                            <div>
                                                                <div className="font-medium text-foreground">
                                                                    {formatHari(row.peak_day.tanggal)}
                                                                </div>
                                                                <div className="text-[10px] text-muted-foreground">
                                                                    {row.peak_day.jumlah} pcs terjual
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-muted-foreground">—</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                    <tfoot>
                                        <tr className="border-t bg-muted/30">
                                            <td colSpan={2} className="py-2.5 pr-3 text-xs font-semibold">Total</td>
                                            <td className="py-2.5 pr-3 text-right text-xs font-semibold">
                                                {totalQty.toLocaleString('id-ID')}
                                            </td>
                                            <td className="py-2.5 pr-3 text-right text-xs font-semibold">100%</td>
                                            <td className="py-2.5 pr-3" />
                                            <td className="py-2.5 pr-3 text-right text-xs font-semibold">
                                                {formatRupiah(ringkasan?.total_hasil ?? 0)}
                                            </td>
                                            <td className="py-2.5 pr-3" />
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}