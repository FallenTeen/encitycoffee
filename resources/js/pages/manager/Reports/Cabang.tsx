import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/react';

interface CabangRow {
    cabang_id: number;
    cabang: string;
    total_penjualan: number;
    jumlah_transaksi: number;
    jumlah_shift: number;
    rata_rata_per_shift: number;
    rata_rata_per_transaksi: number;
}

interface Metrics {
    tanggal_mulai: string;
    tanggal_selesai: string;
    cabang_id?: number | null;
    total_penjualan: number;
    total_transaksi: number;
    total_shift: number;
}

interface Props {
    cabangs: CabangRow[];
    metrics: Metrics;
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

export default function ManagerCabangReport({ cabangs, metrics }: Props) {
    const { data, setData, get, processing } = useForm({
        tanggal_mulai: metrics?.tanggal_mulai ?? '',
        tanggal_selesai: metrics?.tanggal_selesai ?? '',
        cabang_id: metrics?.cabang_id ? String(metrics.cabang_id) : '',
    });

    const submit = () => {
        get('/manager/laporan-cabang', {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['cabangs', 'metrics'],
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Cabang', href: '/manager/laporan-cabang' }]}>
            <Head title="Laporan Cabang" />
            <div className="space-y-6">
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Laporan Performa Cabang</h1>
                        <p className="text-sm text-muted-foreground">
                            Perbandingan omzet, jumlah transaksi, dan jumlah shift antar cabang.
                        </p>
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
                                value={data.tanggal_mulai}
                                onChange={(e) => setData('tanggal_mulai', e.target.value)}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="tanggal_selesai">Tanggal Selesai</Label>
                            <Input
                                id="tanggal_selesai"
                                type="date"
                                value={data.tanggal_selesai}
                                onChange={(e) => setData('tanggal_selesai', e.target.value)}
                            />
                        </div>
                        <div className="space-y-1">
                            <Label htmlFor="cabang_id">ID Cabang (opsional)</Label>
                            <Input
                                id="cabang_id"
                                type="number"
                                value={data.cabang_id}
                                onChange={(e) => setData('cabang_id', e.target.value)}
                                placeholder="Semua cabang"
                            />
                        </div>
                        <button
                            type="submit"
                            className="rounded bg-primary px-4 py-1.5 text-sm text-primary-foreground"
                            disabled={processing}
                        >
                            Terapkan
                        </button>
                    </form>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Ringkasan Periode</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div>
                                <div className="text-xs text-muted-foreground">Periode</div>
                                <div className="text-sm font-semibold">
                                    {metrics?.tanggal_mulai} s/d {metrics?.tanggal_selesai}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs text-muted-foreground">Total Penjualan</div>
                                <div className="text-xl font-semibold">
                                    {formatRupiah(metrics?.total_penjualan ?? 0)}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs text-muted-foreground">Total Transaksi</div>
                                <div className="text-xl font-semibold">
                                    {metrics?.total_transaksi ?? 0}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs text-muted-foreground">Total Shift</div>
                                <div className="text-xl font-semibold">
                                    {metrics?.total_shift ?? 0}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">
                            Perbandingan Kinerja Antar Cabang
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {(!cabangs || cabangs.length === 0) && (
                            <div className="text-sm text-muted-foreground">
                                Belum ada data cabang untuk periode yang dipilih.
                            </div>
                        )}
                        {cabangs && cabangs.length > 0 && (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-xs">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 pr-4 text-left">Cabang</th>
                                            <th className="py-2 pr-4 text-right">Total Penjualan</th>
                                            <th className="py-2 pr-4 text-right">Transaksi</th>
                                            <th className="py-2 pr-4 text-right">Shift</th>
                                            <th className="py-2 pr-4 text-right">Rata-rata/Shift</th>
                                            <th className="py-2 pr-4 text-right">Rata-rata/Transaksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {cabangs.map((row) => (
                                            <tr key={row.cabang_id} className="border-b last:border-0">
                                                <td className="py-2 pr-4 align-top">
                                                    <div className="font-medium">{row.cabang}</div>
                                                    <div className="text-[10px] text-muted-foreground">
                                                        ID {row.cabang_id}
                                                    </div>
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {formatRupiah(row.total_penjualan)}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {row.jumlah_transaksi}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {row.jumlah_shift}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {formatRupiah(row.rata_rata_per_shift)}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {formatRupiah(row.rata_rata_per_transaksi)}
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
        </AppLayout>
    );
}
