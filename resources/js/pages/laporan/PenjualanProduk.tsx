import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, usePage } from '@inertiajs/react';

interface TopProdukRow {
    nama: string;
    total_terjual: number;
    pendapatan: number;
    rata_rata_per_transaksi: number;
    kontribusi_persen: number;
}

interface RingkasanProps {
    total_pendapatan: number;
    top_produk: TopProdukRow[];
    ringkasan_kategori: any[];
    ringkasan_tipe: any[];
    trend_harian: any[];
}

interface PageProps {
    data: RingkasanProps;
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

export default function LaporanPenjualanProduk({ data }: PageProps) {
    const { url } = usePage();
    const queryIndex = String(url).indexOf('?');
    const rawQuery = queryIndex >= 0 ? String(url).substring(queryIndex + 1) : '';
    const querySuffix = rawQuery ? `&${rawQuery}` : '';

    const exportPdfHref = `/laporan/export-pdf?jenis=penjualan_produk${querySuffix}`;
    const exportExcelHref = `/laporan/export-excel?jenis=penjualan_produk${querySuffix}`;

    const topProduk = data?.top_produk ?? [];

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Penjualan Produk', href: '/laporan/penjualan-produk' }]}
        >
            <Head title="Penjualan Produk" />
            <div className="space-y-6">
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Penjualan Produk</h1>
                        <p className="text-sm text-muted-foreground">
                            Ringkasan pendapatan dan produk dengan kontribusi terbesar.
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
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Ringkasan Pendapatan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-semibold">
                            {formatRupiah(data?.total_pendapatan ?? 0)}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium">Top Produk</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {topProduk.length === 0 && (
                            <div className="text-sm text-muted-foreground">
                                Belum ada data penjualan produk.
                            </div>
                        )}
                        {topProduk.length > 0 && (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-xs">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="py-2 pr-4 text-left">Produk</th>
                                            <th className="py-2 pr-4 text-right">Total Terjual</th>
                                            <th className="py-2 pr-4 text-right">Pendapatan</th>
                                            <th className="py-2 pr-4 text-right">Rata-rata/Transaksi</th>
                                            <th className="py-2 pr-4 text-right">Kontribusi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {topProduk.map((row) => (
                                            <tr key={row.nama} className="border-b last:border-0">
                                                <td className="py-2 pr-4 align-top">
                                                    <div className="font-medium">{row.nama}</div>
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {row.total_terjual}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {formatRupiah(row.pendapatan)}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {formatRupiah(row.rata_rata_per_transaksi)}
                                                </td>
                                                <td className="py-2 pr-4 text-right align-top">
                                                    {row.kontribusi_persen}%
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
