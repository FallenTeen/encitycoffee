import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';

export default function TransaksiIndex({ transaksis }: any) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Transaksi', href: '/transaksi' }]}> 
            <Head title="Transaksi" />
            <div className="space-y-6">
                <h1 className="text-xl font-semibold">Daftar Transaksi</h1>
                <pre className="bg-muted p-4 rounded">{JSON.stringify(transaksis, null, 2)}</pre>
            </div>
        </AppLayout>
    );
}