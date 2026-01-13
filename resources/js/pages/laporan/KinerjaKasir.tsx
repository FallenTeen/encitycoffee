import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface KinerjaRow {
    id: number;
    name: string;
    total_shift: number;
    total_transaksi: number;
    total_penjualan: number;
    menit_kerja: number;
    selisih_total: number;
    jam_kerja: number;
    rata_rata_per_shift: number;
    penjualan_per_jam: number;
}

interface TrendBulananRow {
    bulan: string;
    total: number;
}

interface RataRata {
    per_shift: number;
    per_jam: number;
}

interface DataProps {
    kinerja: KinerjaRow[];
    top_5: KinerjaRow[];
    rata_rata: RataRata;
    trend_bulanan: TrendBulananRow[];
}

interface Props {
    data: DataProps;
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

export default function LaporanKinerjaKasir({ data }: Props) {
    const [search, setSearch] = useState('');
    const { kinerja, top_5, rata_rata, trend_bulanan } = data ?? {};

    const { data: filter, setData, get, processing } = useForm({
        cabang_id: '',
        user_id: '',
        tanggal_mulai: '',
        tanggal_akhir: '',
    });

    const filteredRows = useMemo(() => {
        if (!kinerja) return [];
        const term = search.trim().toLowerCase();
        if (!term) return kinerja;
        return kinerja.filter((row) => row.name.toLowerCase().includes(term));
    }, [kinerja, search]);

    const maxPerShift = useMemo(() => {
        if (!filteredRows || filteredRows.length === 0) return 0;
        return filteredRows.reduce(
            (max, r) => (r.rata_rata_per_shift > max ? r.rata_rata_per_shift : max),
            0,
        );
    }, [filteredRows]);

    const submit = () => {
        get('/laporan/kinerja-kasir', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['data'],
        });
    };

    const exportPdfHref = `/laporan/export-pdf?jenis=kinerja_kasir`;
    const exportExcelHref = `/laporan/export-excel?jenis=kinerja_kasir`;

    return (
        <AppLayout breadcrumbs={[{ title: 'Kinerja Kasir', href: '/laporan/kinerja-kasir' }]}>
            <Head title="Kinerja Kasir" />
            <div className="space-y-6">
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Kinerja Kasir</h1>
                        <p className="text-sm text-muted-foreground">
                            Audit performa kasir berdasarkan penjualan, jam kerja, dan selisih kas.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild size="sm" variant="outline">
                            <a href={exportPdfHref} target="_blank" rel="noreferrer">
                                Export PDF
                            </a>
                        </Button>
                        <Button asChild size="sm">
                            <a href={exportExcelHref}>Export Excel</a>
                        </Button>
                    </div>
                    <form
                        className="flex flex-wrap items-end gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            submit();
                        }}
                    >
                        <div className="space-y-1">
                            <Label htmlFor="tanggal_mulai">Tanggal Mulai</Label>
                            <Input
                                id="tanggal_mulai"
                                type="date"
                                value={filter.tanggal_mulai}
                                onChange={(e) => setData('tanggal_mulai', e.target.value)}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="tanggal_akhir">Tanggal Akhir</Label>
                            <Input
                                id="tanggal_akhir"
                                type="date"
                                value={filter.tanggal_akhir}
                                onChange={(e) => setData('tanggal_akhir', e.target.value)}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="cabang_id">ID Cabang</Label>
                            <Input
                                id="cabang_id"
                                type="number"
                                value={filter.cabang_id}
                                onChange={(e) => setData('cabang_id', e.target.value)}
                                placeholder="Semua"
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="user_id">ID Kasir</Label>
                            <Input
                                id="user_id"
                                type="number"
                                value={filter.user_id}
                                onChange={(e) => setData('user_id', e.target.value)}
                                placeholder="Semua"
                            />
                        </div>
                        <Button type="submit" disabled={processing}>
                            Terapkan
                        </Button>
                    </form>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">
                                Rata-rata Penjualan per Shift
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {formatRupiah(rata_rata?.per_shift ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">
                                Rata-rata Penjualan per Jam
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {formatRupiah(rata_rata?.per_jam ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Top 5 Kasir</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {(!top_5 || top_5.length === 0) && (
                                <div className="text-sm text-muted-foreground">
                                    Belum ada data kinerja kasir.
                                </div>
                            )}
                            {top_5 && top_5.length > 0 && (
                                <div className="space-y-1 text-xs">
                                    {top_5.map((row) => (
                                        <div key={row.id} className="flex justify-between">
                                            <span className="font-medium">{row.name}</span>
                                            <span>{formatRupiah(row.total_penjualan)}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between gap-2">
                            <CardTitle className="text-sm font-medium">
                                Daftar Kinerja Kasir
                            </CardTitle>
                            <Input
                                placeholder="Cari berdasarkan nama kasir..."
                                className="h-8 w-64 text-xs"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </CardHeader>
                    <CardContent>
                        {filteredRows.length === 0 && (
                            <div className="text-sm text-muted-foreground">Belum ada data.</div>
                        )}
                        {filteredRows.length > 0 && (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-xs">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 pr-4 text-left">Kasir</th>
                                            <th className="py-2 pr-4 text-right">Shift</th>
                                            <th className="py-2 pr-4 text-right">Transaksi</th>
                                            <th className="py-2 pr-4 text-right">Jam Kerja</th>
                                            <th className="py-2 pr-4 text-right">Total Penjualan</th>
                                            <th className="py-2 pr-4 text-right">Rata-rata/Shift</th>
                                            <th className="py-2 pr-4 text-right">Penjualan/Jam</th>
                                            <th className="py-2 pr-4 text-right">Total Selisih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredRows.map((row) => {
                                            const width =
                                                maxPerShift > 0
                                                    ? (row.rata_rata_per_shift / maxPerShift) * 100
                                                    : 0;
                                            return (
                                                <tr key={row.id} className="border-b last:border-0">
                                                    <td className="py-2 pr-4 align-top">
                                                        <div className="font-medium">{row.name}</div>
                                                        <div className="text-[10px] text-muted-foreground">
                                                            ID {row.id}
                                                        </div>
                                                    </td>
                                                    <td className="py-2 pr-4 text-right align-top">
                                                        {row.total_shift}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right align-top">
                                                        {row.total_transaksi}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right align-top">
                                                        {row.jam_kerja.toFixed(2)}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right align-top">
                                                        {formatRupiah(row.total_penjualan)}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right align-top">
                                                        {formatRupiah(row.rata_rata_per_shift)}
                                                        <div className="mt-1 h-1 rounded bg-muted">
                                                            <div
                                                                className="h-1 rounded bg-primary"
                                                                style={{ width: `${width}%` }}
                                                            />
                                                        </div>
                                                    </td>
                                                    <td className="py-2 pr-4 text-right align-top">
                                                        {formatRupiah(row.penjualan_per_jam)}
                                                    </td>
                                                    <td className="py-2 pr-4 text-right align-top">
                                                        {formatRupiah(row.selisih_total)}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Tren Penjualan Bulanan (Semua Kasir)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {(!trend_bulanan || trend_bulanan.length === 0) && (
                            <div className="text-sm text-muted-foreground">
                                Belum ada data tren bulanan.
                            </div>
                        )}
                        {trend_bulanan && trend_bulanan.length > 0 && (
                            <div className="space-y-1 text-xs">
                                {trend_bulanan.map((row) => (
                                    <div
                                        key={row.bulan}
                                        className="flex items-center justify-between border-b last:border-0 py-1"
                                    >
                                        <span className="font-medium">{row.bulan}</span>
                                        <span>{formatRupiah(row.total)}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
