import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function LaporanShift({ shift, statistik_ringkasan, filter_aktif }: any) {
    const rows = shift?.data ?? [];

    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Shift', href: '/laporan/shift' }]}>
            <Head title="Laporan Shift" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Laporan Shift</h1>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Shift</div>
                        <div className="text-2xl font-bold">{statistik_ringkasan?.total_shift}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Penjualan</div>
                        <div className="text-2xl font-bold">{statistik_ringkasan?.total_penjualan}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Rata-rata/Shift</div>
                        <div className="text-2xl font-bold">{statistik_ringkasan?.rata_rata_per_shift}</div>
                    </div>
                </div>

                <div className="bg-muted p-4 rounded">
                    <div className="text-sm text-muted-foreground mb-2">Filter Aktif</div>
                    <pre className="text-sm">{JSON.stringify(filter_aktif, null, 2)}</pre>
                </div>

                <div className="bg-muted p-4 rounded">
                    <div className="flex items-center justify-between mb-3">
                        <div className="text-sm text-muted-foreground">Daftar Shift</div>
                        <div className="text-xs text-muted-foreground">
                            Total data: {shift?.total ?? 0}
                        </div>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="border-b">
                                    <th className="text-left py-2 pr-4">Shift</th>
                                    <th className="text-left py-2 pr-4">Cabang</th>
                                    <th className="text-left py-2 pr-4">Kasir</th>
                                    <th className="text-right py-2 pr-4">Total Transaksi</th>
                                    <th className="text-right py-2 pr-4">Total Penjualan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row: any) => (
                                    <tr key={row.id} className="border-b last:border-0">
                                        <td className="py-2 pr-4 align-top">
                                            <div className="font-medium">
                                                #{row.id} ({row.status})
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {row.waktu_buka} - {row.waktu_tutup ?? '-'}
                                            </div>
                                        </td>
                                        <td className="py-2 pr-4 align-top">
                                            <div className="font-medium">
                                                {row.cabang?.nama ?? '-'}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {row.cabang?.kode ?? ''}
                                            </div>
                                        </td>
                                        <td className="py-2 pr-4 align-top">
                                            <ul className="list-disc list-inside space-y-0.5">
                                                {(row.nama_kasir_list ?? []).length === 0 && row.nama_kasir && (
                                                    <li>{row.nama_kasir}</li>
                                                )}
                                                {(row.nama_kasir_list ?? []).map((n: string, idx: number) => (
                                                    <li key={idx}>{n}</li>
                                                ))}
                                            </ul>
                                        </td>
                                        <td className="py-2 pr-4 text-right align-top">
                                            {row.total_transaksi}
                                        </td>
                                        <td className="py-2 pr-4 text-right align-top">
                                            {row.total_penjualan}
                                        </td>
                                    </tr>
                                ))}
                                {rows.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="py-4 text-center text-sm text-muted-foreground">
                                            Tidak ada data shift
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
