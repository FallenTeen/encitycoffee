import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

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
    total_shift: number;
    total_transaksi: number;
    total_penjualan: number;
}

interface PerformaKasirRow {
    user_id: number;
    total_shift: number;
    total_transaksi: number;
    total_penjualan: number;
}

interface StatistikHarian {
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

interface Props {
    tanggal: string;
    shift_hari_ini: any[];
    grafik_per_jam: GrafikPerJamRow[];
    produk_terlaris: ProdukTerlarisRow[];
    performa_per_cabang: PerformaCabangRow[];
    performa_per_kasir: PerformaKasirRow[];
    statistik: StatistikHarian;
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

export default function LaporanHarian({
    tanggal,
    shift_hari_ini,
    statistik,
    grafik_per_jam,
    produk_terlaris,
    performa_per_cabang,
    performa_per_kasir,
}: Props) {
    const { data, setData, get, processing } = useForm({
        tanggal: tanggal ?? '',
    });

    const maxJamValue = useMemo(() => {
        if (!grafik_per_jam || grafik_per_jam.length === 0) return 0;
        return grafik_per_jam.reduce((max, row) => {
            const v = row.total_penjualan ?? 0;
            return v > max ? v : max;
        }, 0);
    }, [grafik_per_jam]);

    const submit = () => {
        get('/laporan/harian', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: [
                'tanggal',
                'shift_hari_ini',
                'statistik',
                'grafik_per_jam',
                'produk_terlaris',
                'performa_per_cabang',
                'performa_per_kasir',
            ],
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Harian', href: '/laporan/harian' }]}>
            <Head title="Laporan Harian" />
            <div className="space-y-6">
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Laporan Harian</h1>
                        <p className="text-sm text-muted-foreground">
                            Ringkasan performa operasional untuk satu hari di seluruh cabang.
                        </p>
                    </div>
                    <form
                        className="flex items-end gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            submit();
                        }}
                    >
                        <div className="space-y-1">
                            <Label htmlFor="tanggal">Tanggal</Label>
                            <Input
                                id="tanggal"
                                type="date"
                                value={data.tanggal}
                                onChange={(e) => setData('tanggal', e.target.value)}
                            />
                        </div>
                        <Button type="submit" disabled={processing}>
                            Terapkan
                        </Button>
                    </form>
                </div>

                <div className="text-sm text-muted-foreground">
                    Data untuk tanggal <span className="font-medium text-foreground">{tanggal}</span>.
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Total Shift</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {statistik?.total_shift ?? 0}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                Buka {statistik?.shift_buka ?? 0} • Tutup {statistik?.shift_tutup ?? 0}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Total Penjualan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {formatRupiah(statistik?.total_penjualan ?? 0)}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                Tunai {formatRupiah(statistik?.total_tunai ?? 0)} • QRIS{' '}
                                {formatRupiah(statistik?.total_qris ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Total Transaksi</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {statistik?.total_transaksi ?? 0}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                Rata-rata {formatRupiah(statistik?.rata_rata_per_transaksi ?? 0)} per transaksi
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Rata-rata per Shift</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {formatRupiah(statistik?.rata_rata_per_shift ?? 0)}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                Total kasir aktif {statistik?.total_kasir ?? 0}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Grafik Penjualan per Jam</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {(!grafik_per_jam || grafik_per_jam.length === 0) && (
                            <div className="text-sm text-muted-foreground">
                                Belum ada transaksi untuk hari ini.
                            </div>
                        )}
                        {grafik_per_jam && grafik_per_jam.length > 0 && (
                            <div className="space-y-2">
                                {grafik_per_jam.map((row) => {
                                    const width =
                                        maxJamValue > 0
                                            ? (Number(row.total_penjualan ?? 0) / maxJamValue) * 100
                                            : 0;
                                    return (
                                        <div key={row.jam} className="space-y-1 text-xs">
                                            <div className="flex items-center justify-between">
                                                <span className="font-medium">
                                                    {String(row.jam).padStart(2, '0')}:00
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {row.jumlah_transaksi} trx •{' '}
                                                    {formatRupiah(row.total_penjualan)}
                                                </span>
                                            </div>
                                            <div className="h-2 rounded bg-muted">
                                                <div
                                                    className="h-2 rounded bg-primary"
                                                    style={{ width: `${width}%` }}
                                                />
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">10 Produk Terlaris</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {(!produk_terlaris || produk_terlaris.length === 0) && (
                                <div className="text-sm text-muted-foreground">
                                    Belum ada produk terjual.
                                </div>
                            )}
                            {produk_terlaris && produk_terlaris.length > 0 && (
                                <div className="space-y-2 text-xs">
                                    {produk_terlaris.map((p) => (
                                        <div
                                            key={p.produk_id}
                                            className="flex items-center justify-between border-b last:border-0 py-1"
                                        >
                                            <div className="flex-1">
                                                <div className="font-medium">{p.nama}</div>
                                                <div className="text-muted-foreground">
                                                    Terjual {p.total_terjual} •{' '}
                                                    {formatRupiah(p.pendapatan)}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Performa per Cabang</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {(!performa_per_cabang || performa_per_cabang.length === 0) && (
                                <div className="text-sm text-muted-foreground">
                                    Belum ada data cabang.
                                </div>
                            )}
                            {performa_per_cabang && performa_per_cabang.length > 0 && (
                                <div className="space-y-2 text-xs">
                                    {performa_per_cabang.map((c) => (
                                        <div
                                            key={c.cabang_id}
                                            className="flex items-center justify-between border-b last:border-0 py-1"
                                        >
                                            <div>
                                                <div className="font-medium">Cabang #{c.cabang_id}</div>
                                                <div className="text-muted-foreground">
                                                    Shift {c.total_shift} • Trx {c.total_transaksi}
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <div className="font-semibold">
                                                    {formatRupiah(c.total_penjualan)}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Performa per Kasir</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {(!performa_per_kasir || performa_per_kasir.length === 0) && (
                            <div className="text-sm text-muted-foreground">Belum ada data kasir.</div>
                        )}
                        {performa_per_kasir && performa_per_kasir.length > 0 && (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-xs">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2 pr-4">Kasir</th>
                                            <th className="text-right py-2 pr-4">Shift</th>
                                            <th className="text-right py-2 pr-4">Transaksi</th>
                                            <th className="text-right py-2 pr-4">Total Penjualan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {performa_per_kasir.map((k) => (
                                            <tr key={k.user_id} className="border-b last:border-0">
                                                <td className="py-2 pr-4 align-top">#{k.user_id}</td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {k.total_shift}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {k.total_transaksi}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
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

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Detail Shift Hari Ini</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {(!shift_hari_ini || shift_hari_ini.length === 0) && (
                            <div className="text-sm text-muted-foreground">
                                Belum ada shift yang tercatat untuk hari ini.
                            </div>
                        )}
                        {shift_hari_ini && shift_hari_ini.length > 0 && (
                            <div className="text-xs text-muted-foreground">
                                Total {shift_hari_ini.length} shift.
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
