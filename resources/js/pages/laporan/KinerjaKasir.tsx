import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function LaporanKinerjaKasir({ data }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Kinerja Kasir', href: '/laporan/kinerja-kasir' }]}> 
            <Head title="Kinerja Kasir" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Kinerja Kasir</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(data, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}