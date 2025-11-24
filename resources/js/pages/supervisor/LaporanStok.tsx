import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function SupervisorLaporanStok({ lowStock, expired }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Stok', href: '/supervisor/laporan-stok' }]}> 
            <Head title="Laporan Stok" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Laporan Stok</h1>
                <p className="text-sm text-muted-foreground">Barang stok rendah dan mendekati kadaluarsa.</p>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h2 className="font-medium">Stok Rendah</h2>
                        <pre className="bg-muted p-4 rounded">{JSON.stringify(lowStock, null, 2)}</pre>
                    </div>
                    <div>
                        <h2 className="font-medium">Mendekati Kadaluarsa</h2>
                        <pre className="bg-muted p-4 rounded">{JSON.stringify(expired, null, 2)}</pre>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}