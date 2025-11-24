import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function LaporanStok({ data }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Stok', href: '/laporan/stok' }]}> 
            <Head title="Laporan Stok" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Laporan Stok</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(data, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}