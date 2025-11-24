import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function ManagerCabangReport({ cabangs, metrics }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Laporan Cabang', href: '/manager/laporan-cabang' }]}> 
            <Head title="Laporan Cabang" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Laporan Cabang</h1>
                <p className="text-sm text-muted-foreground">Ringkasan performa cabang dan metrik utama.</p>
                <pre className="bg-muted p-4 rounded">{JSON.stringify({ cabangs, metrics }, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}