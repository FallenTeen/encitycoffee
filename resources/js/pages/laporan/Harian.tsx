import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function LaporanHarian({ tanggal, shift_hari_ini, statistik, grafik_per_jam, produk_terlaris, performa_per_cabang, performa_per_kasir }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Harian', href: '/laporan/harian' }]}> 
            <Head title="Laporan Harian" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Laporan Harian</h1>
                <p className="text-sm">Tanggal: {tanggal}</p>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Shift</div>
                        <div className="text-2xl font-bold">{statistik?.total_shift}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Penjualan</div>
                        <div className="text-2xl font-bold">{statistik?.total_penjualan}</div>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground">Total Transaksi</div>
                        <div className="text-2xl font-bold">{statistik?.total_transaksi}</div>
                    </div>
                </div>

                <div className="bg-muted p-4 rounded">
                    <div className="text-sm text-muted-foreground mb-2">Grafik per Jam</div>
                    <pre className="text-sm">{JSON.stringify(grafik_per_jam, null, 2)}</pre>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground mb-2">Produk Terlaris</div>
                        <pre className="text-sm">{JSON.stringify(produk_terlaris, null, 2)}</pre>
                    </div>
                    <div className="bg-muted p-4 rounded">
                        <div className="text-sm text-muted-foreground mb-2">Performa per Cabang</div>
                        <pre className="text-sm">{JSON.stringify(performa_per_cabang, null, 2)}</pre>
                    </div>
                </div>

                <div className="bg-muted p-4 rounded">
                    <div className="text-sm text-muted-foreground mb-2">Performa per Kasir</div>
                    <pre className="text-sm">{JSON.stringify(performa_per_kasir, null, 2)}</pre>
                </div>

                <div className="bg-muted p-4 rounded">
                    <div className="text-sm text-muted-foreground mb-2">Shift Hari Ini</div>
                    <pre className="text-sm">{JSON.stringify(shift_hari_ini, null, 2)}</pre>
                </div>
            </div>
        </AppLayout>
    );
}