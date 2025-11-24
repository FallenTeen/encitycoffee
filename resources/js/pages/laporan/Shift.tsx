import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function LaporanShift({ shift, statistik_ringkasan, filter_aktif }: any) {
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
                    <div className="text-sm text-muted-foreground mb-2">Shift (paginated)</div>
                    <pre className="text-sm">{JSON.stringify(shift, null, 2)}</pre>
                </div>
            </div>
        </AppLayout>
    );
}