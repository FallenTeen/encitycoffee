import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, usePage } from '@inertiajs/react';

interface StokSummary {
    nilai_inventori: number;
    stok_rendah: {
        count: number;
    };
    kadaluarsa: {
        count: number;
        potensi_kerugian: number;
    };
    per_cabang: any[];
}

interface Props {
    data: StokSummary;
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

export default function LaporanStok({ data }: Props) {
    const { url } = usePage();
    const queryIndex = String(url).indexOf('?');
    const queryString = queryIndex >= 0 ? String(url).substring(queryIndex) : '';

    const exportPdfHref = `/laporan/export-pdf?jenis=stok${queryString}`;
    const exportExcelHref = `/laporan/export-excel?jenis=stok${queryString}`;

    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Stok', href: '/laporan/stok' }]}>
            <Head title="Laporan Stok" />
            <div className="space-y-6">
                <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">Laporan Stok</h1>
                        <p className="text-sm text-muted-foreground">
                            Ringkasan nilai inventori, stok rendah, dan potensi kerugian kadaluarsa.
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

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Nilai Inventori</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {formatRupiah(data?.nilai_inventori ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Item Stok Rendah</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {data?.stok_rendah?.count ?? 0}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">Potensi Kerugian Kadaluarsa</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">
                                {formatRupiah(data?.kadaluarsa?.potensi_kerugian ?? 0)}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
